<?php
/**
 * Génère l'affiche guide parents (PNG) — logo + QR + étapes avec flèches
 */
function generateGuideParentsPng(string $outputPath = 'php://output'): bool
{
    if (!extension_loaded('gd')) {
        return false;
    }

    $inscriptionUrl = BASE_URL . '/inscription.php';
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=' . urlencode($inscriptionUrl);
    $qrRaw = @file_get_contents($qrApiUrl);
    if ($qrRaw === false) {
        return false;
    }
    $qrImg = @imagecreatefromstring($qrRaw);
    if (!$qrImg) {
        return false;
    }

    $width = 900;
    $padding = 36;
    $font = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
    $fontBold = __DIR__ . '/../assets/fonts/DejaVuSans-Bold.ttf';
    if (!file_exists($font) || !file_exists($fontBold)) {
        imagedestroy($qrImg);
        return false;
    }

    $white = null;
    $black = null;
    $gray = null;
    $blue = null;
    $orange = null;
    $lightBlue = null;
    $lightOrange = null;
    $green = null;
    $border = null;

    $canvas = imagecreatetruecolor($width, 2400);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    $black = imagecolorallocate($canvas, 25, 25, 25);
    $gray = imagecolorallocate($canvas, 80, 80, 80);
    $blue = imagecolorallocate($canvas, 13, 110, 253);
    $orange = imagecolorallocate($canvas, 180, 90, 0);
    $lightBlue = imagecolorallocate($canvas, 232, 241, 255);
    $lightOrange = imagecolorallocate($canvas, 255, 243, 224);
    $green = imagecolorallocate($canvas, 25, 135, 84);
    $border = imagecolorallocate($canvas, 210, 210, 210);
    imagefill($canvas, 0, 0, $white);

    $y = $padding;

    $drawCentered = function (string $text, int $size, $color, bool $bold = false) use (&$y, $canvas, $width, $font, $fontBold): void {
        $fontFile = $bold ? $fontBold : $font;
        $box = imagettfbbox($size, 0, $fontFile, $text);
        $textW = abs($box[2] - $box[0]);
        $x = (int) (($width - $textW) / 2);
        imagettftext($canvas, $size, 0, $x, $y + $size, $color, $fontFile, $text);
        $y += $size + 12;
    };

    $drawWrapped = function (string $text, int $size, $color, int $x, int $startY, int $maxWidth = 780) use ($font, $canvas): int {
        $words = explode(' ', $text);
        $line = '';
        $lineH = $size + 8;
        $lines = [];

        foreach ($words as $word) {
            $test = $line === '' ? $word : $line . ' ' . $word;
            $box = imagettfbbox($size, 0, $font, $test);
            if (abs($box[2] - $box[0]) > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $test;
            }
        }
        if ($line !== '') {
            $lines[] = $line;
        }

        foreach ($lines as $i => $ln) {
            imagettftext($canvas, $size, 0, $x, $startY + $size + ($i * $lineH), $color, $font, $ln);
        }

        return $startY + count($lines) * $lineH + 4;
    };

    $drawArrowDown = function (int $cx, int $cy, int $len = 36) use ($canvas, $blue): void {
        imageline($canvas, $cx, $cy, $cx, $cy + $len - 10, $blue);
        imagefilledpolygon($canvas, [
            $cx, $cy + $len,
            $cx - 10, $cy + $len - 14,
            $cx + 10, $cy + $len - 14,
        ], 3, $blue);
    };

    $drawStepBox = function (int $num, string $title, string $detail, int $boxY) use ($canvas, $width, $blue, $lightBlue, $black, $gray, $font, $fontBold, $drawWrapped, $drawArrowDown): int {
        $boxX = 50;
        $boxW = $width - 100;
        $contentTop = $boxY + 16;
        $boxH = 96;

        imagefilledrectangle($canvas, $boxX, $boxY, $boxX + $boxW, $boxY + $boxH, $lightBlue);
        imagerectangle($canvas, $boxX, $boxY, $boxX + $boxW, $boxY + $boxH, $blue);

        imagefilledellipse($canvas, $boxX + 36, $boxY + (int) ($boxH / 2), 44, 44, $blue);
        $numBox = imagettfbbox(16, 0, $fontBold, (string) $num);
        $numW = abs($numBox[2] - $numBox[0]);
        imagettftext($canvas, 16, 0, $boxX + 36 - (int) ($numW / 2), $boxY + (int) ($boxH / 2) + 6, imagecolorallocate($canvas, 255, 255, 255), $fontBold, (string) $num);

        imagettftext($canvas, 13, 0, $boxX + 72, $contentTop + 14, $black, $fontBold, $title);
        $drawWrapped($detail, 11, $gray, $boxX + 72, $contentTop + 22, $boxW - 90);

        $drawArrowDown((int) ($width / 2), $boxY + $boxH + 4, 32);

        return $boxY + $boxH + 40;
    };

    // Logo
    if (schoolLogoExists()) {
        $logoPath = __DIR__ . '/../assets/images/logo.jpg';
        $logoSrc = @imagecreatefromjpeg($logoPath);
        if ($logoSrc) {
            $logoMaxH = 100;
            $logoMaxW = 130;
            $srcW = imagesx($logoSrc);
            $srcH = imagesy($logoSrc);
            $scale = min($logoMaxW / $srcW, $logoMaxH / $srcH, 1);
            $dstW = (int) round($srcW * $scale);
            $dstH = (int) round($srcH * $scale);
            $logoX = (int) (($width - $dstW) / 2);
            imagecopyresampled($canvas, $logoSrc, $logoX, $y, 0, 0, $dstW, $dstH, $srcW, $srcH);
            imagedestroy($logoSrc);
            $y += $dstH + 16;
        }
    }

    $drawCentered(getSetting('school_name', 'C.S LES SUPER GENIES'), 20, $black, true);
    $drawCentered('GUIDE — INSCRIPTION TRANSPORT SCOLAIRE', 15, $blue, true);
    $y += 6;

    // Bandeau important
    $bannerY = $y;
    $bannerH = 72;
    imagefilledrectangle($canvas, 50, $bannerY, $width - 50, $bannerY + $bannerH, $lightOrange);
    imagerectangle($canvas, 50, $bannerY, $width - 50, $bannerY + $bannerH, $orange);
    imagettftext($canvas, 13, 0, 70, $bannerY + 30, $orange, $fontBold, 'IMPORTANT — Pour les familles qui paient DEJA les frais de bus');
    imagettftext($canvas, 11, 0, 70, $bannerY + 54, $gray, $font, 'Ce formulaire sert a enregistrer votre enfant au transport chaque mois.');
    $y = $bannerY + $bannerH + 28;

    // Méthode 1
    imagettftext($canvas, 14, 0, 50, $y + 14, $black, $fontBold, 'METHODE 1 — Scanner le QR Code');
    $y += 28;

    $qrSize = 220;
    $qrX = (int) (($width - $qrSize) / 2);
    imagefilledrectangle($canvas, $qrX - 8, $y - 8, $qrX + $qrSize + 8, $y + $qrSize + 8, $lightBlue);
    imagecopyresampled($canvas, $qrImg, $qrX, $y, 0, 0, $qrSize, $qrSize, imagesx($qrImg), imagesy($qrImg));
    imagedestroy($qrImg);
    $y += $qrSize + 12;

    $drawCentered('Ouvrir l\'appareil photo  →  Scanner  →  Appuyer sur le lien', 11, $gray);
    $drawArrowDown((int) ($width / 2), $y, 40);
    $y += 48;

    // Méthode 2
    imagettftext($canvas, 14, 0, 50, $y + 14, $black, $fontBold, 'METHODE 2 — Via Google ou Chrome (sans QR)');
    $y += 30;
    $urlShort = preg_replace('#^https?://#', '', $inscriptionUrl);
    imagefilledrectangle($canvas, 120, $y, $width - 120, $y + 42, $lightBlue);
    imagerectangle($canvas, 120, $y, $width - 120, $y + 42, $blue);
    $box = imagettfbbox(13, 0, $fontBold, $urlShort);
    $urlX = (int) (($width - abs($box[2] - $box[0])) / 2);
    imagettftext($canvas, 13, 0, $urlX, $y + 28, $blue, $fontBold, $urlShort);
    $y += 52;
    $drawCentered('Taper cette adresse dans Google Chrome sur votre telephone', 11, $gray);
    $drawArrowDown((int) ($width / 2), $y, 40);
    $y += 52;

    imagettftext($canvas, 15, 0, 50, $y + 15, $black, $fontBold, 'REMPLIR LE FORMULAIRE (5 etapes)');
    $y += 34;

    $y = $drawStepBox(1, 'Choisir la SECTION puis la CLASSE', 'Maternelle, Primaire, Secondaire ou Options (1ere a 4eme filiere)', $y);
    $y = $drawStepBox(2, 'Nom complet de l\'eleve + Telephones des parents', 'Ecrire le nom, le telephone principal et le 2e telephone (optionnel)', $y);
    $y = $drawStepBox(3, 'Adresse + Votre arret de bus', 'Adresse de prise en charge. Ecrire votre arret de bus (ex: Golf Maisha, Kenya...)', $y);
    $y = $drawStepBox(4, 'Choisir le MOIS et le MONTANT du bus', 'Selectionner le mois a payer et le tarif (15, 20, 25 ou 30 USD)', $y);
    $y = $drawStepBox(5, 'Cliquer sur ENREGISTRER', 'Attendre le message de confirmation. Conserver votre numero de dossier.', $y);

    // Final
    imagefilledrectangle($canvas, 50, $y, $width - 50, $y + 56, imagecolorallocate($canvas, 220, 248, 232));
    imagerectangle($canvas, 50, $y, $width - 50, $y + 56, $green);
    imagettftext($canvas, 13, 0, 70, $y + 36, $green, $fontBold, 'C\'est fini ! L\'ecole recoit vos informations automatiquement.');
    $y += 72;

    $drawCentered('C.S LES SUPER GENIES — Lubumbashi', 10, $gray);
    $drawCentered('Questions : ' . getSetting('school_phone', '+243815454401'), 10, $gray);

    // Recadrer hauteur réelle
    $finalH = min($y + $padding, 2400);
    $final = imagecreatetruecolor($width, $finalH);
    imagecopy($final, $canvas, 0, 0, 0, 0, $width, $finalH);
    imagedestroy($canvas);

    imagerectangle($final, 0, 0, $width - 1, $finalH - 1, $border);
    $ok = imagepng($final, $outputPath);
    imagedestroy($final);

    return $ok;
}
