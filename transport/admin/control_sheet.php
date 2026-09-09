<?php
/**
 * Fiche de contrôle mensuelle - Reproduction du modèle papier
 * Chaque mois est suivi d'une colonne vide pour marquer OK après vérification
 */
$pageTitle = 'Fiche de contrôle';
require_once __DIR__ . '/../includes/header_admin.php';

$yearId = getSelectedYearId();
$db = getDB();

$filterClasse = (int) ($_GET['classe'] ?? 0);
$filterSection = trim($_GET['section'] ?? '');
$year = getAcademicYearById($yearId);
$classes = getActiveClasses();
$settings = getAllSettings();

// Marquer OK sur paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    if (($_POST['action'] ?? '') === 'mark_ok' && !empty($_POST['payment_id'])) {
        $pid = (int) $_POST['payment_id'];
        $db->prepare('UPDATE payments SET verified_ok = 1 WHERE id = ?')->execute([$pid]);
        logActivity('verify_payment', 'payment', $pid, 'Paiement marqué OK');
        flashMessage('success', 'Paiement marqué OK.');
    }
    redirect(BASE_URL . '/admin/control_sheet.php?' . http_build_query($_GET));
}

$sql = 'SELECT s.id, s.numero_dossier, s.nom_complet, s.section, c.nom AS classe_nom, c.section AS classe_section
        FROM students s
        LEFT JOIN classes c ON s.classe_id = c.id
        WHERE s.academic_year_id = ? AND s.statut = "actif"';
$params = [$yearId];

if ($filterClasse) {
    $sql .= ' AND s.classe_id = ?';
    $params[] = $filterClasse;
}
if ($filterSection) {
    $sql .= ' AND c.section = ?';
    $params[] = $filterSection;
}

$sql .= ' ORDER BY c.ordre ASC, c.section ASC, s.nom_complet ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Charger tous les paiements
$paymentsMap = [];
if ($students) {
    $ids = array_column($students, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM payments WHERE student_id IN ($placeholders) AND academic_year_id = ?");
    $stmt->execute([...$ids, $yearId]);
    foreach ($stmt->fetchAll() as $p) {
        $paymentsMap[$p['student_id']][(int)$p['mois']] = $p;
    }
}

$selectedClass = null;
if ($filterClasse) {
    foreach ($classes as $c) {
        if ($c['id'] == $filterClasse) { $selectedClass = $c; break; }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
    <h2><i class="bi bi-table"></i> Fiche de contrôle — Minerval Transport</h2>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/admin/export.php?type=pdf&classe=<?= $filterClasse ?>&section=<?= e($filterSection) ?>" class="btn btn-danger" target="_blank"><i class="bi bi-file-pdf"></i> Export PDF</a>
        <a href="<?= BASE_URL ?>/admin/export.php?type=csv&classe=<?= $filterClasse ?>" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
        <button onclick="window.print()" class="btn btn-secondary"><i class="bi bi-printer"></i> Imprimer</button>
    </div>
</div>

<div class="card mb-3 no-print">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Section</label>
                <select name="section" class="form-select form-select-sm">
                    <option value="">Toutes les sections</option>
                    <?php foreach (getSchoolSections() as $s): ?>
                    <option value="<?= e($s) ?>" <?= $filterSection === $s ? 'selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small">Classe</label>
                <select name="classe" class="form-select form-select-sm">
                    <option value="">Toutes les classes</option>
                    <?php
                    $filterClasses = $filterSection ? getClassesBySection($filterSection) : $classes;
                    foreach ($filterClasses as $c):
                    ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClasse == $c['id'] ? 'selected' : '' ?>><?= e(formatClassWithSection($c)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            </div>
        </form>
    </div>
</div>

<!-- En-tête imprimable (modèle papier) -->
<div class="print-header" style="display:block;">
    <?php renderSchoolPrintHeader($settings, [
        'section' => $filterSection ?: ($selectedClass['section'] ?? '________'),
        'classe' => $selectedClass ? formatClassName($selectedClass) : '________',
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

        // Lignes vides pour compléter (modèle papier ~50 lignes)
        $emptyRows = max(0, 50 - count($students));
        for ($i = 0; $i < min($emptyRows, 10); $i++):
            $num++;
            renderControlSheetBlankRow($num);
        endfor;
        ?>
        </tbody>
    </table>
</div>

<p class="small text-muted mt-2 no-print">
    <i class="bi bi-info-circle"></i>
    Chaque mois est suivi d'une <strong>colonne vide</strong> pour marquer <strong>OK</strong> après vérification
    (Septembre → colonne OK → Octobre → colonne OK → …).
    Cliquez sur le bouton OK dans la colonne après le montant payé.
</p>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
