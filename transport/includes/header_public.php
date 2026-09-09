<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/functions.php';
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title><?= e($pageTitle ?? 'Inscription Transport Scolaire') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="public-body">
<div class="public-header text-center py-3 mb-3">
    <div class="container">
        <h5 class="mb-0 fw-bold"><?= e(getSetting('school_foundation', 'FONDATION EBEN EZER – ORA S.A.R.I')) ?></h5>
        <p class="mb-0 small"><?= e(getSetting('school_project', 'PROJET EDUCATIF')) ?></p>
        <h4 class="mb-0 text-primary fw-bold"><?= e(getSetting('school_name', 'C.S LES SUPER GENIES')) ?></h4>
        <p class="mb-0 small text-muted">🚌 Inscription au transport scolaire</p>
    </div>
</div>
<main class="container pb-5">
