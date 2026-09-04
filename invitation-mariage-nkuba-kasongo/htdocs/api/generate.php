<?php
/**
 * Génération invitation PNG — affiche officielle + photo couple + nom + table + QR
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

function pickCouplePath(string $uploadDir, string $assetsDir): ?string {
    foreach ([
        "$uploadDir/couple_photo.jpg",
        "$uploadDir/couple_logo.jpg",
        "$assetsDir/couple_photo.jpg",
        "$assetsDir/couple_photo.png",
    ] as $path) {
        if (file_exists($path)) return $path;
    }
    return null;
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

function textWidth(string $text, int $size, ?string $font): int {
    if (!$font) return strlen($text) * 10;
    $box = imagettfbbox($size, 0, $font, $text);
    return abs($box[2] - $box[0]);
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
        $w = textWidth($text, $size, $font);
        imagettftext($img, $size, 0, (int)($cx - $w / 2), $y, $color, $font, $text);
        return;
    }
    $w = imagefontwidth($bold ? 5 : 4) * strlen($text);
    imagestring($img, $bold ? 5 : 4, (int)($cx - $w / 2), $y - 14, $text, $color);
}

function drawScriptCentered(GdImage $img, int $size, int $cx, int $y, string $text, int $color, string $fontsDir): void {
    $font = $fontsDir . '/GreatVibes.ttf';
    if (!file_exists($font)) {
        drawTextCentered($img, $size, $cx, $y, $text, $color, false, $fontsDir);
        return;
    }
    $box = imagettfbbox($size, 0, $font, $text);
    $w = abs($box[2] - $box[0]);
    imagettftext($img, $size, 0, (int)($cx - $w / 2), $y, $color, $font, $text);
}

function coverRect(GdImage $img, int $x, int $y, int $w, int $h, int $color): void {
    imagefilledrectangle($img, $x, $y, $x + $w, $y + $h, $color);
}

function pasteCoupleLeft(GdImage $canvas, string $couplePath, int $panelW = 480): void {
    $couple = loadImage($couplePath);
    if (!$couple) return;

    $srcW = imagesx($couple);
    $srcH = imagesy($couple);
    $dstH = imagesy($canvas);
    $scale = max($panelW / $srcW, $dstH / $srcH);
    $newW = (int)ceil($srcW * $scale);
    $newH = (int)ceil($srcH * $scale);

    $tmp = imagecreatetruecolor($newW, $newH);
    imagecopyresampled($tmp, $couple, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);
    $cropX = max(0, (int)(($newW - $panelW) / 2));
    $cropY = max(0, (int)(($newH - $dstH) / 2));
    imagecopy($canvas, $tmp, 0, 0, $cropX, $cropY, $panelW, $dstH);
    imagedestroy($tmp);
    imagedestroy($couple);
}

function isBlankTable(string $table): bool {
    $t = trim($table);
    return $t === '' || $t === '—' || $t === '-' || $t === '...';
}

$style = $_GET['style'] ?? 'mariage-civil';
$guest = trim($_GET['guest'] ?? 'Invité');
$table = trim($_GET['table'] ?? '—');
$seats = max(1, (int)($_GET['seats'] ?? 1));
$qrData = trim($_GET['qr'] ?? '');
if ($qrData === '') {
    $qrData = "INVITE|nom:$guest|table:$table|places:$seats";
}

$W = 1200;
$H = 1700;
$isBlanche = ($style === 'affiche-blanche');

$posterFile = $isBlanche
    ? pickPoster("$uploadDir/poster_blanche.jpg", "$assetsDir/template_affiche_blanche.png")
    : pickPoster("$uploadDir/poster_civil.jpg", "$assetsDir/template_mariage_civil.png");

$canvas = imagecreatetruecolor($W, $H);
$white = imagecolorallocate($canvas, 255, 255, 255);
imagefill($canvas, 0, 0, $white);

$poster = loadImage($posterFile);
if ($poster) {
    imagecopy($canvas, $poster, 0, 0, 0, 0, $W, $H);
    imagedestroy($poster);
}

$couplePath = pickCouplePath($uploadDir, $assetsDir);
if ($couplePath) {
    pasteCoupleLeft($canvas, $couplePath);
}

$black = imagecolorallocate($canvas, 26, 26, 26);
$dark = imagecolorallocate($canvas, 51, 51, 51);
$purple = imagecolorallocate($canvas, 107, 45, 130);
$blue = imagecolorallocate($canvas, 0, 35, 102);
$gray = imagecolorallocate($canvas, 136, 136, 136);
$pink = imagecolorallocate($canvas, 216, 27, 96);
$accent = $isBlanche ? $blue : $purple;

if ($isBlanche) {
    drawText($canvas, 20, 625, 248, 'Mme, Mlle, M., Couple :', $dark, false, $fontsDir);
    drawText($canvas, 28, 625, 285, $guest, $black, true, $fontsDir);
    if (!isBlankTable($table)) {
        drawTextCentered($canvas, 24, 290, 148, "Table $table", $black, true, $fontsDir);
    }
    $qrX = 45;
    $qrY = $H - 285;
} else {
    // Table sous « Invitation » (panneau droit)
    if (!isBlankTable($table)) {
        $font = fontPath(false, $fontsDir);
        $label = 'Table';
        $labelW = textWidth($label . ' ', 22, $font);
        $tableX = 530;
        drawText($canvas, 22, $tableX, 198, $label, $dark, false, $fontsDir);
        drawText($canvas, 26, $tableX + $labelW, 198, $table, $accent, true, $fontsDir);
        // pointillés sous le numéro
        $valW = textWidth($table, 26, fontPath(true, $fontsDir));
        for ($dx = $tableX + $labelW; $dx < $tableX + $labelW + $valW + 40; $dx += 12) {
            imageline($canvas, $dx, 206, $dx + 7, 206, $gray);
        }
    }

    // Nom invité — masquer la zone pointillée du template puis écrire
    coverRect($canvas, 525, 248, 555, 78, $white);
    drawText($canvas, 20, 530, 278, 'Mme, Mlle, Mr, Couple :', $dark, false, $fontsDir);
    drawText($canvas, 28, 530, 312, $guest, $black, true, $fontsDir);
    for ($dx = 530; $dx < 1080; $dx += 14) {
        imageline($canvas, $dx, 328, $dx + 8, 328, $gray);
    }

    // Réécrire les noms des mariés (photo couple peut les recouvrir)
    coverRect($canvas, 480, 830, 700, 65, $white);
    drawScriptCentered($canvas, 52, 720, 875, 'Moïse NKUBA & Sarah KASONGO', $pink, $fontsDir);

    // Badge places en bas à droite
    $badge = $seats . ' place(s)';
    if (!isBlankTable($table)) {
        $badge .= ' • Table ' . $table;
    }
    drawText($canvas, 17, 890, 1595, $badge, $accent, true, $fontsDir);

    $qrX = 38;
    $qrY = $H - 255;
}

$qrSize = 200;
$qr = fetchQrImage($qrData, $qrSize);
if ($qr) {
    $pad = 8;
    coverRect($canvas, $qrX - $pad - 4, $qrY - $pad - 4, $qrSize + ($pad + 4) * 2, $qrSize + ($pad + 4) * 2 + 28, $white);
    imagefilledrectangle($canvas, $qrX - $pad, $qrY - $pad, $qrX + $qrSize + $pad, $qrY + $qrSize + $pad, $white);
    imagerectangle($canvas, $qrX - $pad, $qrY - $pad, $qrX + $qrSize + $pad, $qrY + $qrSize + $pad, $accent);
    imagecopyresampled($canvas, $qr, $qrX, $qrY, 0, 0, $qrSize, $qrSize, imagesx($qr), imagesy($qr));
    imagedestroy($qr);
    drawTextCentered($canvas, 13, $qrX + (int)($qrSize / 2), $qrY + $qrSize + 22, 'Scannez pour valider', $accent, true, $fontsDir);
}

header('Content-Type: image/png');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
imagepng($canvas);
imagedestroy($canvas);
