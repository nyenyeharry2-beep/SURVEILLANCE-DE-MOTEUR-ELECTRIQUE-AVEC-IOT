<?php
declare(strict_types=1);

define('SUPERGENIES_NO_HEADERS', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../parser/PdfParser.php';

$admText = PdfParser::extractText('/home/ubuntu/.cursor/projects/workspace/uploads/admissions_1ere_maternelle_5b7d.pdf');
$payText = PdfParser::extractText('/home/ubuntu/.cursor/projects/workspace/uploads/paiementinscription_3602.pdf');

$students = PdfParser::parseInscriptions($admText);
$payments = PdfParser::parsePaiements($payText);

$index = ['by_name' => [], 'by_matricule' => [], 'name_keys_sorted' => []];
foreach ($students as $s) {
    $index['by_matricule'][$s['matricule']] = $s;
    $key = PdfParser::normalizeName($s['nom'] . ' ' . $s['prenom']);
    $index['by_name'][$key][] = $s;
    $index['name_keys_sorted'][] = $key;
}
usort($index['name_keys_sorted'], fn($a, $b) => strlen($b) <=> strlen($a));

$matched = 0;
$unmatched = [];
foreach ($payments as $p) {
    $st = PdfParser::resolveStudentFromRow($p, $index);
    if ($st) {
        $matched++;
    } else {
        $unmatched[] = $p['eleve_raw'] . ' (' . $p['label'] . ' ' . $p['montant_paye'] . '$)';
    }
}

echo "Paiements: " . count($payments) . "\n";
echo "Élèves inscrits (PDF): " . count($students) . "\n";
echo "Rapprochés: $matched\n";
echo "Non rapprochés: " . count($unmatched) . "\n\n";
if ($unmatched) {
    echo "Exemples non rapprochés:\n";
    foreach (array_slice($unmatched, 0, 15) as $u) {
        echo "  - $u\n";
    }
}

// Show successful matches for maternelle students
echo "\nCorrespondances maternelle:\n";
foreach ($payments as $p) {
    $st = PdfParser::resolveStudentFromRow($p, $index);
    if ($st && str_contains($st['classe'], 'MATERNELLE')) {
        echo "  {$p['eleve_raw']} → {$st['matricule']} {$st['nom']} {$st['prenom']} | {$p['label']}\n";
    }
}
