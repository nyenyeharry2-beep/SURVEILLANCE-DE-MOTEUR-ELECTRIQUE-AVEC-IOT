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

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
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
        WHERE {$whereSql}
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
    return match (expirationStatus($date)) {
        'expired' => 'Expiré',
        'soon'    => 'À écouler (' . moisRestantsExpiration($date) . ' mois)',
        'ok'      => 'OK',
        default   => '—',
    };
}

function expirationStatusClass(?string $date): string
{
    return match (expirationStatus($date)) {
        'expired' => 'badge-expired',
        'soon'    => 'badge-warning-expiry',
        'ok'      => 'bg-success',
        default   => 'bg-secondary',
    };
}

function sqlIntervalExpirationAlerte(): string
{
    return getAlerteExpirationMois() . ' MONTH';
}
