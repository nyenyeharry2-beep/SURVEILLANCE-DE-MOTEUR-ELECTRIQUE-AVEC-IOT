<?php

function appName(): string
{
    if (defined('APP_NAME')) {
        return APP_NAME;
    }

    $configPath = __DIR__ . '/../config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }

    return defined('APP_NAME') ? APP_NAME : 'Nouvelle Eve';
}

function appTagline(): string
{
    if (defined('APP_TAGLINE')) {
        return APP_TAGLINE;
    }

    $configPath = __DIR__ . '/../config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }

    return defined('APP_TAGLINE') ? APP_TAGLINE : 'Votre santé, notre priorité';
}

function appLogo(): string
{
    if (defined('APP_LOGO')) {
        return APP_LOGO;
    }

    $configPath = __DIR__ . '/../config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    }

    return defined('APP_LOGO') ? APP_LOGO : 'assets/img/logo.jpg';
}

function appUrl(): string
{
    if (defined('APP_URL')) {
        return APP_URL;
    }

    return '';
}

function appAddress(): string
{
    if (defined('APP_ADDRESS')) {
        return APP_ADDRESS;
    }

    return '';
}

function appPhone(): string
{
    if (defined('APP_PHONE')) {
        return APP_PHONE;
    }

    return '';
}

function nombreEnLettresFr(int $n): string
{
    if ($n === 0) {
        return 'zéro';
    }

    $units = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf', 'dix',
        'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
    $tens = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

    $under100 = static function (int $num) use ($units, $tens): string {
        if ($num < 20) {
            return $units[$num];
        }
        if ($num < 70) {
            $ten = intdiv($num, 10);
            $unit = $num % 10;
            if ($unit === 1 && $ten !== 8) {
                return $tens[$ten] . ' et un';
            }
            return $tens[$ten] . ($unit ? '-' . $units[$unit] : ($ten === 8 ? 's' : ''));
        }
        if ($num < 80) {
            return 'soixante-' . $units[$num - 60];
        }
        if ($num < 100) {
            $rest = $num - 80;
            return 'quatre-vingt' . ($rest ? '-' . $units[$rest] : 's');
        }
        return '';
    };

    $under1000 = static function (int $num) use ($under100): string {
        if ($num < 100) {
            return $under100($num);
        }
        $hundreds = intdiv($num, 100);
        $rest = $num % 100;
        $text = $hundreds === 1 ? 'cent' : $units[$hundreds] . ' cent';
        if ($hundreds > 1 && $rest === 0) {
            $text .= 's';
        }
        return $rest ? $text . ' ' . $under100($rest) : $text;
    };

    if ($n < 1000) {
        return $under1000($n);
    }

    if ($n < 1000000) {
        $thousands = intdiv($n, 1000);
        $rest = $n % 1000;
        $text = $thousands === 1 ? 'mille' : $under1000($thousands) . ' mille';
        return $rest ? $text . ' ' . $under1000($rest) : $text;
    }

    return number_format($n, 0, ',', ' ');
}

function montantEnLettres(float $montant, string $devise): string
{
    $devise = normalizeDevise($devise);

    if ($devise === 'USD') {
        $entier = (int) floor($montant);
        $centimes = (int) round(($montant - $entier) * 100);
        $texte = nombreEnLettresFr($entier) . ' dollar' . ($entier > 1 ? 's' : '') . ' américain' . ($entier > 1 ? 's' : '');
        if ($centimes > 0) {
            $texte .= ' et ' . nombreEnLettresFr($centimes) . ' centime' . ($centimes > 1 ? 's' : '');
        }
        return $texte;
    }

    $entier = (int) round($montant);
    return nombreEnLettresFr($entier) . ' franc' . ($entier > 1 ? 's' : '') . ' congolais';
}

function formatMoneyPlain(float $amount, string $devise = 'CDF'): string
{
    $devise = normalizeDevise($devise);
    if ($devise === 'USD') {
        return number_format($amount, 2, '.', ' ') . ' USD';
    }

    return number_format($amount, 0, ',', ' ') . ' FC';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF invalide.');
    }
}

function getTauxUsdCdf(): float
{
    return defined('TAUX_USD_CDF') ? (float) TAUX_USD_CDF : 2850.0;
}

function normalizeDevise(string $devise): string
{
    return strtoupper($devise) === 'USD' ? 'USD' : 'CDF';
}

