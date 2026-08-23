<?php
/**
 * URLs des assets branding (affiche + logo) — uploads prioritaire
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$base = dirname(__DIR__);
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';

function urlFor(string $uploadPath, string $defaultPath): string {
    return file_exists($uploadPath) ? $defaultPath : $defaultPath;
}

function pickUrl(string $uploadFile, string $webUpload, string $webDefault): array {
    global $uploadDir, $assetsDir;
    $uploadPath = $uploadDir . '/' . $uploadFile;
    $exists = file_exists($uploadPath);
    return [
        'url' => $exists ? $webUpload : $webDefault,
        'custom' => $exists,
        'updated' => $exists ? filemtime($uploadPath) : null,
    ];
}

$civil = pickUrl('poster_civil.jpg', 'assets/uploads/poster_civil.jpg', 'assets/template_mariage_civil.png');
$blanche = pickUrl('poster_blanche.jpg', 'assets/uploads/poster_blanche.jpg', 'assets/template_affiche_blanche.png');
$couple = pickUrl('couple_photo.jpg', 'assets/uploads/couple_photo.jpg', 'assets/couple_photo.png');
$logo = pickUrl('couple_logo.jpg', 'assets/uploads/couple_logo.jpg', $couple['url']);

echo json_encode([
    'success' => true,
    'poster_civil' => $civil['url'],
    'poster_blanche' => $blanche['url'],
    'couple' => $couple['custom'] ? $couple['url'] : ($logo['custom'] ? $logo['url'] : 'assets/couple_photo.png'),
    'has_custom_poster_civil' => $civil['custom'],
    'has_custom_poster_blanche' => $blanche['custom'],
    'has_custom_couple' => $couple['custom'] || $logo['custom'],
]);
