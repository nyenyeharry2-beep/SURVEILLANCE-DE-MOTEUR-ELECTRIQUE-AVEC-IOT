<?php
/** Test local du parseur paiements (sans base de données) */
declare(strict_types=1);

define('SUPERGENIES_NO_HEADERS', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../parser/PdfParser.php';

$samples = [
    'connexe' => <<<'TXT'
RAPPORT FRAIS CONNEXES 2026-2027
Classe : 1ère Maternelle
12345 KABIKA AURELIA 1ère Maternelle 30.00 30.00 Payé
12346 MULUMBA GRACE 1ère Maternelle 30.00 15.00 Partiel
TXT,
    'minerval' => <<<'TXT'
RAPPORT MINERVAL SEPTEMBRE 2026-2027
Reçu 98765 KABIKA AURELIA 65.00 65.00 Payé
Reçu 98766 MULUMBA GRACE 65.00 0.00 Impayé
TXT,
    'matricule' => <<<'TXT'
PAIEMENTS SCOLAIRES
CSLSG-2026-2027-00167 KABIKA AURELIA 65.00 65.00 Payé
TXT,
];

foreach ($samples as $name => $text) {
    echo "=== $name (cat: " . PdfParser::detectPaymentCategory($text) . ") ===\n";
    $rows = PdfParser::parsePaiements($text);
    foreach ($rows as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
    echo count($rows) . " ligne(s)\n\n";
}

echo "OK\n";
