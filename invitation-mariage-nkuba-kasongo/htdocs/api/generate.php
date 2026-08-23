<?php
/**
 * Génération invitation PNG — affiche + nom invité + table + QR
 */
header('Access-Control-Allow-Origin: *');

$base = dirname(__DIR__);
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';
$fontsDir = $assetsDir . '/fonts';

function loadImage(string $path): ?GdImage {
    if (!file_exists($path)) return null;
    $info = @getimagesize($path);
    if (!$info) return null;
    return match ($info[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($path),
        IMAGETYPE_PNG => imagecreatefrompng($path),
        default => null,
    };
}

function pickPoster(string $upload, string $fallback): string {
    return file_exists($upload) ? $upload : $fallback;
}

function fetchQrImage(string $data, int $size): ?GdImage {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&data=' . rawurlencode($data);
    $ctx = stream_context_create(['http' => ['timeout' => 8], 'ssl' => ['verify_peer' => false]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) return null;
    return @imagecreatefromstring($raw) ?: null;
}

function fontPath(bool $bold, string $fontsDir): ?string {
    $local = $fontsDir . ($bold ? '/PlayfairDisplay.ttf' : '/PlayfairDisplay-Regular.ttf');
    if (file_exists($local)) return $local;
    $sys = '/usr/share/fonts/truetype/dejavu/DejaVuSerif' . ($bold ? '-Bold' : '') . '.ttf';
    return file_exists($sys) ? $sys : null;
}

function drawText(GdImage $img, int $size, int $x, int $y, string $text, int $color, bool $bold, string $fontsDir): void {
    $font = fontPath($bold, $fontsDir);
    if ($font) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        return;
    }
    imagestring($img, $bold ? 5 : 4, $x, $y - 14, $text, $color);
}

function drawTextCentered(GdImage $img, int $size, int $cx, int $y, string $text, int $color, bool $bold, string $fontsDir): void {
    $font = fontPath($bold, $fontsDir);
    if ($font) {
        $box = imagettfbbox($size, 0, $font, $text);
        $w = abs($box[2] - $box[0]);
        imagettftext($img, $size, 0, (int)($cx - $w / 2), $y, $color, $font, $text);
        return;
    }
    $w = imagefontwidth($bold ? 5 : 4) * strlen($text);
    imagestring($img, $bold ? 5 : 4, (int)($cx - $w / 2), $y - 14, $text, $color);
}

function coverRect(GdImage $img, int $x, int $y, int $w, int $h, int $color): void {
    imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $color);
}

$style = $_GET['style'] ?? 'mariage-civil';
$guest = trim($_GET['guest'] ?? 'Invité');
$table = trim($_GET['table'] ?? '—');
$seats = (int)($_GET['seats'] ?? 1);

$W = 1200;
$H = 1700;
$isBlanche = ($style === 'affiche-blanche');

$posterFile = $isBlanche
    ? pickPoster("$uploadDir/poster_blanche.jpg", "$assetsDir/template_affiche_blanche.png")
    : pickPoster("$uploadDir/poster_civil.jpg", "$assetsDir/template_mariage_civil.png");

$poster = loadImage($posterFile);
$canvas = imagecreatetruecolor($W, $H);
$white = imagecolorallocate($canvas, 255, 255, 255);
imagefill($canvas, 0, 0, $white);

if ($poster) {
    imagecopyresampled($canvas, $poster, 0, 0, 0, 0, $W, $H, imagesx($poster), imagesy($poster));
    imagedestroy($poster);
}

$black = imagecolorallocate($canvas, 26, 26, 26);
$purple = imagecolorallocate($canvas, 107, 45, 130);
$blue = imagecolorallocate($canvas, 0, 35, 102);
$accent = $isBlanche ? $blue : $purple;

if ($isBlanche) {
    drawText($canvas, 24, 625, 228, $guest, $black, true, $fontsDir);
    drawTextCentered($canvas, 24, 290, 148, "Table $table", $black, true, $fontsDir);
    $qrX = 45;
    $qrY = $H - 285;
} else {
    drawText($canvas, 26, 520, 318, $guest, $black, true, $fontsDir);
    drawTextCentered($canvas, 24, 255, 232, "Table $table", $accent, true, $fontsDir);
    drawText($canvas, 16, 890, 1555, "$seats place(s) • Table $table", $accent, true, $fontsDir);
    $qrX = 45;
    $qrY = $H - 285;
}

$qrData = "INVITE|nom:$guest|table:$table|places:$seats";
$qrSize = 200;
$qr = fetchQrImage($qrData, $qrSize);
if ($qr) {
    $pad = 10;
    coverRect($canvas, $qrX - 6, $qrY - 6, $qrSize + $pad * 2 + 12, $qrSize + $pad * 2 + 34, $white);
    imagefilledrectangle($canvas, $qrX - $pad, $qrY - $pad, $qrX + $qrSize + $pad, $qrY + $qrSize + $pad, $white);
    imagerectangle($canvas, $qrX - $pad, $qrY - $pad, $qrX + $qrSize + $pad, $qrY + $qrSize + $pad, $accent);
    imagecopyresampled($canvas, $qr, $qrX, $qrY, 0, 0, $qrSize, $qrSize, imagesx($qr), imagesy($qr));
    imagedestroy($qr);
    drawTextCentered($canvas, 13, $qrX + (int)($qrSize / 2), $qrY + $qrSize + 24, 'Scannez pour valider', $accent, true, $fontsDir);
}

header('Content-Type: image/png');
header('Cache-Control: no-cache');
imagepng($canvas);
imagedestroy($canvas);
