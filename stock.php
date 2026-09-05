<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/medicaments_unites.php';
requireLogin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    if (($_POST['action'] ?? '') === 'retirer_expire') {
        require_once __DIR__ . '/includes/achats.php';
        try {
            retirerStockExpire($db, (int) ($_POST['medicament_id'] ?? 0));
            flash('success', 'Stock expiré retiré (mis à 0).');
        } catch (InvalidArgumentException $e) {
            flash('danger', $e->getMessage());
        }
        redirect('stock.php?tab=expiration');
    }
}

$tab = $_GET['tab'] ?? 'stock';
$moisAlerte = getAlerteExpirationMois();
$interval = sqlIntervalExpirationAlerte();

$stockFaible = $db->query('
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.quantite_stock <= m.seuil_alerte
    ORDER BY m.quantite_stock ASC
')->fetchAll();

$aEcouler = $db->query("
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.date_expiration IS NOT NULL
      AND m.date_expiration >= CURDATE()
      AND m.date_expiration <= DATE_ADD(CURDATE(), INTERVAL {$interval})
    ORDER BY m.date_expiration ASC
")->fetchAll();

$expires = $db->query("
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.date_expiration IS NOT NULL AND m.date_expiration < CURDATE()
    ORDER BY m.date_expiration ASC
")->fetchAll();

$sansDates = $db->query("
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND (m.date_expiration IS NULL OR m.date_fabrication IS NULL)
    ORDER BY m.nom ASC
")->fetchAll();

$pageTitle = 'Alertes stock';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-exclamation-triangle me-2"></i>Alertes stock & expiration</h1>

<div class="alert alert-warning mb-4">
    <i class="bi bi-bell me-1"></i>
    Les produits expirant dans les <strong><?= $moisAlerte ?> prochains mois</strong> sont signalés pour planifier leur écoulement (promotions, ventes prioritaires, retours fournisseur).
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'stock' ? 'active' : '' ?>" href="?tab=stock">
            Stock faible <span class="badge bg-danger"><?= count($stockFaible) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'ecouler' ? 'active' : '' ?>" href="?tab=ecouler">
            À écouler <span class="badge bg-warning text-dark"><?= count($aEcouler) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'expiration' ? 'active' : '' ?>" href="?tab=expiration">
            Expirés <span class="badge bg-danger"><?= count($expires) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'dates' ? 'active' : '' ?>" href="?tab=dates">
            Dates manquantes <span class="badge bg-secondary"><?= count($sansDates) ?></span>
        </a>
    </li>
</ul>

<?php if ($tab === 'ecouler'): ?>
<div class="card">
    <div class="card-header bg-warning bg-opacity-25">
        <strong><i class="bi bi-hourglass-split me-1"></i> Produits à écouler (expiration dans <?= $moisAlerte ?> mois ou moins)</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code</th><th>Médicament</th><th>Stock</th>
                    <th>Fabrication</th><th>Expiration</th><th>Mois restants</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($aEcouler as $m):
                $m = enrichMedicamentRow($m);
            ?>
            <tr class="table-warning">
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><span class="badge bg-primary"><?= e($m['stock_label']) ?></span></td>
                <td><?= formatDate($m['date_fabrication']) ?></td>
                <td><?= formatDate($m['date_expiration']) ?></td>
                <td><strong><?= moisRestantsExpiration($m['date_expiration']) ?> mois</strong></td>
                <td><small>Promouvoir la vente, proposer remise</small></td>
                <td>
                    <a href="achats.php?medicament_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-success">Entrée stock</a>
                    <a href="ventes.php" class="btn btn-sm btn-outline-primary">Vendre</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($aEcouler)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Aucun produit à écouler pour le moment.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'expiration'): ?>
<div class="card">
    <div class="card-header bg-danger bg-opacity-25">
        <strong><i class="bi bi-x-circle me-1"></i> Produits expirés — retirer du stock</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Code</th><th>Médicament</th><th>Stock</th><th>Fabrication</th><th>Expiration</th><th>Statut</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($expires as $m):
                $m = enrichMedicamentRow($m);
            ?>
            <tr class="table-danger">
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= e($m['stock_label']) ?></td>
                <td><?= formatDate($m['date_fabrication']) ?></td>
                <td><?= formatDate($m['date_expiration']) ?></td>
                <td><span class="badge badge-expired">Expiré depuis <?= abs(daysUntilExpiry($m['date_expiration'])) ?> jours</span></td>
                <td>
                    <form method="post" class="d-inline" data-confirm="Retirer tout le stock de ce produit expiré ?">
                        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                        <input type="hidden" name="action" value="retirer_expire">
                        <input type="hidden" name="medicament_id" value="<?= $m['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Retirer du stock</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($expires)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Aucun produit expiré.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php elseif ($tab === 'dates'): ?>
<div class="card">
    <div class="card-header">
        <strong><i class="bi bi-calendar-plus me-1"></i> Produits sans date de fabrication ou d'expiration</strong>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Code</th><th>Médicament</th><th>Fabrication</th><th>Expiration</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($sansDates as $m): ?>
            <tr>
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= $m['date_fabrication'] ? formatDate($m['date_fabrication']) : '<span class="text-danger">Manquante</span>' ?></td>
                <td><?= $m['date_expiration'] ? formatDate($m['date_expiration']) : '<span class="text-danger">Manquante</span>' ?></td>
                <td><a href="medicaments.php?edit=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Compléter</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sansDates)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Tous les produits ont leurs dates renseignées.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Médicament</th><th>Catégorie</th><th>Stock actuel</th><th>Seuil alerte</th><th>Manquant</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($stockFaible as $m):
                $m = enrichMedicamentRow($m);
            ?>
            <tr>
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= e($m['categorie_nom'] ?? '—') ?></td>
                <td><span class="badge bg-danger"><?= e($m['stock_label']) ?></span></td>
                <td><?= $m['seuil_alerte'] ?></td>
                <td><?= max(0, $m['seuil_alerte'] - $m['quantite_stock']) ?></td>
                <td>
                    <a href="achats.php?medicament_id=<?= $m['id'] ?>" class="btn btn-sm btn-success">Entrée stock</a>
                    <a href="achats_import.php" class="btn btn-sm btn-outline-primary">Import Excel</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($stockFaible)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Tous les stocks sont suffisants.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
