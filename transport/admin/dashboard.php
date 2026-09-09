<?php
$pageTitle = 'Tableau de bord';
require_once __DIR__ . '/../includes/header_admin.php';

$yearId = getSelectedYearId();
$db = getDB();

// Sélecteur année scolaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['year_id'])) {
    $_SESSION['selected_year_id'] = (int) $_POST['year_id'];
    $yearId = getSelectedYearId();
}

$years = getAllAcademicYears();
$currentYear = getAcademicYearById($yearId);

// Statistiques
$stats = [];

$stmt = $db->prepare('SELECT COUNT(*) FROM students WHERE academic_year_id = ? AND statut = "actif"');
$stmt->execute([$yearId]);
$stats['total_students'] = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(DISTINCT student_id) FROM payments WHERE academic_year_id = ?');
$stmt->execute([$yearId]);
$stats['with_transport'] = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM payments WHERE academic_year_id = ? AND montant_paye > 0');
$stmt->execute([$yearId]);
$stats['total_payments'] = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COALESCE(SUM(montant_paye), 0) FROM payments WHERE academic_year_id = ?');
$stmt->execute([$yearId]);
$stats['total_paid'] = (float) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COALESCE(SUM(reste), 0) FROM payments WHERE academic_year_id = ?');
$stmt->execute([$yearId]);
$stats['total_remaining'] = (float) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(DISTINCT student_id) FROM payments WHERE academic_year_id = ? AND statut IN ("partiel", "impaye") AND reste > 0');
$stmt->execute([$yearId]);
$stats['late_payments'] = (int) $stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM students WHERE academic_year_id = ? AND verified = 0 AND statut = "actif"');
$stmt->execute([$yearId]);
$stats['pending_verification'] = (int) $stmt->fetchColumn();

// Dernières inscriptions
$stmt = $db->prepare(
    'SELECT s.*, c.nom AS classe_nom, c.section AS classe_section
     FROM students s LEFT JOIN classes c ON s.classe_id = c.id
     WHERE s.academic_year_id = ? ORDER BY s.created_at DESC LIMIT 10'
);
$stmt->execute([$yearId]);
$recentStudents = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2><i class="bi bi-speedometer2"></i> Tableau de bord</h2>
    <form method="POST" class="d-flex align-items-center gap-2">
        <?= csrfField() ?>
        <label class="form-label mb-0 small">Année scolaire :</label>
        <select name="year_id" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
            <?php foreach ($years as $y): ?>
            <option value="<?= $y['id'] ?>" <?= $y['id'] == $yearId ? 'selected' : '' ?>><?= e($y['label']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card bg-primary text-white h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= $stats['total_students'] ?></div>
                <div class="small">Élèves inscrits</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card bg-info text-white h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= $stats['with_transport'] ?></div>
                <div class="small">Utilisent le bus</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card bg-success text-white h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= $stats['total_payments'] ?></div>
                <div class="small">Paiements</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card bg-success text-white h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= number_format($stats['total_paid'], 0) ?>$</div>
                <div class="small">Total payé</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card bg-warning text-dark h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= number_format($stats['total_remaining'], 0) ?>$</div>
                <div class="small">Reste à payer</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card stat-card bg-danger text-white h-100">
            <div class="card-body text-center">
                <div class="stat-value"><?= $stats['late_payments'] ?></div>
                <div class="small">En retard</div>
            </div>
        </div>
    </div>
</div>

<?php if ($stats['pending_verification'] > 0): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong><?= $stats['pending_verification'] ?></strong> inscription(s) en attente de vérification.
    <a href="<?= BASE_URL ?>/admin/students.php?filter=pending" class="alert-link">Voir</a>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-clock"></i> Dernières inscriptions</span>
                <a href="<?= BASE_URL ?>/admin/students.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>N° Dossier</th><th>Nom</th><th>Classe</th><th>Date</th><th>Statut</th></tr></thead>
                        <tbody>
                        <?php foreach ($recentStudents as $s): ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/admin/student.php?id=<?= $s['id'] ?>"><?= e($s['numero_dossier']) ?></a></td>
                            <td><?= e($s['nom_complet']) ?></td>
                            <td><?= e(formatClassName($s)) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                            <td><?= $s['verified'] ? '<span class="badge bg-success">OK</span>' : '<span class="badge bg-warning text-dark">En attente</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentStudents)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Aucune inscription pour le moment.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning"></i> Actions rapides</div>
            <div class="card-body d-grid gap-2">
                <a href="<?= BASE_URL ?>/admin/control_sheet.php" class="btn btn-outline-primary"><i class="bi bi-table"></i> Fiche de contrôle</a>
                <a href="<?= BASE_URL ?>/admin/payments.php?action=add" class="btn btn-outline-success"><i class="bi bi-plus-circle"></i> Ajouter un paiement</a>
                <a href="<?= BASE_URL ?>/admin/qr_code.php" class="btn btn-outline-dark"><i class="bi bi-qr-code"></i> QR Code inscription</a>
                <a href="<?= BASE_URL ?>/admin/export.php?type=csv" class="btn btn-outline-secondary"><i class="bi bi-download"></i> Export CSV</a>
                <a href="<?= BASE_URL ?>/admin/export.php?type=pdf" class="btn btn-outline-secondary"><i class="bi bi-file-pdf"></i> Export PDF</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
