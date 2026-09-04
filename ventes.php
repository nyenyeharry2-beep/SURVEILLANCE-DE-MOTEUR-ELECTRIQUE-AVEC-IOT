<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/journee.php';
require_once __DIR__ . '/includes/ventes.php';
requireLogin();

$db = getDB();
ensureJourneeSchema($db);
ensureVenteSchema($db);
$dateMetier = getBusinessDate();
$taux = getTauxUsdCdfForDate($db, $dateMetier);
$journee = getJourneeStatus($db, $dateMetier);
$filterDate = $_GET['date'] ?? $dateMetier;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if (($_POST['action'] ?? '') === 'annuler') {
        try {
            cancelVenteTransaction($db, currentUser(), (int) ($_POST['vente_id'] ?? 0), trim($_POST['motif'] ?? ''));
            flash('success', 'Vente annulée — visible sur le tableau de gestion.');
        } catch (InvalidArgumentException $e) {
            flash('danger', $e->getMessage());
        } catch (Exception $e) {
            flash('danger', 'Erreur lors de l\'annulation.');
        }
        redirect('ventes.php?date=' . urlencode($filterDate));
    }

    $lignesJson = $_POST['lignes_json'] ?? '';
    $lignes = json_decode($lignesJson, true);
    if (!is_array($lignes)) {
        $lignes = [];
    }

    try {
        $result = createVenteTransaction($db, currentUser(), [
            'lignes' => $lignes,
            'devise' => $_POST['devise'] ?? 'CDF',
            'client_nom' => trim($_POST['client_nom'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ]);
        redirect('recu.php?id=' . $result['id']);
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    } catch (InvalidArgumentException $e) {
        flash('danger', $e->getMessage());
    } catch (Exception $e) {
        flash('danger', 'Erreur lors de l\'enregistrement de la facture.');
    }
    redirect('ventes.php?date=' . urlencode($filterDate));
}

$ventes = $db->prepare('
    SELECT v.*, u.nom AS vendeur, ua.nom AS annulee_par_nom,
           (SELECT GROUP_CONCAT(CONCAT(m.nom, " x", vl.quantite) SEPARATOR ", ")
            FROM vente_lignes vl JOIN medicaments m ON m.id = vl.medicament_id
            WHERE vl.vente_id = v.id) AS details,
           (SELECT COUNT(*) FROM vente_lignes vl WHERE vl.vente_id = v.id) AS nb_lignes
    FROM ventes v
    JOIN utilisateurs u ON u.id = v.utilisateur_id
    LEFT JOIN utilisateurs ua ON ua.id = v.annulee_par
    WHERE COALESCE(v.date_jour, DATE(v.date_vente)) = ?
    ORDER BY v.date_vente DESC
');
$ventes->execute([$filterDate]);
$listeVentes = $ventes->fetchAll();

$medicaments = $db->query('SELECT id, code, nom, prix_vente, quantite_stock FROM medicaments WHERE actif = 1 AND quantite_stock > 0 ORDER BY nom')->fetchAll();

$pageTitle = 'Ventes & Factures';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!$journee['peut_vendre']): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <?= e($journee['message']) ?>
    <a href="journal.php" class="alert-link ms-2">Ouvrir la journée →</a>
</div>
<?php else: ?>
<div class="alert alert-success py-2">
    <i class="bi bi-check-circle me-1"></i>
    Journée métier du <?= formatDate($dateMetier) ?> (<?= journeeHeureDebut() ?>h–<?= journeeHeureFin() ?>h, après 20h = nouveau jour)
    — Taux : 1 USD = <?= number_format($taux, 0, ',', ' ') ?> FC
    — Fond caisse : <?= formatCDF($journee['fond_caisse_cdf']) ?> / <?= formatUSD($journee['fond_caisse_usd']) ?>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-receipt-cutoff me-1"></i> Nouvelle facture (multi-produits)</strong></div>
            <div class="card-body">
                <form method="post" id="facture-form" data-taux="<?= $taux ?>" <?= $journee['peut_vendre'] ? '' : 'onsubmit="return false"' ?>>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <input type="hidden" name="lignes_json" id="lignes-json" value="[]">

                    <div class="mb-3">
                        <label class="form-label">Rechercher un produit</label>
                        <input type="search" class="form-control" id="search-med" placeholder="Lettre ou nom…" autocomplete="off" <?= $journee['peut_vendre'] ? '' : 'disabled' ?>>
                        <select class="form-select mt-2" id="pick-med" size="6" <?= $journee['peut_vendre'] ? '' : 'disabled' ?>>
                            <?php foreach ($medicaments as $m): ?>
                            <option value="<?= $m['id'] ?>"
                                    data-code="<?= e($m['code']) ?>"
                                    data-nom="<?= e($m['nom']) ?>"
                                    data-prix="<?= $m['prix_vente'] ?>"
                                    data-stock="<?= $m['quantite_stock'] ?>">
                                <?= e($m['code'] . ' — ' . $m['nom'] . ' (' . $m['quantite_stock'] . ')') ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btn-add-line" <?= $journee['peut_vendre'] ? '' : 'disabled' ?>>
                            <i class="bi bi-plus-lg"></i> Ajouter à la facture
                        </button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Articles de la facture</label>
                        <div id="cart-lines" class="border rounded p-2 bg-light min-h-cart">
                            <p class="text-muted small mb-0" id="cart-empty">Aucun article — ajoutez des produits ci-dessus.</p>
                        </div>
                        <div class="mt-2 text-end fw-bold">Total : <span id="cart-total">0 FC</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Devise *</label>
                        <select name="devise" class="form-select" id="vente-devise" required>
                            <option value="CDF">Franc Congolais (FC)</option>
                            <option value="USD">Dollar ($)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Client (optionnel)</label>
                        <input type="text" name="client_nom" class="form-control" placeholder="Nom du client">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="btn-submit-facture" disabled>
                        <i class="bi bi-check-lg me-1"></i> Valider la facture
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h1 class="h3 mb-0"><i class="bi bi-calendar-day me-2"></i>Ventes du jour</h1>
            <form method="get" class="d-flex gap-2">
                <input type="date" name="date" class="form-control" value="<?= e($filterDate) ?>">
                <button type="submit" class="btn btn-outline-primary">Voir</button>
            </form>
        </div>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>N°</th><th>Heure</th><th>Articles</th><th>Client</th><th>Montant</th><th>Statut</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($listeVentes as $v): ?>
                    <?php
                    $devise = normalizeDevise($v['devise'] ?? 'CDF');
                    $annulee = (int) ($v['annulee'] ?? 0) === 1;
                    ?>
                    <tr class="<?= $annulee ? 'table-secondary text-muted' : '' ?>">
                        <td><code><?= e($v['numero']) ?></code></td>
                        <td><?= date('H:i', strtotime($v['date_vente'])) ?></td>
                        <td><small><?= e($v['details'] ?: '—') ?> (<?= (int)$v['nb_lignes'] ?>)</small></td>
                        <td><?= e($v['client_nom'] ?: '—') ?></td>
                        <td><?= formatMoney((float) $v['montant_total'], $devise) ?></td>
                        <td>
                            <?php if ($annulee): ?>
                            <span class="badge bg-danger">ANNULÉE</span>
                            <small class="d-block"><?= e($v['annulee_par_nom'] ?? '') ?> — <?= e($v['motif_annulation'] ?? '') ?></small>
                            <?php else: ?>
                            <span class="badge bg-success">Validée</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$annulee): ?>
                            <a href="recu.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-outline-primary" title="Reçu"><i class="bi bi-printer"></i></a>
                            <?php if ($filterDate === $dateMetier): ?>
                            <form method="post" class="d-inline" onsubmit="return confirm('Annuler cette vente ? Le stock sera restauré.');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="action" value="annuler">
                                <input type="hidden" name="vente_id" value="<?= $v['id'] ?>">
                                <input type="hidden" name="motif" value="Annulation superviseur">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Annuler"><i class="bi bi-x-circle"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php else: ?>
                            <small><?= $v['annulee_at'] ? date('d/m H:i', strtotime($v['annulee_at'])) : '' ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($listeVentes)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune vente pour cette date.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>.min-h-cart{min-height:80px}</style>
