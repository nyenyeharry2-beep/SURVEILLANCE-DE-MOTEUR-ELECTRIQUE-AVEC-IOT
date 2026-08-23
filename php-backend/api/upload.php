<?php
/**
 * Upload photos — met à jour affiche + logo automatiquement
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$base = dirname(__DIR__);
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowed = [
    'couple' => ['upload' => 'couple_photo.jpg', 'targets' => ['couple_photo.png', 'app-icon.png']],
    'poster_civil' => ['upload' => 'poster_civil.jpg', 'targets' => ['template_mariage_civil.png']],
    'poster_blanche' => ['upload' => 'poster_blanche.jpg', 'targets' => ['template_affiche_blanche.png']],
];

$type = $_POST['type'] ?? '';
if (!isset($allowed[$type])) {
    respond(400, ['error' => 'Type invalide. Utilisez: couple, poster_civil, poster_blanche']);
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    respond(400, ['error' => 'Fichier photo manquant ou erreur upload']);
}

$tmp = $_FILES['photo']['tmp_name'];
$info = @getimagesize($tmp);
if ($info === false) {
    respond(400, ['error' => 'Le fichier doit être une image JPG ou PNG']);
}

$img = loadImage($tmp, $info[2]);
if (!$img) {
    respond(400, ['error' => 'Format image non supporté']);
}

$cfg = $allowed[$type];
$uploadPath = $uploadDir . '/' . $cfg['upload'];
saveJpeg($img, $uploadPath, 92);

foreach ($cfg['targets'] as $target) {
    savePng($img, $assetsDir . '/' . $target);
}

if ($type === 'couple') {
    createAppIcon($img, $assetsDir . '/app-icon.png');
}

$logoUpdated = false;
if ($type === 'poster_civil' || $type === 'poster_blanche') {
    $logoUpdated = extractCoupleLogo($img, $assetsDir, $type === 'poster_blanche');
}

imagedestroy($img);

$msg = match ($type) {
    'couple' => 'Photo couple enregistrée — logo et icône mis à jour',
    'poster_civil' => 'Affiche mariage civil enregistrée — affiche + logo couple mis à jour',
    'poster_blanche' => 'Affiche bénédiction enregistrée — affiche + logo couple mis à jour',
};

respond(200, [
    'success' => true,
    'type' => $type,
    'path' => 'assets/uploads/' . $cfg['upload'],
    'logoUpdated' => $logoUpdated,
    'message' => $msg,
]);

// ── Helpers ──────────────────────────────────────────────────────────

function respond(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function loadImage(string $path, int $type): ?GdImage {
    return match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
        default => null,
    };
}

function saveJpeg(GdImage $img, string $path, int $quality): void {
    imagejpeg($img, $path, $quality);
}

function savePng(GdImage $img, string $path): void {
    $w = imagesx($img);
    $h = imagesy($img);
    $out = imagecreatetruecolor($w, $h);
    imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
    imagecopy($out, $img, 0, 0, 0, 0, $w, $h);
    imagepng($out, $path, 6);
    imagedestroy($out);
}

/** Extrait la photo couple (colonne gauche) pour le logo */
function extractCoupleLogo(GdImage $poster, string $assetsDir, bool $blanche): bool {
    $pw = imagesx($poster);
    $ph = imagesy($poster);

    $sx = $blanche ? (int)($pw * 0.04) : 0;
    $sy = $blanche ? (int)($ph * 0.10) : (int)($ph * 0.07);
    $sw = (int)($pw * 0.42);
    $sh = $blanche ? (int)($ph * 0.62) : (int)($ph * 0.88);

    if ($sw < 50 || $sh < 50) return false;

    $crop = imagecreatetruecolor($sw, $sh);
    imagecopy($crop, $poster, 0, 0, $sx, $sy, $sw, $sh);

    savePng($crop, $assetsDir . '/couple_photo.png');

    $iconSize = 512;
    $icon = imagecreatetruecolor($iconSize, $iconSize);
    $bg = imagecolorallocate($icon, 253, 250, 245);
    imagefill($icon, 0, 0, $bg);
    imagecopyresampled($icon, $crop, 0, 0, 0, 0, $iconSize, $iconSize, $sw, $sh);
    savePng($icon, $assetsDir . '/app-icon.png');
    imagedestroy($icon);
    imagedestroy($crop);
    return true;
}

function createAppIcon(GdImage $src, string $path): void {
    $sw = imagesx($src);
    $sh = imagesy($src);
    $size = min($sw, $sh);
    $sx = (int)(($sw - $size) / 2);
    $sy = (int)(($sh - $size) * 0.15);
    $size = (int)($size * 0.85);

    $icon = imagecreatetruecolor(512, 512);
    $bg = imagecolorallocate($icon, 253, 250, 245);
    imagefill($icon, 0, 0, $bg);
    imagecopyresampled($icon, $src, 0, 0, $sx, $sy, 512, 512, $size, $size);
    savePng($icon, $path);
    imagedestroy($icon);
}
