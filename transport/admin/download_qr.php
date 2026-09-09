<?php
/**
 * Téléchargement du QR Code avec logo et texte sur une seule image PNG
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

if (!extension_loaded('gd')) {
    http_response_code(500);
    exit('Extension GD requise pour générer l\'image.');
}

$inscriptionUrl = BASE_URL . '/inscription.php';
$qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($inscriptionUrl);

$qrRaw = @file_get_contents($qrApiUrl);
if ($qrRaw === false) {
    http_response_code(502);
    exit('Impossible de générer le QR Code.');
}

$qrImg = @imagecreatefromstring($qrRaw);
if (!$qrImg) {
    http_response_code(502);
    exit('QR Code invalide.');
}

$width = 420;
$padding = 24;
$logoMaxH = 90;
$logoMaxW = 120;
$qrSize = 300;
$lineH = 18;

$foundation = getSetting('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I');
$project = getSetting('school_project', 'PROJET EDUCATIF');
$schoolName = getSetting('school_name', 'C.S LES SUPER GENIES');
$subtitle = 'Inscription au transport scolaire';

$textLines = 5;
$height = $padding + $logoMaxH + 12 + ($lineH * $textLines) + 16 + $qrSize + 36 + $padding;

$canvas = imagecreatetruecolor($width, $height);
$white = imagecolorallocate($canvas, 255, 255, 255);
$black = imagecolorallocate($canvas, 30, 30, 30);
$gray = imagecolorallocate($canvas, 90, 90, 90);
$border = imagecolorallocate($canvas, 220, 220, 220);
imagefill($canvas, 0, 0, $white);
imagerectangle($canvas, 0, 0, $width - 1, $height - 1, $border);

$y = $padding;

if (schoolLogoExists()) {
    $logoPath = __DIR__ . '/../assets/images/logo.jpg';
    $logoSrc = @imagecreatefromjpeg($logoPath);
    if ($logoSrc) {
        $srcW = imagesx($logoSrc);
        $srcH = imagesy($logoSrc);
        $scale = min($logoMaxW / $srcW, $logoMaxH / $srcH, 1);
        $dstW = (int) round($srcW * $scale);
        $dstH = (int) round($srcH * $scale);
        $logoX = (int) (($width - $dstW) / 2);
        imagecopyresampled($canvas, $logoSrc, $logoX, $y, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($logoSrc);
        $y += $dstH + 12;
    }
} else {
    $y += 8;
}

$font = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
$useTtf = file_exists($font);

$drawCentered = function (string $text, int $size, $color, bool $bold = false) use (&$y, $canvas, $width, $font, $useTtf, $lineH): void {
    $text = trim($text);
    if ($text === '') {
        return;
    }
    if ($useTtf) {
        $fontFile = $bold ? str_replace('DejaVuSans.ttf', 'DejaVuSans-Bold.ttf', $font) : $font;
        if (!file_exists($fontFile)) {
            $fontFile = $font;
        }
        $box = imagettfbbox($size, 0, $fontFile, $text);
        $textW = abs($box[2] - $box[0]);
        $x = (int) (($width - $textW) / 2);
        imagettftext($canvas, $size, 0, $x, $y + $size, $color, $fontFile, $text);
        $y += $size + 8;
        return;
    }
    $fontBuiltin = $bold ? 5 : 3;
    $textW = imagefontwidth($fontBuiltin) * strlen($text);
    $x = (int) (($width - $textW) / 2);
    imagestring($canvas, $fontBuiltin, max(4, $x), $y, $text, $color);
    $y += $lineH;
};

$drawCentered($foundation, 11, $black, true);
$drawCentered($project, 10, $gray);
$drawCentered($schoolName, 14, $black, true);
$drawCentered($subtitle, 10, $gray);
$y += 4;

$qrX = (int) (($width - $qrSize) / 2);
imagecopyresampled($canvas, $qrImg, $qrX, $y, 0, 0, $qrSize, $qrSize, imagesx($qrImg), imagesy($qrImg));
imagedestroy($qrImg);
$y += $qrSize + 14;

$urlShort = preg_replace('#^https?://#', '', $inscriptionUrl);
$drawCentered($urlShort, 9, $gray);

$preview = isset($_GET['preview']);
header('Content-Type: image/png');
header('Cache-Control: no-store, no-cache, must-revalidate');
if (!$preview) {
    header('Content-Disposition: attachment; filename="qr-inscription-super-genies.png"');
}
imagepng($canvas);
imagedestroy($canvas);
logActivity('download_qr', 'qr', null, 'Téléchargement QR avec logo');
exit;
