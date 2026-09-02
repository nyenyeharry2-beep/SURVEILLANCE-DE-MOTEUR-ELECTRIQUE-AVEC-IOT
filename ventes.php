<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $medicamentId = (int) ($_POST['medicament_id'] ?? 0);
    $quantite = (int) ($_POST['quantite'] ?? 0);
    $prixUnitaire = (float) ($_POST['prix_unitaire'] ?? 0);
    $clientNom = trim($_POST['client_nom'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($medicamentId <= 0 || $quantite <= 0) {
        flash('danger', 'Médicament et quantité obligatoires.');
        redirect('ventes.php');
    }

    $stmt = $db->prepare('SELECT * FROM medicaments WHERE id = ? AND actif = 1');
    $stmt->execute([$medicamentId]);
    $med = $stmt->fetch();

    if (!$med) {
        flash('danger', 'Médicament introuvable.');
        redirect('ventes.php');
    }

    if ($med['quantite_stock'] < $quantite) {
        flash('danger', 'Stock insuffisant. Disponible : ' . $med['quantite_stock']);
        redirect('ventes.php');
    }

    if ($prixUnitaire <= 0) {
        $prixUnitaire = (float) $med['prix_vente'];
    }

    $sousTotal = $quantite * $prixUnitaire;
    $numero = 'VTE-' . date('Ymd') . '-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

    try {
        $db->beginTransaction();

        $db->prepare('INSERT INTO ventes (numero, utilisateur_id, client_nom, montant_total, notes) VALUES (?,?,?,?,?)')
           ->execute([$numero, currentUser()['id'], $clientNom ?: null, $sousTotal, $notes]);
        $venteId = (int) $db->lastInsertId();

        $db->prepare('INSERT INTO vente_lignes (vente_id, medicament_id, quantite, prix_unitaire, sous_total) VALUES (?,?,?,?,?)')
           ->execute([$venteId, $medicamentId, $quantite, $prixUnitaire, $sousTotal]);

        $db->prepare('UPDATE medicaments SET quantite_stock = quantite_stock - ? WHERE id = ?')
           ->execute([$quantite, $medicamentId]);

        $db->commit();
        flash('success', 'Vente enregistrée : ' . $numero . ' — ' . formatMoney($sousTotal));
    } catch (Exception $e) {
        $db->rollBack();
        flash('danger', 'Erreur lors de l\'enregistrement de la vente.');
    }

    redirect('ventes.php');
}

$ventes = $db->query('
    SELECT v.*, u.nom AS vendeur,
           (SELECT GROUP_CONCAT(CONCAT(m.nom, " x", vl.quantite) SEPARATOR ", ")
            FROM vente_lignes vl JOIN medicaments m ON m.id = vl.medicament_id
            WHERE vl.vente_id = v.id) AS details
    FROM ventes v
    JOIN utilisateurs u ON u.id = v.utilisateur_id
    ORDER BY v.date_vente DESC
    LIMIT 50
')->fetchAll();

$medicaments = $db->query('SELECT id, code, nom, prix_vente, quantite_stock FROM medicaments WHERE actif = 1 AND quantite_stock > 0 ORDER BY nom')->fetchAll();

$pageTitle = 'Ventes';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-header"><strong><i class="bi bi-receipt me-1"></i> Nouvelle vente</strong></div>
            <div class="card-body">
                <form method="post" id="sale-form">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">Médicament *</label>
                        <select name="medicament_id" class="form-select" required id="vente-med">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($medicaments as $m): ?>
                            <option value="<?= $m['id'] ?>" data-prix="<?= $m['prix_vente'] ?>" data-stock="<?= $m['quantite_stock'] ?>">
                                <?= e($m['code'] . ' — ' . $m['nom'] . ' (stock: ' . $m['quantite_stock'] . ')') ?>
                            </option>
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
                            <input type="number" step="0.01" name="prix_unitaire" class="form-control" id="vente-prix" value="0">
                        </div>
                    </div>
                    <div class="mb-3 p-2 bg-light rounded text-center">
                        Total : <strong id="sale-total">0 FCFA</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Client (optionnel)</label>
                        <input type="text" name="client_nom" class="form-control" placeholder="Nom du client">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-lg me-1"></i> Valider la vente</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <h1 class="h3 mb-4"><i class="bi bi-receipt me-2"></i>Historique des ventes</h1>
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>N°</th><th>Date</th><th>Détails</th><th>Client</th><th>Vendeur</th><th>Montant</th></tr></thead>
                    <tbody>
                    <?php foreach ($ventes as $v): ?>
                    <tr>
                        <td><code><?= e($v['numero']) ?></code></td>
                        <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                        <td><small><?= e($v['details'] ?: '—') ?></small></td>
                        <td><?= e($v['client_nom'] ?: '—') ?></td>
                        <td><?= e($v['vendeur']) ?></td>
                        <td><?= formatMoney((float) $v['montant_total']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ventes)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucune vente enregistrée.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('vente-med')?.addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    if (opt && opt.dataset.prix) {
        document.getElementById('vente-prix').value = opt.dataset.prix;
        document.getElementById('vente-prix').dispatchEvent(new Event('input'));
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
