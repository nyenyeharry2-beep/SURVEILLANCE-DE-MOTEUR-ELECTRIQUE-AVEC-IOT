<?php
/**
 * Export CSV et PDF
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$yearId = getSelectedYearId();
$db = getDB();
$type = $_GET['type'] ?? 'csv';
$filterClasse = (int) ($_GET['classe'] ?? 0);
$filterSection = trim($_GET['section'] ?? '');

$sql = 'SELECT s.id, s.numero_dossier, s.nom_complet, s.section, s.telephone_parent, s.adresse,
               c.nom AS classe_nom, c.section AS classe_section, bs.nom AS arret_nom
        FROM students s
        LEFT JOIN classes c ON s.classe_id = c.id
        LEFT JOIN bus_stops bs ON s.arret_id = bs.id
        WHERE s.academic_year_id = ? AND s.statut = "actif"';
$params = [$yearId];
if ($filterClasse) { $sql .= ' AND s.classe_id = ?'; $params[] = $filterClasse; }
if ($filterSection) { $sql .= ' AND c.section = ?'; $params[] = $filterSection; }
$sql .= ' ORDER BY c.ordre ASC, c.section ASC, s.nom_complet ASC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

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

$year = getAcademicYearById($yearId);
$settings = getAllSettings();

if ($type === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="transport_' . ($year['label'] ?? 'export') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    $headers = ['N°', 'Dossier', 'Nom & Post-nom', 'Classe', 'Section', 'Téléphone', 'Arrêt', 'Adresse'];
    foreach (SCHOOL_MONTHS as $info) {
        $headers[] = $info['label'] . ' (Payé)';
        $headers[] = $info['label'] . ' (OK)';
    }
    fputcsv($out, $headers, ';');

    $num = 0;
    foreach ($students as $s) {
        $num++;
        $row = [
            $num, $s['numero_dossier'], $s['nom_complet'], $s['classe_nom'], $s['section'] ?: $s['classe_section'],
            $s['telephone_parent'], $s['arret_nom'], $s['adresse']
        ];
        $sp = $paymentsMap[$s['id']] ?? [];
        foreach (SCHOOL_MONTHS as $monthNum => $info) {
            $p = $sp[$monthNum] ?? null;
            $row[] = $p ? $p['montant_paye'] : '';
            $row[] = ($p && $p['verified_ok']) ? 'OK' : '';
        }
        fputcsv($out, $row, ';');
    }
    fclose($out);
    logActivity('export_csv', 'report', null, 'Export CSV');
    exit;
}

if ($type === 'pdf') {
    // Export HTML imprimable (compatible InfinityFree sans lib PDF)
    $pageTitle = 'Export PDF';
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Fiche Contrôle Transport - <?= e($year['label'] ?? '') ?></title>
        <style>
            @page { size: A4 landscape; margin: 10mm; }
            body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 10px; }
            .school-print-header { margin-bottom: 8px; }
            .school-print-logo { text-align: center; margin-bottom: 6px; }
            .school-logo-print { height: 70px; width: auto; object-fit: contain; }
            .school-print-name { font-size: 14px; }
            .school-print-title { font-size: 16px; }
            .school-print-body { display: flex; justify-content: space-between; gap: 1rem; }
            .school-print-left { flex: 2; text-align: left; }
            .school-print-right { flex: 1; text-align: right; }
            .school-print-divider { border: 1px solid #000; margin: 5px 0; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 2px 3px; text-align: center; font-size: 9px; }
            th { background: #f0f0f0; font-weight: bold; }
            .col-name { text-align: left; min-width: 150px; padding-left: 5px; }
            .col-num { width: 25px; }
            .col-ok-pdf { width: 22px; min-width: 22px; background: #fafafa; border-left: 1px solid #666 !important; }
            .cell-ok-marked { color: #198754; font-weight: bold; }
            .title { font-size: 14px; font-weight: bold; }
            hr { border: 1px solid #000; margin: 5px 0; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
    <div class="no-print" style="margin-bottom:10px;">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">🖨️ Imprimer / Enregistrer PDF</button>
    </div>

    <?php renderSchoolPrintHeader($settings, [
        'section' => $filterSection ?: '________',
        'classe' => $filterClasse ? 'Filtrée' : 'Toutes',
        'year' => $year['label'] ?? '',
        'show_date' => true,
    ]); ?>

    <table>
        <?php renderControlSheetThead(true); ?>
        <tbody>
        <?php
        $num = 0;
        foreach ($students as $s):
            $num++;
            renderControlSheetStudentRow($s, $paymentsMap[$s['id']] ?? [], $num, false, true);
        endforeach;

        for ($i = count($students); $i < 50; $i++):
            renderControlSheetBlankRow($i + 1, true);
        endfor;
        ?>
        </tbody>
    </table>

    <script>window.onload = function() { /* auto-print option: window.print(); */ };</script>
    </body>
    </html>
    <?php
    logActivity('export_pdf', 'report', null, 'Export PDF');
    exit;
}

redirect(BASE_URL . '/admin/dashboard.php');
