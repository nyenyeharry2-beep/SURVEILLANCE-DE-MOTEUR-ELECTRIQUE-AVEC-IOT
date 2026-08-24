<?php
/** Page principale — affiche et logo chargés côté serveur */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$V = '2.8.1';
$base = __DIR__;
$uploadDir = $base . '/assets/uploads';

function assetUrl(string $uploadFile, string $webUpload, string $webDefault): string {
    global $uploadDir, $V;
    $path = $uploadDir . '/' . $uploadFile;
    if (file_exists($path)) {
        return $webUpload . '?v=' . filemtime($path);
    }
    return $webDefault . '?v=' . $V;
}

$posterCivil = assetUrl('poster_civil.jpg', 'assets/uploads/poster_civil.jpg', 'assets/template_mariage_civil.png');
$posterBlanche = assetUrl('poster_blanche.jpg', 'assets/uploads/poster_blanche.jpg', 'assets/template_affiche_blanche.png');
if (file_exists($uploadDir . '/couple_logo.jpg')) {
    $coupleLogo = assetUrl('couple_logo.jpg', 'assets/uploads/couple_logo.jpg', 'assets/couple_photo.png');
} elseif (file_exists($uploadDir . '/couple_photo.jpg')) {
    $coupleLogo = assetUrl('couple_photo.jpg', 'assets/uploads/couple_photo.jpg', 'assets/couple_photo.png');
} else {
    $coupleLogo = 'assets/couple_photo.png?v=' . $V;
}
$hasCustomPoster = file_exists($uploadDir . '/poster_civil.jpg');

require __DIR__ . '/views/home.php';
