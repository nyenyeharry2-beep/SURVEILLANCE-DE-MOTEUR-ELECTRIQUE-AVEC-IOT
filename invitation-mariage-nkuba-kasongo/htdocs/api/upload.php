<?php
/**
 * Upload affiche + logo — compatible InfinityFree (écriture dans assets/uploads/)
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
    @mkdir($uploadDir, 0755, true);
}

$allowed = [
    'couple' => 'couple_photo.jpg',
    'poster_civil' => 'poster_civil.jpg',
    'poster_blanche' => 'poster_blanche.jpg',
];

$type = $_POST['type'] ?? '';
if (!isset($allowed[$type])) {
    respond(400, ['error' => 'Type invalide']);
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $code = $_FILES['photo']['error'] ?? -1;
    respond(400, ['error' => 'Erreur upload fichier (code ' . $code . ')']);
}

$tmp = $_FILES['photo']['tmp_name'];
$info = @getimagesize($tmp);
if ($info === false) {
    respond(400, ['error' => 'Image JPG ou PNG requise']);
}

$img = loadImage($tmp, $info[2]);
if (!$img) {
    respond(400, ['error' => 'Format non supporté']);
}

$filename = $allowed[$type];
$uploadPath = $uploadDir . '/' . $filename;

if (!saveJpeg($img, $uploadPath, 90)) {
    imagedestroy($img);
    respond(500, ['error' => 'Impossible d\'écrire dans assets/uploads/ — vérifiez permissions 755']);
}

$logoUpdated = false;
if ($type === 'couple') {
    @copy($uploadPath, $uploadDir . '/couple_logo.jpg');
    $logoUpdated = true;
} elseif ($type === 'poster_civil' || $type === 'poster_blanche') {
    $logoUpdated = extractCoupleLogo($img, $uploadDir, $type === 'poster_blanche');
    @copy($uploadPath, $uploadDir . '/couple_logo.jpg');
}

/* Copie optionnelle vers assets/ (peut échouer sur hébergement gratuit) */
tryCopyAssets($img, $type, $assetsDir);

imagedestroy($img);

$webPath = 'assets/uploads/' . $filename;
respond(200, [
    'success' => true,
    'type' => $type,
    'path' => $webPath,
    'logoUpdated' => $logoUpdated,
    'couple' => file_exists($uploadDir . '/couple_logo.jpg') ? 'assets/uploads/couple_logo.jpg' : $webPath,
    'message' => match ($type) {
        'couple' => 'Logo couple enregistré',
        'poster_civil' => 'Affiche civil enregistrée — logo extrait automatiquement',
        'poster_blanche' => 'Affiche bénédiction enregistrée — logo extrait',
    },
]);

function respond(int $code, array $data): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
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

function saveJpeg(GdImage $img, string $path, int $quality): bool {
    return @imagejpeg($img, $path, $quality);
}

function tryCopyAssets(GdImage $img, string $type, string $assetsDir): void {
    if ($type === 'couple') {
        @imagejpeg($img, $assetsDir . '/couple_photo.jpg', 90);
        @imagejpeg($img, $assetsDir . '/app-icon.jpg', 90);
    }
}

function extractCoupleLogo(GdImage $poster, string $dir, bool $blanche): bool {
    $pw = imagesx($poster);
    $ph = imagesy($poster);
    $sx = $blanche ? (int)($pw * 0.04) : 0;
    $sy = $blanche ? (int)($ph * 0.10) : (int)($ph * 0.05);
    $sw = (int)($pw * 0.42);
    $sh = $blanche ? (int)($ph * 0.65) : (int)($ph * 0.90);
    if ($sw < 50 || $sh < 50) return false;

    $crop = imagecreatetruecolor($sw, $sh);
    imagecopy($crop, $poster, 0, 0, $sx, $sy, $sw, $sh);
    saveJpeg($crop, $dir . '/couple_logo.jpg', 90);
    saveJpeg($crop, $dir . '/couple_photo.jpg', 90);
    imagedestroy($crop);
    return true;
}
