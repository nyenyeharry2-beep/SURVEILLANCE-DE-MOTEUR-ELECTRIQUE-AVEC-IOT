<?php
/**
 * Fiche de contrôle mensuelle - Reproduction du modèle papier
 * Chaque mois est suivi d'une colonne vide pour marquer OK après vérification
 */
$pageTitle = 'Fiche de contrôle';
require_once __DIR__ . '/../includes/header_admin.php';

$yearId = getSelectedYearId();
$db = getDB();

$reportFilters = parseReportFilters($_GET);
$filterSection = $reportFilters['section'];
$filterOption = $reportFilters['option'];
$filterClasse = $reportFilters['classe'];

$year = getAcademicYearById($yearId);
$settings = getAllSettings();
$filterMeta = getReportFilterDisplayMeta($reportFilters);
$hasFilter = $filterSection !== '';

// Marquer OK sur paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'mark_ok' && !empty($_POST['payment_id'])) {
        $pid = (int) $_POST['payment_id'];
        $db->prepare('UPDATE payments SET verified_ok = 1 WHERE id = ?')->execute([$pid]);
        logActivity('verify_payment', 'payment', $pid, 'Paiement marqué OK');
        flashMessage('success', 'Paiement marqué OK.');
    }
    redirect(BASE_URL . '/admin/control_sheet.php?' . http_build_query($reportFilters));
}

$students = [];
$paymentsMap = [];

if ($hasFilter) {
    $sql = 'SELECT s.id, s.numero_dossier, s.nom_complet, s.section, s.adresse, s.telephone_parent, s.telephone_parent2,
                   s.arret_precision, c.nom AS classe_nom, c.section AS classe_section, bs.nom AS arret_nom
            FROM students s
            LEFT JOIN classes c ON s.classe_id = c.id
            LEFT JOIN bus_stops bs ON s.arret_id = bs.id
            WHERE s.academic_year_id = ? AND s.statut = "actif"';
    $params = [$yearId];
    applyStudentListFilters($sql, $params, $reportFilters);
    $sql .= ' ORDER BY c.ordre ASC, c.section ASC, s.nom_complet ASC';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    if ($students) {
        $ids = array_column($students, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT * FROM payments WHERE student_id IN ($placeholders) AND academic_year_id = ?");
        $stmt->execute([...$ids, $yearId]);
        foreach ($stmt->fetchAll() as $p) {
            $paymentsMap[$p['student_id']][(int)$p['mois']] = $p;
        }
    }
}

$exportPdfUrl = BASE_URL . '/admin/export.php?' . buildReportExportQuery($reportFilters, 'pdf');
$exportExcelUrl = BASE_URL . '/admin/export.php?' . buildReportExportQuery($reportFilters, 'excel', false);
$exportAddressesUrl = BASE_URL . '/admin/export.php?' . buildReportExportQuery($reportFilters, 'addresses', false);
?>

<div class="mb-3 no-print">
    <h2><i class="bi bi-table"></i> Fiche de contrôle — Minerval Transport</h2>
</div>

<div class="card mb-3 no-print">
    <div class="card-header py-2"><i class="bi bi-funnel"></i> Choisir la section, l'option et la classe</div>
    <div class="card-body py-2">
        <?php require __DIR__ . '/../includes/admin_report_filter.php'; ?>
        <p class="small text-muted mb-0 mt-2">
            Pour <strong>Secondaire</strong> : laissez « Tout le secondaire » pour 7ème, 8ème et toutes les options (1ère à 4ème),
            ou choisissez <strong>7ème-8ème</strong> / une filière (Pédagogie, Commercial…), puis la <strong>classe</strong>.
        </p>
    </div>
</div>

<?php if ($hasFilter): ?>
<div class="card mb-3 no-print border-primary">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <strong><i class="bi bi-download"></i> Télécharger le rapport filtré</strong>
            <span class="badge bg-primary"><?= count($students) ?> élève<?= count($students) > 1 ? 's' : '' ?></span>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= e($exportPdfUrl) ?>" class="btn btn-danger"><i class="bi bi-file-pdf"></i> PDF</a>
            <a href="<?= e($exportExcelUrl) ?>" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Excel</a>
            <a href="<?= e($exportAddressesUrl) ?>" class="btn btn-info text-white"><i class="bi bi-geo-alt"></i> Adresses + Tél</a>
            <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!$hasFilter): ?>
<div class="alert alert-info no-print">
    <i class="bi bi-info-circle"></i> Sélectionnez une section ci-dessus pour afficher la fiche et télécharger le rapport.
</div>
<?php else: ?>

<?php if (empty($students)): ?>
<div class="alert alert-warning no-print">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>Aucun élève inscrit</strong> pour ce filtre (<?= e($filterMeta['section']) ?> — <?= e($filterMeta['classe']) ?>).
    Les adresses et téléphones parents apparaîtront ici après les inscriptions via le formulaire en ligne.
    <a href="<?= BASE_URL ?>/admin/students.php" class="alert-link">Voir tous les élèves</a>
</div>
<?php endif; ?>

<!-- En-tête imprimable (modèle papier) -->
<div class="print-header" style="display:block;">
    <?php renderSchoolPrintHeader($settings, [
        'section' => $filterMeta['section'],
        'option' => $filterMeta['option'],
        'classe' => $filterMeta['classe'],
        'year' => $year['label'] ?? '',
    ]); ?>
</div>

<div class="table-responsive">
    <table class="control-sheet">
        <?php renderControlSheetThead(); ?>
        <tbody>
        <?php
        $num = 0;
        foreach ($students as $student):
            $num++;
            renderControlSheetStudentRow($student, $paymentsMap[$student['id']] ?? [], $num, true);
        endforeach;

        if ($students) {
            $emptyRows = max(0, 50 - count($students));
            for ($i = 0; $i < min($emptyRows, 10); $i++):
                $num++;
                renderControlSheetBlankRow($num);
            endfor;
        }
        ?>
        </tbody>
    </table>
</div>

<p class="small text-muted mt-2 no-print">
    <i class="bi bi-info-circle"></i>
    Rapport filtré : <strong><?= e($filterMeta['section']) ?></strong>
    <?php if ($filterMeta['option'] !== '—'): ?> — Option : <strong><?= e($filterMeta['option']) ?></strong><?php endif; ?>
    — Classe : <strong><?= e($filterMeta['classe']) ?></strong>
    (<?= count($students) ?> élève<?= count($students) > 1 ? 's' : '' ?>)
</p>

<p class="small text-muted mt-2 no-print">
    Chaque mois est suivi d'une <strong>colonne vide</strong> pour marquer <strong>OK</strong> après vérification.
    Cliquez sur le bouton OK dans la colonne après le montant payé.
</p>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
