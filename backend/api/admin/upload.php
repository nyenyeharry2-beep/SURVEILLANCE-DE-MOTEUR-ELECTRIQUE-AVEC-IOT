<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../parser/PdfParser.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode POST requise', 405);
}

requireAdminAuth();

if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Fichier PDF requis');
}

$type = $_POST['type'] ?? 'auto';
$config = getAppConfig();
$maxBytes = ($config['upload_max_mb'] ?? 10) * 1024 * 1024;

if ($_FILES['pdf']['size'] > $maxBytes) {
    jsonError('Fichier trop volumineux (max ' . $config['upload_max_mb'] . ' Mo)');
}

$mime = mime_content_type($_FILES['pdf']['tmp_name']);
if (!in_array($mime, ['application/pdf', 'application/octet-stream'], true)) {
    jsonError('Le fichier doit être un PDF');
}

$uploadDir = __DIR__ . '/../uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['pdf']['name']);
$destPath = $uploadDir . '/' . $filename;

if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $destPath)) {
    jsonError('Échec de l\'enregistrement du fichier', 500);
}

try {
    $text = PdfParser::extractText($destPath);

    if (trim($text) === '') {
        jsonError('Impossible d\'extraire le texte du PDF. Vérifiez le format.');
    }

    if ($type === 'auto') {
        $type = PdfParser::detectImportType($text);
    }

    if (!in_array($type, ['inscriptions', 'paiements'], true)) {
        jsonError('Type d\'import invalide. Utilisez: inscriptions ou paiements');
    }

    $pdo = getPdo();

    if ($type === 'inscriptions') {
        $rows = PdfParser::parseInscriptions($text);
        if (empty($rows)) {
            jsonError('Aucune inscription détectée dans le PDF');
        }
        $result = ImportService::importInscriptions($pdo, $rows, $filename);
    } else {
        $rows = PdfParser::parsePaiements($text);
        if (empty($rows)) {
            jsonError('Aucun paiement détecté dans le PDF');
        }
        $result = ImportService::importPaiements($pdo, $rows, $filename);
    }

    jsonResponse([
        'success' => true,
        'type' => $type,
        'filename' => $filename,
        'lignes_detectees' => count($rows),
        'result' => $result,
        'message' => sprintf(
            'Import %s terminé : %d ligne(s) traitée(s), %d erreur(s).',
            $type,
            $result['processed'],
            $result['errors']
        ),
    ]);
} catch (Throwable $e) {
    jsonError('Erreur import: ' . $e->getMessage(), 500);
}
