<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$db = getDB();

$stats = [
    'medicaments' => (int) $db->query('SELECT COUNT(*) FROM medicaments WHERE actif = 1')->fetchColumn(),
    'stock_faible' => (int) $db->query('SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND quantite_stock <= seuil_alerte')->fetchColumn(),
    'expires_bientot' => (int) $db->query("SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND date_expiration IS NOT NULL AND date_expiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND date_expiration >= CURDATE()")->fetchColumn(),
    'expires' => (int) $db->query("SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND date_expiration IS NOT NULL AND date_expiration < CURDATE()")->fetchColumn(),
    'ventes_jour' => (float) $db->query('SELECT COALESCE(SUM(montant_total), 0) FROM ventes WHERE DATE(date_vente) = CURDATE()')->fetchColumn(),
    'ventes_mois' => (float) $db->query('SELECT COALESCE(SUM(montant_total), 0) FROM ventes WHERE MONTH(date_vente) = MONTH(CURDATE()) AND YEAR(date_vente) = YEAR(CURDATE())')->fetchColumn(),
];

$alertesStock = $db->query('
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.quantite_stock <= m.seuil_alerte
    ORDER BY m.quantite_stock ASC
    LIMIT 5
')->fetchAll();

$alertesExpiration = $db->query("
    SELECT m.*, c.nom AS categorie_nom
    FROM medicaments m
    LEFT JOIN categories c ON c.id = m.categorie_id
    WHERE m.actif = 1 AND m.date_expiration IS NOT NULL
      AND m.date_expiration <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY m.date_expiration ASC
    LIMIT 5
")->fetchAll();

$dernieresVentes = $db->query('
    SELECT v.*, u.nom AS vendeur
    FROM ventes v
    JOIN utilisateurs u ON u.id = v.utilisateur_id
    ORDER BY v.date_vente DESC
    LIMIT 5
')->fetchAll();

$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4"><i class="bi bi-speedometer2 me-2"></i>Tableau de bord</h1>

<div class="row g-3 mb-4">
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <div class="text-muted small">Médicaments</div>
                    <div class="h4 mb-0"><?= $stats['medicaments'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <div class="text-muted small">Stock faible</div>
                    <div class="h4 mb-0"><?= $stats['stock_faible'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-2">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-calendar-x"></i>
                </div>
                <div>
                    <div class="text-muted small">Expirés</div>
                    <div class="h4 mb-0"><?= $stats['expires'] ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <div>
                    <div class="text-muted small">Ventes aujourd'hui</div>
                    <div class="h5 mb-0"><?= formatMoney($stats['ventes_jour']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="stat-icon bg-info bg-opacity-10 text-info">
                    <i class="bi bi-graph-up"></i>
                </div>
                <div>
                    <div class="text-muted small">Ventes ce mois</div>
                    <div class="h5 mb-0"><?= formatMoney($stats['ventes_mois']) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-exclamation-triangle text-warning me-1"></i> Stock faible</strong>
                <a href="stock.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($alertesStock)): ?>
                <p class="text-muted p-3 mb-0">Aucune alerte de stock.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Médicament</th><th>Stock</th><th>Seuil</th></tr></thead>
                        <tbody>
                        <?php foreach ($alertesStock as $m): ?>
                        <tr>
                            <td><?= e($m['nom']) ?></td>
                            <td><span class="badge bg-danger"><?= $m['quantite_stock'] ?></span></td>
                            <td><?= $m['seuil_alerte'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-calendar-event text-danger me-1"></i> Expirations proches</strong>
                <a href="stock.php?tab=expiration" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($alertesExpiration)): ?>
                <p class="text-muted p-3 mb-0">Aucune expiration dans les 30 prochains jours.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Médicament</th><th>Expiration</th><th>Statut</th></tr></thead>
                        <tbody>
                        <?php foreach ($alertesExpiration as $m): ?>
                        <tr>
                            <td><?= e($m['nom']) ?></td>
                            <td><?= formatDate($m['date_expiration']) ?></td>
                            <td>
                                <?php if (isExpired($m['date_expiration'])): ?>
                                <span class="badge badge-expired">Expiré</span>
                                <?php else: ?>
                                <span class="badge badge-warning-expiry"><?= daysUntilExpiry($m['date_expiration']) ?> jours</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><i class="bi bi-receipt me-1"></i> Dernières ventes</strong>
                <a href="ventes.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($dernieresVentes)): ?>
                <p class="text-muted p-3 mb-0">Aucune vente enregistrée.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>N°</th><th>Date</th><th>Client</th><th>Vendeur</th><th>Montant</th></tr></thead>
                        <tbody>
                        <?php foreach ($dernieresVentes as $v): ?>
                        <tr>
                            <td><?= e($v['numero']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                            <td><?= e($v['client_nom'] ?: '—') ?></td>
                            <td><?= e($v['vendeur']) ?></td>
                            <td><?= formatMoney((float) $v['montant_total']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
