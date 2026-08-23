<?php
/**
 * Génération invitation PNG avec QR code (PHP + GD)
 * GET: style, guest, table, seats, date, time, venue
 */
header('Access-Control-Allow-Origin: *');

$base = dirname(__DIR__);
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';

function loadImage(string $path) {
    if (!file_exists($path)) return null;
    $info = @getimagesize($path);
    if (!$info) return null;
    return match ($info[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_PNG => imagecreatefrompng($path),
        default => null,
    };
}

function pickFile(string $upload, string $fallback): string {
    return file_exists($upload) ? $upload : $fallback;
}

function fetchQrImage(string $data, int $size): ?GdImage {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode($data);
    $ctx = stream_context_create(['http' => ['timeout' => 10]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    return @imagecreatefromstring($raw) ?: null;
}

function drawText(GdImage $img, int $size, int $x, int $y, string $text, int $color, bool $bold = false): void {
    $font = '/usr/share/fonts/truetype/dejavu/DejaVuSerif' . ($bold ? '-Bold' : '') . '.ttf';
    if (!file_exists($font)) {
        imagestring($img, $bold ? 5 : 4, $x, $y, $text, $color);
        return;
    }
    imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
}

$style = $_GET['style'] ?? 'mariage-civil';
$guest = trim($_GET['guest'] ?? 'Invité');
$table = trim($_GET['table'] ?? '—');
$seats = (int)($_GET['seats'] ?? 1);
$date = trim($_GET['date'] ?? 'Vendredi, le 11 Septembre 2026');
$time = trim($_GET['time'] ?? '11h00');
$venue = trim($_GET['venue'] ?? 'Commune de Kipushi, Ville de KIPUSHI');

$W = 1200;
$H = 1700;

$posterFile = $style === 'affiche-blanche'
    ? pickFile("$uploadDir/poster_blanche.jpg", "$assetsDir/template_affiche_blanche.png")
    : pickFile("$uploadDir/poster_civil.jpg", "$assetsDir/template_mariage_civil.png");

$coupleFile = pickFile("$uploadDir/couple_photo.jpg", "$assetsDir/couple_photo.png");

$poster = loadImage($posterFile);
$couple = loadImage($coupleFile);

$canvas = imagecreatetruecolor($W, $H);
$white = imagecolorallocate($canvas, 255, 255, 255);
imagefill($canvas, 0, 0, $white);

if ($poster) {
    imagecopyresampled($canvas, $poster, 0, 0, 0, 0, $W, $H, imagesx($poster), imagesy($poster));
    imagedestroy($poster);
}

if ($couple) {
    $cw = $style === 'affiche-blanche' ? 480 : 200;
    $ch = $style === 'affiche-blanche' ? 620 : 260;
    $cx = $style === 'affiche-blanche' ? 50 : 40;
    $cy = $style === 'affiche-blanche' ? 180 : 40;
    imagecopyresampled($canvas, $couple, $cx, $cy, 0, 0, $cw, $ch, imagesx($couple), imagesy($couple));
    imagedestroy($couple);
}

$black = imagecolorallocate($canvas, 26, 26, 26);
$purple = imagecolorallocate($canvas, 107, 45, 130);
$blue = imagecolorallocate($canvas, 0, 35, 102);
$accent = $style === 'affiche-blanche' ? $blue : $purple;

drawText($canvas, 28, 490, 320, $guest, $black, true);
drawText($canvas, 22, 60, 250, "Table $table", $accent, true);
drawText($canvas, 18, 490, 920, "Date : $date", $black);
drawText($canvas, 18, 490, 980, "Heure : $time", $black);
drawText($canvas, 16, 490, 1040, "Lieu : $venue", $black);
drawText($canvas, 14, 900, 1580, "$seats place(s) • Table $table", $accent, true);

$qrData = "INVITE|nom:$guest|table:$table|date:$date|places:$seats";
$qrSize = 200;
$qr = fetchQrImage($qrData, $qrSize);
if ($qr) {
    $qx = 50;
    $qy = $H - 280;
    $pad = 12;
    $box = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, $qx - $pad, $qy - $pad, $qx + $qrSize + $pad, $qy + $qrSize + $pad + 30, $box);
    imagecopyresampled($canvas, $qr, $qx, $qy, 0, 0, $qrSize, $qrSize, imagesx($qr), imagesy($qr));
    imagedestroy($qr);
    drawText($canvas, 14, $qx, $qy + $qrSize + 28, 'Scannez pour valider', $accent, true);
}

header('Content-Type: image/png');
header('Cache-Control: no-cache');
imagepng($canvas);
imagedestroy($canvas);
