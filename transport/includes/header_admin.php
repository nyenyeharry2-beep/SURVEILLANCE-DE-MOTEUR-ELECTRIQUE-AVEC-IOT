<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Administration') ?> - Transport Scolaire</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css?v=2" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/admin.css?v=3" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark bg-dark navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/dashboard.php">
            <?= renderSchoolLogo('small', 'navbar-logo') ?>
            <span><i class="bi bi-bus-front"></i> Transport Scolaire</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/dashboard.php"><i class="bi bi-speedometer2"></i> Tableau de bord</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'students' || $currentPage === 'student' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/students.php"><i class="bi bi-people"></i> Élèves</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'payments' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/payments.php"><i class="bi bi-cash-coin"></i> Paiements</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'control_sheet' || $currentPage === 'reports' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/control_sheet.php"><i class="bi bi-table"></i> Fiche contrôle</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'stops' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/stops.php"><i class="bi bi-geo-alt"></i> Arrêts</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'classes' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/classes.php"><i class="bi bi-mortarboard"></i> Classes</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'tariffs' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/tariffs.php"><i class="bi bi-tag"></i> Tarifs</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'qr_code' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/qr_code.php"><i class="bi bi-qr-code"></i> QR Code</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'history' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/history.php"><i class="bi bi-clock-history"></i> Historique</a></li>
                <li class="nav-item"><a class="nav-link <?= $currentPage === 'settings' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/settings.php"><i class="bi bi-gear"></i> Paramètres</a></li>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= e($currentUser['nom'] ?? 'Admin') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/admin/logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid py-4">
    <?= renderFlash() ?>
