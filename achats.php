<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $medicamentId = (int) ($_POST['medicament_id'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 0);
    $prixUnitaire = (float) ($_POST['prix_unitaire'] ?? 0);
    $fournisseurId = $_POST['fournisseur_id'] ?: null;
    $dateAchat = $_POST['date_achat'] ?? date('Y-m-d');
    $dateExpiration = $_POST['date_expiration'] ?: null;
    $notes = trim($_POST['notes'] ?? '');

    if ($medicamentId <= 0 || $quantite <= 0) {
        flash('danger', 'Médicament et quantité obligatoires.');
        redirect('achats.php');
    }

    $montantTotal = $quantite * $prixUnitaire;

    try {
        $db->beginTransaction();

        $db->prepare('INSERT INTO achats (fournisseur_id, utilisateur_id, date_achat, montant_total, notes) VALUES (?,?,?,?,?)')
           ->execute([$fournisseurId, currentUser()['id'], $dateAchat, $montantTotal, $notes]);
        $achatId = (int) $db->lastInsertId();

        $db->prepare('INSERT INTO achat_lignes (achat_id, medicament_id, quantite, prix_unitaire, date_expiration) VALUES (?,?,?,?,?)')
           ->execute([$achatId, $medicamentId, $quantite, $prixUnitaire, $dateExpiration]);

        $updateSql = 'UPDATE medicaments SET quantite_stock = quantite_stock + ?';
        $params = [$quantite];
        if ($dateExpiration) {
            $updateSql .= ', date_expiration = ?';
            $params[] = $dateExpiration;
        }
        $updateSql .= ' WHERE id = ?';
        $params[] = $medicamentId;
        $db->prepare($updateSql)->execute($params);

        $db->commit();
        flash('success', 'Entrée de stock enregistrée : +' . $quantite . ' unités.');
    } catch (Exception $e) {
        $db->rollBack();
        flash('danger', 'Erreur lors de l\'enregistrement de l\'achat.');
    }

    redirect('achats.php');
}

$achats = $db->query('
    SELECT a.*, f.nom AS fournisseur_nom, u.nom AS utilisateur_nom
    FROM achats a
    LEFT JOIN fournisseurs f ON f.id = a.fournisseur_id
    JOIN utilisateurs u ON u.id = a.utilisateur_id
    ORDER BY a.date_achat DESC, a.id DESC
    LIMIT 50
')->fetchAll();

$medicaments = $db->query('SELECT id, code, nom, prix_achat FROM medicaments WHERE actif = 1 ORDER BY nom')->fetchAll();
$fournisseurs = $db->query('SELECT id, nom FROM fournisseurs ORDER BY nom')->fetchAll();

$pageTitle = 'Achats';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-cart-plus me-1"></i> Nouvelle entrée de stock</strong></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Médicament *</label>
                        <select name="medicament_id" class="form-select" required id="achat-med">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($medicaments as $m): ?>
                            <option value="<?= $m['id'] ?>" data-prix="<?= $m['prix_achat'] ?>"><?= e($m['code'] . ' — ' . $m['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Quantité *</label>
                            <input type="number" name="quantite" class="form-control" min="1" required value="1">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Prix unitaire</label>
                            <input type="number" step="0.01" name="prix_unitaire" class="form-control" id="achat-prix" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date achat</label>
                        <input type="date" name="date_achat" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date expiration (lot)</label>
                        <input type="date" name="date_expiration" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i> Enregistrer</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <h1 class="h3 mb-4"><i class="bi bi-cart-plus me-2"></i>Historique des achats</h1>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Fournisseur</th><th>Par</th><th>Montant</th><th>Notes</th></tr></thead>
                    <tbody>
                    <?php foreach ($achats as $a): ?>
                    <tr>
                        <td><?= formatDate($a['date_achat']) ?></td>
                        <td><?= e($a['fournisseur_nom'] ?: '—') ?></td>
                        <td><?= e($a['utilisateur_nom']) ?></td>
                        <td><?= formatCDF((float) $a['montant_total']) ?><br><small class="text-muted"><?= formatUSD(convertirDevise((float) $a['montant_total'], 'CDF', 'USD')) ?></small></td>
                        <td><?= e($a['notes'] ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($achats)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">Aucun achat enregistré.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('achat-med')?.addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    if (opt && opt.dataset.prix) {
        document.getElementById('achat-prix').value = opt.dataset.prix;
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
