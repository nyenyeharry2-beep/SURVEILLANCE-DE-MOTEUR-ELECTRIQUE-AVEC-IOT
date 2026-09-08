<?php
/**
 * Analyse un PDF Super Genies (ligne de commande ou navigateur).
 * Usage: php analyze_pdf.php /chemin/vers/paiementinscription.pdf
 */
declare(strict_types=1);

define('SUPERGENIES_NO_HEADERS', true);
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../parser/PdfParser.php';

$path = $argv[1] ?? ($_GET['file'] ?? '');
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "Usage: php analyze_pdf.php /chemin/fichier.pdf\n");
    exit(1);
}

$text = PdfParser::extractText($path);
$type = PdfParser::detectImportType($text);
$category = PdfParser::detectPaymentCategory($text);

echo "Fichier: $path\n";
echo "Caractères extraits: " . strlen($text) . "\n";
echo "Type: $type\n";
echo "Catégorie paiement: $category\n";
echo "Matricules: " . count(PdfParser::extractMatricules($text)) . "\n\n";
echo "--- Extrait (1500 premiers caractères) ---\n";
echo substr($text, 0, 1500) . "\n\n";

if ($type === 'inscriptions') {
    $rows = PdfParser::parseInscriptions($text);
    echo "Lignes inscriptions: " . count($rows) . "\n";
    if ($rows) {
        echo json_encode($rows[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    }
} else {
    $rows = PdfParser::parsePaiements($text);
    echo "Lignes paiements: " . count($rows) . "\n";
    foreach (array_slice($rows, 0, 5) as $r) {
        echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
    }
}
