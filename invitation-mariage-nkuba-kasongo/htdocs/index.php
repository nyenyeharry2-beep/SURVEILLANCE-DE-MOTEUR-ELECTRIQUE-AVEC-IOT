<?php
/** Page principale — affiches HTML/CSS + photo couple dynamique */
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$V = '2.9.0';
$base = __DIR__;
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';

function assetUrl(string $uploadFile, string $webUpload, string $webDefault): string {
    global $uploadDir, $V;
    $path = $uploadDir . '/' . $uploadFile;
    if (file_exists($path)) {
        return $webUpload . '?v=' . filemtime($path);
    }
    if (file_exists(dirname($webDefault) === 'assets/uploads' ? $uploadDir . '/' . basename($webDefault) : __DIR__ . '/' . $webDefault)) {
        return $webDefault . '?v=' . $V;
    }
    return $webDefault . '?v=' . $V;
}

function pickCoupleUrl(): string {
    global $uploadDir, $V, $assetsDir;
    foreach ([
        [$uploadDir . '/couple_photo.jpg', 'assets/uploads/couple_photo.jpg'],
        [$uploadDir . '/couple_logo.jpg', 'assets/uploads/couple_logo.jpg'],
        [$assetsDir . '/couple_photo.jpg', 'assets/couple_photo.jpg'],
        [$assetsDir . '/couple_photo.png', 'assets/couple_photo.png'],
    ] as [$path, $web]) {
        if (file_exists($path)) {
            return $web . '?v=' . filemtime($path);
        }
    }
    return 'assets/couple_photo.jpg?v=' . $V;
}

$couplePhoto = pickCoupleUrl();
$coupleLogo = pickCoupleUrl();
$hasCustomCouple = file_exists($uploadDir . '/couple_photo.jpg') || file_exists($uploadDir . '/couple_logo.jpg');

require __DIR__ . '/views/home.php';
