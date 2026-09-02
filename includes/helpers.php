<?php

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

function formatMoney(float $amount): string
{
    return number_format($amount, 0, ',', ' ') . ' FCFA';
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
