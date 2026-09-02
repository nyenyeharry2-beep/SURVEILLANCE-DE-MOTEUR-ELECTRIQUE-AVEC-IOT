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

function e(?string $value): string