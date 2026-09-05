<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

try {
    $db = getDB();
    $interval = sqlIntervalExpirationAlerte();
    $moisAlerte = getAlerteExpirationMois();

    $stats = [
        'medicaments' => (int) $db->query('SELECT COUNT(*) FROM medicaments WHERE actif = 1')->fetchColumn(),
        'stock_faible' => (int) $db->query('SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND quantite_stock <= seuil_alerte')->fetchColumn(),
        'a_ecouler' => (int) $db->query("SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND date_expiration IS NOT NULL AND date_expiration >= CURDATE() AND date_expiration <= DATE_ADD(CURDATE(), INTERVAL {$interval})")->fetchColumn(),
        'expires' => (int) $db->query("SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND date_expiration IS NOT NULL AND date_expiration < CURDATE()")->fetchColumn(),
        'sans_dates' => (int) $db->query('SELECT COUNT(*) FROM medicaments WHERE actif = 1 AND (date_expiration IS NULL OR date_fabrication IS NULL)')->fetchColumn(),
    ];

    $ventesJour = sommeVentesDual($db, 'DATE(date_vente) = CURDATE()');
    $ventesMois = sommeVentesDual($db, 'MONTH(date_vente) = MONTH(CURDATE()) AND YEAR(date_vente) = YEAR(CURDATE())');

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
          AND m.date_expiration >= CURDATE()
          AND m.date_expiration <= DATE_ADD(CURDATE(), INTERVAL {$interval})
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
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Erreur tableau de bord</title>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head><body class="p-4">';
    echo '<div class="container" style="max-width:720px"><h1 class="h4">Erreur tableau de bord</h1>';
    echo '<div class="alert alert-danger"><strong>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</strong></div>';
    echo '<ol><li>Ouvrez <a href="test_dashboard.php">test_dashboard.php</a> pour voir l\'étape qui échoue</li>';
    echo '<li>Ouvrez <a href="diagnostic.php">diagnostic.php</a></li>';
    echo '<li>Importez <code>database/schema_nouvelle_eve_complet_v1.7.sql</code> si les tables manquent</li>';
    echo '<li>Remplacez <code>includes/medicaments_unites.php</code> par la dernière version</li>';
    echo '<li>Puis <a href="reparer.php">reparer.php</a></li></ol>';
    echo '<a href="login.php" class="btn btn-primary">Connexion</a></div></body></html>';
    exit;
}

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
                <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
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
                <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <div class="text-muted small">À écouler (<?= $moisAlerte ?> mois)</div>
                    <div class="h4 mb-0"><?= $stats['a_ecouler'] ?></div>
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
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div class="text-muted small">Ventes aujourd'hui</div>
                </div>
                <div class="h6 mb-0"><?= formatCDF((float) $ventesJour['total_cdf']) ?></div>
                <div class="h6 mb-0 text-primary"><?= formatUSD((float) $ventesJour['total_usd']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="text-muted small">Ventes ce mois</div>
                </div>
                <div class="h6 mb-0"><?= formatCDF((float) $ventesMois['total_cdf']) ?></div>
                <div class="h6 mb-0 text-primary"><?= formatUSD((float) $ventesMois['total_usd']) ?></div>
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
                <strong><i class="bi bi-hourglass-split text-warning me-1"></i> À écouler (<?= $moisAlerte ?> mois)</strong>
                <a href="stock.php?tab=ecouler" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($alertesExpiration)): ?>
                <p class="text-muted p-3 mb-0">Aucun produit à écouler dans les <?= $moisAlerte ?> prochains mois.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Médicament</th><th>Stock</th><th>Expiration</th><th>Mois restants</th></tr></thead>
                        <tbody>
                        <?php foreach ($alertesExpiration as $m): ?>
                        <tr>
                            <td><?= e($m['nom']) ?></td>
                            <td><?= $m['quantite_stock'] ?></td>
                            <td><?= formatDate($m['date_expiration']) ?></td>
                            <td><span class="badge badge-warning-expiry"><?= moisRestantsExpiration($m['date_expiration']) ?> mois</span></td>
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
                <a href="rapports.php" class="btn btn-sm btn-outline-primary">Rapports</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($dernieresVentes)): ?>
                <p class="text-muted p-3 mb-0">Aucune vente enregistrée.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>N°</th><th>Date</th><th>Client</th><th>Devise</th><th>Montant</th><th>Équivalent</th></tr></thead>
                        <tbody>
                        <?php foreach ($dernieresVentes as $v): ?>
                        <?php $devise = normalizeDevise($v['devise'] ?? 'CDF'); ?>
                        <tr>
                            <td><?= e($v['numero']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                            <td><?= e($v['client_nom'] ?: '—') ?></td>
                            <td><span class="badge bg-secondary"><?= e($devise) ?></span></td>
                            <td><?= formatMoney((float) $v['montant_total'], $devise) ?></td>
                            <td><small><?= formatDualMoney((float) $v['montant_total'], $devise) ?></small></td>
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
