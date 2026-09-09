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
if ($filterSection) { $sql .= ' AND (s.section = ? OR c.section = ?)'; $params[] = $filterSection; $params[] = $filterSection; }
$sql .= ' ORDER BY s.nom_complet ASC';
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
    foreach (SCHOOL_MONTHS as $info) $headers[] = $info['label'] . ' (Payé)';
    foreach (SCHOOL_MONTHS as $info) $headers[] = $info['label'] . ' (Statut)';
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
        }
        foreach (SCHOOL_MONTHS as $monthNum => $info) {
            $p = $sp[$monthNum] ?? null;
            $row[] = $p ? getPaymentStatusLabel($p['statut']) : '';
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
            .header { display: flex; justify-content: space-between; margin-bottom: 8px; }
            .header-left { text-align: left; }
            .header-right { text-align: right; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #000; padding: 2px 3px; text-align: center; font-size: 9px; }
            th { background: #f0f0f0; font-weight: bold; }
            .col-name { text-align: left; min-width: 150px; padding-left: 5px; }
            .col-num { width: 25px; }
            .separator td { border: none; height: 6px; border-bottom: 1px solid #ccc; }
            .title { font-size: 14px; font-weight: bold; }
            hr { border: 1px solid #000; margin: 5px 0; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
    <div class="no-print" style="margin-bottom:10px;">
        <button onclick="window.print()" style="padding:8px 16px;cursor:pointer;">🖨️ Imprimer / Enregistrer PDF</button>
    </div>

    <div class="header">
        <div class="header-left">
            <strong><?= e($settings['school_foundation'] ?? '') ?></strong><br>
            <?= e($settings['school_project'] ?? '') ?><br>
            <span class="title"><?= e($settings['school_name'] ?? '') ?></span><br>
            <?= e($settings['school_address'] ?? '') ?><br>
            <?= e($settings['school_quarter'] ?? '') ?><br>
            <strong><?= e($settings['school_city'] ?? '') ?></strong><br>
            Mail: <?= e($settings['school_email'] ?? '') ?><br>
            TEL: <?= e($settings['school_phone'] ?? '') ?>
        </div>
        <div class="header-right">
            <strong>SERVICE CONTROLE</strong><br>
            Section : <?= e($filterSection ?: '________') ?><br>
            Classe : <?= e($filterClasse ? 'Filtrée' : 'Toutes') ?><br><br>
            <span class="title">Minerval</span><br>
            Année : <?= e($year['label'] ?? '') ?><br>
            Date : <?= date('d/m/Y') ?>
        </div>
    </div>
    <hr>

    <table>
        <thead>
            <tr>
                <th class="col-num">N°</th>
                <th class="col-name">NOM &amp; POST-NOM</th>
                <?php foreach (SCHOOL_MONTHS as $info): ?>
                <th><?= e($info['short']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php
        $num = 0;
        foreach ($students as $idx => $s):
            $num++;
            $sp = $paymentsMap[$s['id']] ?? [];
        ?>
        <tr>
            <td><?= str_pad($num, 2, '0', STR_PAD_LEFT) ?></td>
            <td class="col-name"><?= e($s['nom_complet']) ?></td>
            <?php foreach (SCHOOL_MONTHS as $monthNum => $info):
                $p = $sp[$monthNum] ?? null;
                $val = ($p && (float)$p['montant_paye'] > 0) ? formatMonthPayment($p) : '';
                if ($p && $p['verified_ok'] && $val) $val .= ' ✓';
            ?>
            <td><?= $val ?></td>
            <?php endforeach; ?>
        </tr>
        <?php if (($idx + 1) % 5 === 0 && $idx + 1 < count($students)): ?>
        <tr class="separator"><td colspan="12"></td></tr>
        <?php endif; ?>
        <?php endforeach;

        for ($i = count($students); $i < 50; $i++):
            $num = $i + 1;
        ?>
        <tr>
            <td><?= str_pad($num, 2, '0', STR_PAD_LEFT) ?></td>
            <td class="col-name">&nbsp;</td>
            <?php for ($m = 0; $m < 10; $m++): ?><td>&nbsp;</td><?php endfor; ?>
        </tr>
        <?php endfor; ?>
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
