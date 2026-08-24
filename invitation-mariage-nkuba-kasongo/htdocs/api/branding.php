<?php
/**
 * URLs branding — photo couple (affiches = HTML/CSS pur)
 */
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

$base = dirname(__DIR__);
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';

function coupleUrl(): string {
    global $uploadDir, $assetsDir;
    foreach ([
        [$uploadDir . '/couple_photo.jpg', 'assets/uploads/couple_photo.jpg'],
        [$uploadDir . '/couple_logo.jpg', 'assets/uploads/couple_logo.jpg'],
        [$assetsDir . '/couple_photo.jpg', 'assets/couple_photo.jpg'],
        [$assetsDir . '/couple_photo.png', 'assets/couple_photo.png'],
    ] as [$path, $web]) {
        if (file_exists($path)) return $web;
    }
    return 'assets/couple_photo.jpg';
}

$couple = coupleUrl();
$hasCustom = str_contains($couple, '/uploads/');

echo json_encode([
    'success' => true,
    'couple' => $couple,
    'has_custom_couple' => $hasCustom,
    'render_mode' => 'html',
]);
