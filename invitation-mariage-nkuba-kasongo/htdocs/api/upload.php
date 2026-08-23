<?php
/**
 * Upload des photos utilisateur — couple (logo) + affiches
 */
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uploadDir = dirname(__DIR__) . '/assets/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowed = [
    'couple' => 'couple_photo.jpg',
    'poster_civil' => 'poster_civil.jpg',
    'poster_blanche' => 'poster_blanche.jpg',
];

$type = $_POST['type'] ?? '';
if (!isset($allowed[$type])) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Type invalide. Utilisez: couple, poster_civil, poster_blanche']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Fichier photo manquant ou erreur upload']);
    exit;
}

$tmp = $_FILES['photo']['tmp_name'];
$info = @getimagesize($tmp);
if ($info === false) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Le fichier doit être une image JPG ou PNG']);
    exit;
}

$dest = $uploadDir . '/' . $allowed[$type];
$img = null;
switch ($info[2]) {
    case IMAGETYPE_JPEG: $img = imagecreatefromjpeg($tmp); break;
    case IMAGETYPE_PNG: $img = imagecreatefrompng($tmp); break;
    case IMAGETYPE_WEBP:
        if (function_exists('imagecreatefromwebp')) {
            $img = imagecreatefromwebp($tmp);
        }
        break;
}
if (!$img) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Format image non supporté']);
    exit;
}

imagejpeg($img, $dest, 92);
imagedestroy($img);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'type' => $type,
    'path' => 'assets/uploads/' . $allowed[$type],
    'message' => 'Photo enregistrée — elle sera utilisée comme logo et sur les invitations',
]);