function convertirDevise(float $montant, string $de, string $vers): float
{
    $de = normalizeDevise($de);
    $vers = normalizeDevise($vers);

    if ($de === $vers) {
        return $montant;
    }

    $taux = getTauxUsdCdf();

    if ($de === 'USD' && $vers === 'CDF') {
        return $montant * $taux;
    }

    return $montant / $taux;
}

function formatCDF(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FC';
}

function formatUSD(float $amount): string
{
    return '$' . number_format($amount, 2, ',', ' ');
}

function formatMoney(float $amount, string $devise = 'CDF'): string
{
    return normalizeDevise($devise) === 'USD' ? formatUSD($amount) : formatCDF($amount);
}

function formatDualMoney(float $amount, string $devise): string
{
    $devise = normalizeDevise($devise);
    $cdf = convertirDevise($amount, $devise, 'CDF');
    $usd = convertirDevise($amount, $devise, 'USD');

    return formatCDF($cdf) . ' / ' . formatUSD($usd);
}

function sommeVentesDual(PDO $db, string $whereSql = '1=1', array $params = []): array
{
    $taux = getTauxUsdCdf();
    $sql = "
        SELECT
            COALESCE(SUM(CASE WHEN COALESCE(devise, 'CDF') = 'CDF' THEN montant_total ELSE 0 END), 0) AS total_cdf_brut,
            COALESCE(SUM(CASE WHEN COALESCE(devise, 'CDF') = 'USD' THEN montant_total ELSE 0 END), 0) AS total_usd_brut,
            COALESCE(SUM(
                CASE WHEN COALESCE(devise, 'CDF') = 'CDF' THEN montant_total ELSE montant_total * ?
            END), 0) AS total_cdf,
            COALESCE(SUM(
                CASE WHEN COALESCE(devise, 'CDF') = 'USD' THEN montant_total ELSE montant_total / ?
            END), 0) AS total_usd
        FROM ventes
        WHERE {$whereSql} AND COALESCE(annulee, 0) = 0
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge([$taux, $taux], $params));

    return $stmt->fetch() ?: [
        'total_cdf_brut' => 0,
        'total_usd_brut' => 0,
        'total_cdf' => 0,
        'total_usd' => 0,
    ];
}

function formatDate(?string $date): string
{
    if (!$date) {
        return '—';
    }
    return date('d/m/Y', strtotime($date));
}

function isExpired(?string $date): bool
{
    if (!$date) {
        return false;
    }
    return strtotime($date) < strtotime('today');
}

function daysUntilExpiry(?string $date): ?int
{
    if (!$date) {
        return null;
    }
    return (int) floor((strtotime($date) - strtotime('today')) / 86400);
}

function getAlerteExpirationMois(): int
{
    return defined('ALERTE_EXPIRATION_MOIS') ? max(1, (int) ALERTE_EXPIRATION_MOIS) : 5;
}

function isExpiringSoon(?string $date): bool
{
    if (!$date || isExpired($date)) {
        return false;
    }
    $limit = (new DateTime('today'))->modify('+' . getAlerteExpirationMois() . ' months');
    return new DateTime($date) <= $limit;
}

function moisRestantsExpiration(?string $date): ?int
{
    if (!$date || isExpired($date)) {
        return null;
    }
    $exp = new DateTime($date);
    $now = new DateTime('today');
    $diff = $now->diff($exp);
    $mois = $diff->y * 12 + $diff->m;
    if ($diff->d >= 15) {
        $mois++;
    }
    return max(0, $mois);
}

function expirationStatus(?string $date): string
{
    if (!$date) {
        return 'none';
    }
    if (isExpired($date)) {
        return 'expired';
    }
    if (isExpiringSoon($date)) {
        return 'soon';
    }
    return 'ok';
}

function expirationStatusLabel(?string $date): string
{
    switch (expirationStatus($date)) {
        case 'expired':
            return 'Expiré';
        case 'soon':
            return 'À écouler (' . moisRestantsExpiration($date) . ' mois)';
        case 'ok':
            return 'OK';
        default:
            return '—';
    }
}

function expirationStatusClass(?string $date): string
{
    switch (expirationStatus($date)) {
        case 'expired':
            return 'badge-expired';
        case 'soon':
            return 'badge-warning-expiry';
        case 'ok':
            return 'bg-success';
        default:
            return 'bg-secondary';
    }
}

function sqlIntervalExpirationAlerte(): string
{
    return getAlerteExpirationMois() . ' MONTH';
}
