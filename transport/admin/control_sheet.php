<?php
/**
 * Fiche de contrôle mensuelle - Reproduction du modèle papier
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
    $sql .= ' AND (s.section = ? OR c.section = ?)';
    $params[] = $filterSection;
    $params[] = $filterSection;
}

$sql .= ' ORDER BY s.nom_complet ASC';
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
                <label class="form-label small">Classe</label>
                <select name="classe" class="form-select form-select-sm">
                    <option value="">Toutes les classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterClasse == $c['id'] ? 'selected' : '' ?>><?= e(formatClassName($c)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small">Section</label>
                <select name="section" class="form-select form-select-sm">
                    <option value="">—</option>
                    <?php foreach (['A','B','C','D'] as $s): ?>
                    <option value="<?= $s ?>" <?= $filterSection === $s ? 'selected' : '' ?>><?= $s ?></option>
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
    <div class="row" style="font-size:11px;">
        <div class="col-8 text-start">
            <strong><?= e($settings['school_foundation'] ?? '') ?></strong><br>
            <?= e($settings['school_project'] ?? '') ?><br>
            <strong style="font-size:14px;"><?= e($settings['school_name'] ?? '') ?></strong><br>
            <?= e($settings['school_address'] ?? '') ?><br>
            <?= e($settings['school_quarter'] ?? '') ?><br>
            <strong><?= e($settings['school_city'] ?? '') ?></strong><br>
            Mail: <?= e($settings['school_email'] ?? '') ?><br>
            TEL: <?= e($settings['school_phone'] ?? '') ?>
        </div>
        <div class="col-4 text-end">
            <strong>SERVICE CONTROLE</strong><br>
            Section : <strong><?= e($filterSection ?: ($selectedClass['section'] ?? '________')) ?></strong><br>
            Classe : <strong><?= e($selectedClass ? formatClassName($selectedClass) : '________') ?></strong><br>
            <br>
            <strong style="font-size:16px;">Minerval</strong><br>
            <small>Année : <?= e($year['label'] ?? '') ?></small>
        </div>
    </div>
    <hr style="border:1px solid #000;">
</div>

<div class="table-responsive">
    <table class="control-sheet">
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-name">NOM &amp; POST-NOM</th>
                <?php foreach (SCHOOL_MONTHS as $info): ?>
                <th class="col-month"><?= e($info['short']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $num = 0;
        foreach ($students as $idx => $student):
            $num++;
            $studentPayments = $paymentsMap[$student['id']] ?? [];
        ?>
            <tr>
                <td class="col-num"><?= str_pad($num, 2, '0', STR_PAD_LEFT) ?></td>
                <td class="col-name"><?= e($student['nom_complet']) ?></td>
                <?php foreach (SCHOOL_MONTHS as $monthNum => $info):
                    $p = $studentPayments[$monthNum] ?? null;
                    $cellClass = '';
                    $cellContent = '';
                    if ($p) {
                        if ((float)$p['montant_paye'] > 0) {
                            $cellContent = formatMonthPayment($p);
                            $cellClass = 'cell-paid';
                            if ($p['verified_ok']) {
                                $cellContent .= ' ✓';
                                $cellClass .= ' cell-ok';
                            }
                        } else {
                            $cellContent = '—';
                            $cellClass = 'cell-empty';
                        }
                    }
                ?>
                <td class="col-month <?= $cellClass ?>"><?= $cellContent ?></td>
                <?php endforeach; ?>
            </tr>
            <?php if (($idx + 1) % 5 === 0 && $idx + 1 < count($students)): ?>
            <tr class="row-separator"><td colspan="12"></td></tr>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php
        // Lignes vides pour compléter jusqu'à 50 (comme le modèle papier)
        $emptyRows = max(0, 50 - count($students));
        for ($i = 0; $i < min($emptyRows, 10); $i++):
            $num++;
        ?>
            <tr>
                <td class="col-num"><?= str_pad($num, 2, '0', STR_PAD_LEFT) ?></td>
                <td class="col-name">&nbsp;</td>
                <?php for ($m = 0; $m < 10; $m++): ?><td>&nbsp;</td><?php endfor; ?>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
</div>

<p class="small text-muted mt-2 no-print">
    <i class="bi bi-info-circle"></i>
    Les lignes vides séparent les groupes de 5 élèves (comme sur la fiche papier).
    Cliquez sur un élève pour gérer ses paiements et marquer OK.
</p>

<?php require_once __DIR__ . '/../includes/footer_admin.php'; ?>
