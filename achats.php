<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/journee.php';
require_once __DIR__ . '/includes/achats.php';
require_once __DIR__ . '/includes/schema_util.php';
requireLogin();

$db = getDB();
ensureAchatSchema($db);
ensureMedicamentUnitesSchema($db);
$dateMetier = getBusinessDate();
$preselectMed = (int) ($_GET['medicament_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $lignesJson = $_POST['lignes_json'] ?? '';
    $lignes = json_decode($lignesJson, true);
    if (!is_array($lignes)) {
        $lignes = [];
    }

    try {
        $result = createAchatTransaction($db, currentUser(), [
            'lignes' => $lignes,
            'fournisseur_id' => $_POST['fournisseur_id'] ?? null,
            'date_achat' => $_POST['date_achat'] ?? $dateMetier,
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        flash('success', 'Entrée enregistrée — ' . $result['nb_lignes'] . ' produit(s) : ' . $result['details']);
    } catch (InvalidArgumentException $e) {
        flash('danger', $e->getMessage());
    } catch (Exception $e) {
        flash('danger', 'Erreur : ' . $e->getMessage());
    }
    redirect('achats.php');
}

$achatDetailSql = sqlAchatLigneDetailExpr($db, 'al', 'm');
$achats = $db->query("
    SELECT a.*, f.nom AS fournisseur_nom, u.nom AS utilisateur_nom,
           (SELECT GROUP_CONCAT({$achatDetailSql} SEPARATOR ', ')
            FROM achat_lignes al
            JOIN medicaments m ON m.id = al.medicament_id
            WHERE al.achat_id = a.id) AS details,
           (SELECT COUNT(*) FROM achat_lignes al WHERE al.achat_id = a.id) AS nb_lignes
    FROM achats a
    LEFT JOIN fournisseurs f ON f.id = a.fournisseur_id
    JOIN utilisateurs u ON u.id = a.utilisateur_id
    ORDER BY a.date_achat DESC, a.id DESC
    LIMIT 80
")->fetchAll();

$medicamentsRaw = $db->query('SELECT * FROM medicaments WHERE actif = 1 ORDER BY nom')->fetchAll();
$medicaments = array_map('enrichMedicamentRow', $medicamentsRaw);
$fournisseurs = $db->query('SELECT id, nom FROM fournisseurs ORDER BY nom')->fetchAll();

$pageTitle = 'Achats / Entrées stock';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <h1 class="h3 mb-0"><i class="bi bi-cart-plus me-2"></i>Achats & entrées de stock</h1>
    <div class="d-flex gap-2 flex-wrap">
        <a href="medicaments_import.php" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Import catalogue Excel
        </a>
        <a href="achats_import.php" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-box-arrow-in-down me-1"></i> Import entrées stock Excel
        </a>
    </div>
</div>

<div class="alert alert-info py-2">
    <i class="bi bi-info-circle me-1"></i>
    Journée métier : <?= formatDate($dateMetier) ?> — Les entrées mettent à jour le stock et le journal automatiquement.
    Unités : comprimés, plaquettes ou flacons selon le produit.
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-plus-circle me-1"></i> Nouvelle entrée (multi-produits)</strong></div>
            <div class="card-body">
                <form method="post" id="achat-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="lignes_json" id="lignes-json" value="[]">

                    <div class="mb-3">
                        <label class="form-label">Produit</label>
                        <input type="search" class="form-control mb-2" id="search-med" placeholder="Rechercher…" autocomplete="off">
                        <select class="form-select" id="pick-med" size="5">
                            <?php foreach ($medicaments as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= $preselectMed === (int)$m['id'] ? 'selected' : '' ?>
                                    data-code="<?= e($m['code']) ?>"
                                    data-nom="<?= e($m['nom']) ?>"
                                    data-type="<?= e($m['type_unite']) ?>"
                                    data-prix-achat="<?= $m['prix_achat'] ?>"
                                    data-unites="<?= e(implode(',', $m['unites_vente'])) ?>"
                                    data-cpp="<?= $m['comprimes_par_plaquette'] ?>"
                                    data-stock-label="<?= e($m['stock_label']) ?>">
                                <?= e($m['code'] . ' — ' . $m['nom'] . ' (' . $m['stock_label'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2" id="unite-picker">
                            <label class="form-label small">Unité d'entrée</label>
                            <select class="form-select form-select-sm" id="pick-unite"></select>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-4">
                                <label class="form-label small">Quantité</label>
                                <input type="number" min="1" class="form-control form-control-sm" id="pick-qty" value="1">
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Prix achat unit.</label>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="pick-prix" value="0">
                            </div>
                            <div class="col-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btn-add-line">
                                    <i class="bi bi-plus-lg"></i> Ajouter
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Articles de l'entrée</label>
                        <div id="cart-lines" class="border rounded p-2 bg-light min-h-cart">
                            <p class="text-muted small mb-0" id="cart-empty">Aucun article — ajoutez des produits.</p>
                        </div>
                        <div class="mt-2 text-end fw-bold">Total achat : <span id="cart-total">0 FC</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fournisseur</label>
                        <select name="fournisseur_id" class="form-select">
                            <option value="">— Aucun —</option>
                            <?php foreach ($fournisseurs as $f): ?>
                            <option value="<?= $f['id'] ?>"><?= e($f['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date entrée</label>
                        <input type="date" name="date_achat" class="form-control" value="<?= e($dateMetier) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="N° facture fournisseur, remarques…"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="btn-submit" disabled>
                        <i class="bi bi-check-lg me-1"></i> Enregistrer l'entrée de stock
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><strong>Historique des entrées</strong></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Produits</th><th>Fournisseur</th><th>Par</th><th>Montant</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($achats as $a): ?>
                    <tr>
                        <td><?= formatDate($a['date_achat']) ?></td>
                        <td><small><?= e($a['details'] ?: '—') ?> (<?= (int)$a['nb_lignes'] ?>)</small></td>
                        <td><?= e($a['fournisseur_nom'] ?: '—') ?></td>
                        <td><?= e($a['utilisateur_nom']) ?></td>
                        <td><?= formatCDF((float) $a['montant_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($achats)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucune entrée enregistrée.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>.min-h-cart{min-height:60px}</style>
<script>
(function () {
    const cart = [];
    const pickMed = document.getElementById('pick-med');
    const pickUnite = document.getElementById('pick-unite');
    const pickQty = document.getElementById('pick-qty');
    const pickPrix = document.getElementById('pick-prix');
    const cartEl = document.getElementById('cart-lines');
    const cartEmpty = document.getElementById('cart-empty');
    const cartTotal = document.getElementById('cart-total');
    const lignesJson = document.getElementById('lignes-json');
    const btnSubmit = document.getElementById('btn-submit');
    const form = document.getElementById('achat-form');
    const uniteLabels = { comprime: 'Comprimé', plaquette: 'Plaquette', flacon: 'Flacon' };

    function getOpt() { return pickMed.selectedOptions[0]; }

    function refreshUnitePicker() {
        const opt = getOpt();
        if (!opt) return;
        const unites = (opt.dataset.unites || 'comprime').split(',').filter(Boolean);
        pickUnite.innerHTML = unites.map(u => `<option value="${u}">${uniteLabels[u] || u}</option>`).join('');
        if (opt.dataset.prixAchat) pickPrix.value = opt.dataset.prixAchat;
    }

    pickMed.addEventListener('change', refreshUnitePicker);
    refreshUnitePicker();

    document.getElementById('search-med').addEventListener('input', e => {
        const ql = e.target.value.toLowerCase();
        [...pickMed.options].forEach(o => {
            o.hidden = ql && !o.textContent.toLowerCase().includes(ql);
        });
    });

    function renderCart() {
        cartEl.querySelectorAll('.cart-row').forEach(el => el.remove());
        if (!cart.length) {
            cartEmpty.style.display = '';
            btnSubmit.disabled = true;
            cartTotal.textContent = '0 FC';
            lignesJson.value = '[]';
            return;
        }
        cartEmpty.style.display = 'none';
        btnSubmit.disabled = false;
        let total = 0;
        cart.forEach((item, i) => {
            total += item.qty * item.prix;
            const row = document.createElement('div');
            row.className = 'cart-row d-flex align-items-center gap-2 mb-2 pb-2 border-bottom flex-wrap';
            row.innerHTML = `
                <div class="flex-grow-1"><strong>${item.nom}</strong><br><small>${item.code} — ${uniteLabels[item.unite]}</small></div>
                <input type="number" min="1" value="${item.qty}" class="form-control form-control-sm cart-qty" style="width:70px" data-i="${i}">
                <span class="small">${Math.round(item.prix).toLocaleString('fr-FR')} FC</span>
                <button type="button" class="btn btn-sm btn-outline-danger cart-rm" data-i="${i}">&times;</button>`;
            cartEl.appendChild(row);
        });
        cartTotal.textContent = Math.round(total).toLocaleString('fr-FR') + ' FC';
        lignesJson.value = JSON.stringify(cart.map(c => ({
            medicament_id: c.id, quantite: c.qty, prix_unitaire: c.prix,
            unite_entree: c.unite,
            date_fabrication: c.fabrication || null,
            date_expiration: c.expiration || null
        })));
        cartEl.querySelectorAll('.cart-qty').forEach(inp => {
            inp.addEventListener('change', () => {
                cart[+inp.dataset.i].qty = Math.max(1, +inp.value || 1);
                renderCart();
            });
        });
        cartEl.querySelectorAll('.cart-rm').forEach(btn => {
            btn.addEventListener('click', () => { cart.splice(+btn.dataset.i, 1); renderCart(); });
        });
    }

    document.getElementById('btn-add-line').addEventListener('click', () => {
        const opt = getOpt();
        if (!opt) return;
        const id = +opt.value;
        const unite = pickUnite.value || 'comprime';
        const qty = Math.max(1, +pickQty.value || 1);
        const prix = Math.max(0, +pickPrix.value || 0);
        const existing = cart.find(c => c.id === id && c.unite === unite);
        if (existing) existing.qty += qty;
        else cart.push({ id, nom: opt.dataset.nom, code: opt.dataset.code, unite, qty, prix });
        renderCart();
    });

    form.addEventListener('submit', e => {
        if (!cart.length) { e.preventDefault(); alert('Ajoutez au moins un produit.'); }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
