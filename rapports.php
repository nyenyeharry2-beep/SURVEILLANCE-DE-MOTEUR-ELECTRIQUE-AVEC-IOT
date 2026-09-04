<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/journee.php';
requireLogin();

$db = getDB();
ensureJourneeSchema($db);
$taux = getTauxUsdCdf();

$dateDebut = $_GET['debut'] ?? date('Y-m-01');
$dateFin = $_GET['fin'] ?? date('Y-m-d');

$where = 'DATE(date_vente) BETWEEN ? AND ?';
$params = [$dateDebut, $dateFin];

$totaux = sommeVentesDual($db, $where, $params);

$ventesParDevise = $db->prepare("
    SELECT COALESCE(devise, 'CDF') AS devise, COUNT(*) AS nb_ventes, SUM(montant_total) AS total
    FROM ventes
    WHERE {$where}
    GROUP BY COALESCE(devise, 'CDF')
");
$ventesParDevise->execute($params);
$parDevise = $ventesParDevise->fetchAll();

$ventes = $db->prepare('
    SELECT v.*, u.nom AS vendeur
    FROM ventes v
    JOIN utilisateurs u ON u.id = v.utilisateur_id
    WHERE DATE(v.date_vente) BETWEEN ? AND ?
    ORDER BY v.date_vente DESC
');
$ventes->execute($params);
$listeVentes = $ventes->fetchAll();
$rapportJours = fetchRapportParJours($db, $dateDebut, $dateFin);

$pageTitle = 'Rapports';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-bar-chart me-2"></i>Rapports de ventes</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Date début</label>
                <input type="date" name="debut" class="form-control" value="<?= e($dateDebut) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Date fin</label>
                <input type="date" name="fin" class="form-control" value="<?= e($dateFin) ?>">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card stat-card h-100 border-success">
            <div class="card-body">
                <div class="text-muted small mb-1">Total en Francs Congolais (FC)</div>
                <div class="h3 mb-1 text-success"><?= formatCDF((float) $totaux['total_cdf']) ?></div>
                <small class="text-muted">
                    Dont <?= formatCDF((float) $totaux['total_cdf_brut']) ?> en ventes FC directes
                </small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card stat-card h-100 border-primary">
            <div class="card-body">
                <div class="text-muted small mb-1">Total en Dollars ($)</div>
                <div class="h3 mb-1 text-primary"><?= formatUSD((float) $totaux['total_usd']) ?></div>
                <small class="text-muted">
                    Dont <?= formatUSD((float) $totaux['total_usd_brut']) ?> en ventes $ directes
                </small>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong><i class="bi bi-calendar-week me-1"></i> Rapport par journée</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Taux</th>
                    <th>Fond matin</th>
                    <th>Ventes</th>
                    <th>Caisse soir</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rapportJours)): ?>
            <tr><td colspan="7" class="text-center text-muted py-4">Aucune journée enregistrée sur cette période.</td></tr>
            <?php else: ?>
            <?php foreach ($rapportJours as $j): ?>
            <tr>
                <td><strong><?= formatDate($j['date']) ?></strong></td>
                <td>
                    <?php if ($j['cloture']): ?>
                    <span class="badge bg-success">Clôturée</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark">Ouverte</span>
                    <?php endif; ?>
                </td>
                <td><?= number_format($j['taux_usd_cdf'], 0, ',', ' ') ?> FC</td>
                <td><?= formatCDF($j['fond_caisse_cdf']) ?></td>
                <td>
                    <?= (int) $j['nb_ventes'] ?> ventes<br>
                    <small><?= formatCDF($j['ventes_cdf']) ?> / <?= formatUSD($j['ventes_usd']) ?></small>
                </td>
                <td>
                    <?php if ($j['caisse_cloture_cdf'] !== null): ?>
                    <?= formatCDF($j['caisse_cloture_cdf']) ?> / <?= formatUSD((float) $j['caisse_cloture_usd']) ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                    <a href="ventes.php?date=<?= e($j['date']) ?>" class="btn btn-sm btn-outline-primary">Ventes</a>
                    <a href="journal.php?date=<?= e($j['date']) ?>" class="btn btn-sm btn-outline-secondary">Journal</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header"><strong>Récapitulatif par devise</strong></div>
    <div class="table-responsive">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Devise</th>
                    <th>Nombre de ventes</th>
                    <th>Montant brut</th>
                    <th>Équivalent FC</th>
                    <th>Équivalent $</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($parDevise)): ?>
            <tr><td colspan="5" class="text-center text-muted py-4">Aucune vente sur cette période.</td></tr>
            <?php else: ?>
            <?php foreach ($parDevise as $row): ?>
            <?php $devise = normalizeDevise($row['devise']); ?>
            <tr>
                <td><span class="badge bg-secondary"><?= e($devise) ?></span></td>
                <td><?= (int) $row['nb_ventes'] ?></td>
                <td><?= formatMoney((float) $row['total'], $devise) ?></td>
                <td><?= formatCDF(convertirDevise((float) $row['total'], $devise, 'CDF')) ?></td>
                <td><?= formatUSD(convertirDevise((float) $row['total'], $devise, 'USD')) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="table-light fw-bold">
                <td>TOTAL</td>
                <td><?= array_sum(array_column($parDevise, 'nb_ventes')) ?></td>
                <td>—</td>
                <td><?= formatCDF((float) $totaux['total_cdf']) ?></td>
                <td><?= formatUSD((float) $totaux['total_usd']) ?></td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header"><strong>Détail des ventes</strong></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>N°</th><th>Date</th><th>Client</th><th>Vendeur</th>
                    <th>Devise</th><th>Montant</th><th>FC</th><th>$</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($listeVentes as $v): ?>
            <?php $devise = normalizeDevise($v['devise'] ?? 'CDF'); ?>
            <tr>
                <td><code><?= e($v['numero']) ?></code></td>
                <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                <td><?= e($v['client_nom'] ?: '—') ?></td>
                <td><?= e($v['vendeur']) ?></td>
                <td><?= e($devise) ?></td>
                <td><?= formatMoney((float) $v['montant_total'], $devise) ?></td>
                <td><?= formatCDF(convertirDevise((float) $v['montant_total'], $devise, 'CDF')) ?></td>
                <td><?= formatUSD(convertirDevise((float) $v['montant_total'], $devise, 'USD')) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($listeVentes)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4">Aucune vente sur cette période.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="bi bi-info-circle me-1"></i>
    Taux de conversion utilisé : <strong>1 USD = <?= number_format($taux, 0, ',', ' ') ?> FC</strong>
    (modifiable dans <code>config/config.php</code> → <code>TAUX_USD_CDF</code>)
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
