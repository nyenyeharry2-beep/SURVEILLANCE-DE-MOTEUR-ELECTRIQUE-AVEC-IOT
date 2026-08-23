<?php
/**
 * Génération invitation PNG — affiche officielle + nom invité + photo + QR
 * Positions alignées sur l'app Android (HtmlInvitationRenderer)
 */
header('Access-Control-Allow-Origin: *');

$base = dirname(__DIR__);
$uploadDir = $base . '/assets/uploads';
$assetsDir = $base . '/assets';

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

function fontPath(bool $bold = false): ?string {
    $path = '/usr/share/fonts/truetype/dejavu/DejaVuSerif' . ($bold ? '-Bold' : '') . '.ttf';
    return file_exists($path) ? $path : null;
}

function drawText(GdImage $img, int $size, int $x, int $y, string $text, int $color, bool $bold = false): void {
    $font = fontPath($bold);
    if ($font) {
        imagettftext($img, $size, 0, $x, $y, $color, $font, $text);
        return;
    }
    imagestring($img, $bold ? 5 : 4, $x, $y - 14, $text, $color);
}

function drawTextCentered(GdImage $img, int $size, int $cx, int $y, string $text, int $color, bool $bold = false): void {
    $font = fontPath($bold);
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

function pasteCover(GdImage $canvas, GdImage $src, int $dx, int $dy, int $dw, int $dh): void {
    $sw = imagesx($src);
    $sh = imagesy($src);
    $scale = max($dw / $sw, $dh / $sh);
    $cw = (int)($sw * $scale);
    $ch = (int)($sh * $scale);
    $sx = (int)(($cw - $dw) / 2 / $scale);
    $sy = (int)(($ch - $dh) / 2 / $scale);
    imagecopyresampled($canvas, $src, $dx, $dy, $sx, $sy, $dw, $dh, (int)($dw / $scale), (int)($dh / $scale));
}

$style = $_GET['style'] ?? 'mariage-civil';
$guest = trim($_GET['guest'] ?? 'Invité');
$table = trim($_GET['table'] ?? '—');
$seats = (int)($_GET['seats'] ?? 1);

$W = 1200;
$H = 1700;

$isBlanche = ($style === 'affiche-blanche');

$posterFile = $isBlanche
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
    if ($isBlanche) {
        pasteCover($canvas, $couple, 50, 180, 480, 1100);
    } else {
        pasteCover($canvas, $couple, 0, 120, 480, 1450);
    }
    imagedestroy($couple);
}

$black = imagecolorallocate($canvas, 26, 26, 26);
$purple = imagecolorallocate($canvas, 107, 45, 130);
$blue = imagecolorallocate($canvas, 0, 35, 102);
$accent = $isBlanche ? $blue : $purple;

if ($isBlanche) {
    coverRect($canvas, 620, 195, 520, 55, $white);
    drawText($canvas, 30, 620, 240, $guest, $black, true);

    coverRect($canvas, 50, 115, 480, 50, $white);
    drawTextCentered($canvas, 28, 290, 155, "Table $table", $black, true);

    $qrX = 50;
    $qrY = $H - 290;
} else {
    coverRect($canvas, 490, 285, 660, 55, $white);
    drawText($canvas, 34, 490, 330, $guest, $black, true);

    coverRect($canvas, 60, 205, 400, 45, $white);
    drawTextCentered($canvas, 28, 260, 240, "Table $table", $accent, true);

    coverRect($canvas, 880, 1540, 280, 35, $white);
    drawText($canvas, 18, 900, 1565, "$seats place(s) • Table $table", $accent, true);

    $qrX = 50;
    $qrY = $H - 290;
}

$qrData = "INVITE|nom:$guest|table:$table|places:$seats";
$qrSize = 200;
$qr = fetchQrImage($qrData, $qrSize);
if ($qr) {
    $pad = 12;
    $border = $accent;
    $boxW = $qrSize + $pad * 2;
    $boxH = $qrSize + $pad * 2 + 28;
    coverRect($canvas, $qrX - 4, $qrY - 4, $boxW + 8, $boxH + 8, $white);
    imagefilledrectangle($canvas, $qrX - $pad, $qrY - $pad, $qrX + $qrSize + $pad, $qrY + $qrSize + $pad, $white);
    imagerectangle($canvas, $qrX - $pad, $qrY - $pad, $qrX + $qrSize + $pad, $qrY + $qrSize + $pad, $border);
    imagecopyresampled($canvas, $qr, $qrX, $qrY, 0, 0, $qrSize, $qrSize, imagesx($qr), imagesy($qr));
    imagedestroy($qr);
    drawTextCentered($canvas, 14, $qrX + (int)($qrSize / 2), $qrY + $qrSize + 26, 'Scannez pour valider', $accent, true);
}

header('Content-Type: image/png');
header('Cache-Control: no-cache');
imagepng($canvas);
imagedestroy($canvas);
