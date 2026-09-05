<?php
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? appName()) ?> — <?= e(appName()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <link rel="icon" type="image/jpeg" href="<?= e(appLogo()) ?>">
</head>
<body>
<?php if ($user): ?>
<div class="d-flex" id="wrapper">
    <nav id="sidebar" class="sidebar-dark">
        <div class="sidebar-brand py-3 px-3">
            <a href="dashboard.php" class="text-decoration-none">
                <span class="sidebar-brand-text"><?= e(appName()) ?></span>
                <small class="sidebar-brand-tagline d-block"><?= e(appTagline()) ?></small>
            </a>
        </div>
        <ul class="nav flex-column p-2">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Tableau de bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'medicaments' ? 'active' : '' ?>" href="medicaments.php">
                    <i class="bi bi-box-seam me-2"></i> Médicaments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories' ? 'active' : '' ?>" href="categories.php">
                    <i class="bi bi-tags me-2"></i> Catégories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'fournisseurs' ? 'active' : '' ?>" href="fournisseurs.php">
                    <i class="bi bi-truck me-2"></i> Fournisseurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'achats' ? 'active' : '' ?>" href="achats.php">
                    <i class="bi bi-cart-plus me-2"></i> Achats / Entrées
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'ventes' ? 'active' : '' ?>" href="ventes.php">
                    <i class="bi bi-receipt me-2"></i> Ventes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'caisse' ? 'active' : '' ?>" href="caisse.php">
                    <i class="bi bi-cash-stack me-2"></i> Entrées / Sorties
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'journal' ? 'active' : '' ?>" href="journal.php">
                    <i class="bi bi-journal-text me-2"></i> Journal quotidien
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'rapports' ? 'active' : '' ?>" href="rapports.php">
                    <i class="bi bi-bar-chart me-2"></i> Rapports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'stock' ? 'active' : '' ?>" href="stock.php">
                    <i class="bi bi-exclamation-triangle me-2"></i> Alertes stock
                </a>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'utilisateurs' ? 'active' : '' ?>" href="utilisateurs.php">
                    <i class="bi bi-people me-2"></i> Utilisateurs
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer p-3 mt-auto">
            <small class="user-name d-block mb-2"><?= e($user['nom']) ?></small>
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
            </a>
        </div>
    </nav>
    <main id="page-content" class="flex-grow-1">
        <header class="app-topbar d-flex align-items-center justify-content-between px-4 py-2">
            <h1 class="app-topbar-title mb-0"><?= e($pageTitle ?? appName()) ?></h1>
            <div class="d-flex align-items-center gap-3">
                <small class="text-muted" title="<?= e(appTimezone()->getName()) ?>">
                    <i class="bi bi-clock me-1"></i><?= e(localTimeInfo()['label']) ?>
                </small>
                <div class="app-topbar-logo-wrap">
                    <img src="<?= e(appLogo()) ?>" alt="<?= e(appName()) ?>" class="app-topbar-logo" width="40" height="40">
                </div>
            </div>
        </header>
        <div class="container-fluid p-4">
            <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
<?php else: ?>
<main>
<?php endif; ?>