<script>
(function () {
    const form = document.getElementById('facture-form');
    if (!form) return;
    const taux = parseFloat(form.dataset.taux) || 2850;
    const cart = [];
    const pickMed = document.getElementById('pick-med');
    const searchMed = document.getElementById('search-med');
    const cartEl = document.getElementById('cart-lines');
    const cartEmpty = document.getElementById('cart-empty');
    const cartTotal = document.getElementById('cart-total');
    const lignesJson = document.getElementById('lignes-json');
    const btnSubmit = document.getElementById('btn-submit-facture');
    const deviseSelect = document.getElementById('vente-devise');

    function fmt(n, d) {
        if (d === 'USD') return '$' + n.toFixed(2).replace('.', ',');
        return Math.round(n).toLocaleString('fr-FR') + ' FC';
    }

    function filterMeds(q) {
        const ql = q.toLowerCase();
        [...pickMed.options].forEach(opt => {
            const text = opt.textContent.toLowerCase();
            opt.hidden = ql && !text.startsWith(ql) && !text.includes(ql);
        });
    }

    function renderCart() {
        cartEl.querySelectorAll('.cart-row').forEach(el => el.remove());
        if (!cart.length) {
            cartEmpty.style.display = '';
            btnSubmit.disabled = true;
            cartTotal.textContent = fmt(0, deviseSelect.value);
            lignesJson.value = '[]';
            return;
        }
        cartEmpty.style.display = 'none';
        btnSubmit.disabled = false;
        let total = 0;
        const d = deviseSelect.value;
        cart.forEach((item, i) => {
            total += item.qty * item.prix;
            const row = document.createElement('div');
            row.className = 'cart-row d-flex align-items-center gap-2 mb-2 pb-2 border-bottom';
            row.innerHTML = `
                <div class="flex-grow-1"><strong>${item.nom}</strong><br><small>${item.code}</small></div>
                <input type="number" min="1" max="${item.stock}" value="${item.qty}" class="form-control form-control-sm cart-qty" style="width:70px" data-i="${i}">
                <span class="small">${fmt(item.prix, d)}</span>
                <button type="button" class="btn btn-sm btn-outline-danger cart-rm" data-i="${i}">&times;</button>`;
            cartEl.appendChild(row);
        });
        cartTotal.textContent = fmt(total, d);
        lignesJson.value = JSON.stringify(cart.map(c => ({
            medicament_id: c.id, quantite: c.qty, prix_unitaire: c.prix
        })));

        cartEl.querySelectorAll('.cart-qty').forEach(inp => {
            inp.addEventListener('change', () => {
                const i = +inp.dataset.i;
                cart[i].qty = Math.max(1, Math.min(+inp.value || 1, cart[i].stock));
                renderCart();
            });
        });
        cartEl.querySelectorAll('.cart-rm').forEach(btn => {
            btn.addEventListener('click', () => { cart.splice(+btn.dataset.i, 1); renderCart(); });
        });
    }

    document.getElementById('btn-add-line').addEventListener('click', () => {
        const opt = pickMed.selectedOptions[0];
        if (!opt) return;
        const id = +opt.value;
        const existing = cart.find(c => c.id === id);
        if (existing) { existing.qty = Math.min(existing.qty + 1, existing.stock); }
        else {
            const prixCdf = +opt.dataset.prix;
            const prix = deviseSelect.value === 'USD' ? prixCdf / taux : prixCdf;
            cart.push({ id, nom: opt.dataset.nom, code: opt.dataset.code, stock: +opt.dataset.stock, qty: 1, prix });
        }
        renderCart();
    });

    searchMed.addEventListener('input', e => filterMeds(e.target.value.trim()));
    deviseSelect.addEventListener('change', () => {
        cart.forEach(c => {
            const opt = [...pickMed.options].find(o => +o.value === c.id);
            if (opt) {
                const prixCdf = +opt.dataset.prix;
                c.prix = deviseSelect.value === 'USD' ? prixCdf / taux : prixCdf;
            }
        });
        renderCart();
    });

    form.addEventListener('submit', e => {
        if (!cart.length) { e.preventDefault(); alert('Ajoutez au moins un produit.'); }
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
