<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();
$tab = $_GET['tab'] ?? 'stock';

$stockFaible = $db->query('
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.quantite_stock <= m.seuil_alerte
    ORDER BY m.quantite_stock ASC
')->fetchAll();

$expirations = $db->query("
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.date_expiration IS NOT NULL
      AND m.date_expiration <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    ORDER BY m.date_expiration ASC
")->fetchAll();

$pageTitle = 'Alertes stock';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-exclamation-triangle me-2"></i>Alertes stock & expiration</h1>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab !== 'expiration' ? 'active' : '' ?>" href="?tab=stock">
            Stock faible <span class="badge bg-danger"><?= count($stockFaible) ?></span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'expiration' ? 'active' : '' ?>" href="?tab=expiration">
            Expirations <span class="badge bg-warning text-dark"><?= count($expirations) ?></span>
        </a>
    </li>
</ul>

<?php if ($tab === 'expiration'): ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Médicament</th><th>Catégorie</th><th>Stock</th><th>Expiration</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($expirations as $m): ?>
            <tr class="<?= isExpired($m['date_expiration']) ? 'table-danger' : '' ?>">
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= e($m['categorie_nom'] ?? '—') ?></td>
                <td><?= $m['quantite_stock'] ?></td>
                <td><?= formatDate($m['date_expiration']) ?></td>
                <td>
                    <?php if (isExpired($m['date_expiration'])): ?>
                    <span class="badge badge-expired">Expiré</span>
                    <?php else: ?>
                    <span class="badge badge-warning-expiry"><?= daysUntilExpiry($m['date_expiration']) ?> jours restants</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($expirations)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Aucune alerte d'expiration.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Médicament</th><th>Catégorie</th><th>Stock actuel</th><th>Seuil alerte</th><th>Manquant</th></tr></thead>
            <tbody>
            <?php foreach ($stockFaible as $m): ?>
            <tr>
                <td><code><?= e($m['code']) ?></code></td>
                <td><?= e($m['nom']) ?></td>
                <td><?= e($m['categorie_nom'] ?? '—') ?></td>
                <td><span class="badge bg-danger"><?= $m['quantite_stock'] ?></span></td>
                <td><?= $m['seuil_alerte'] ?></td>
                <td><?= max(0, $m['seuil_alerte'] - $m['quantite_stock']) ?> unités</td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($stockFaible)): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Tous les stocks sont suffisants.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
