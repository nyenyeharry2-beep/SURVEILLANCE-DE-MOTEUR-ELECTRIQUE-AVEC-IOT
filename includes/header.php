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
    <nav id="sidebar" class="text-white">
        <div class="sidebar-brand p-3 border-bottom border-secondary border-opacity-25">
            <a href="dashboard.php" class="text-decoration-none">
                <img src="<?= e(appLogo()) ?>" alt="<?= e(appName()) ?>" class="app-logo d-block mx-auto">
            </a>
            <div class="brand-tagline mt-2"><?= e(appTagline()) ?></div>
        </div>
        <ul class="nav flex-column p-2">
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2 me-2"></i> Tableau de bord
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'medicaments' ? 'active' : '' ?>" href="medicaments.php">
                    <i class="bi bi-box-seam me-2"></i> Médicaments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'categories' ? 'active' : '' ?>" href="categories.php">
                    <i class="bi bi-tags me-2"></i> Catégories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'fournisseurs' ? 'active' : '' ?>" href="fournisseurs.php">
                    <i class="bi bi-truck me-2"></i> Fournisseurs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'achats' ? 'active' : '' ?>" href="achats.php">
                    <i class="bi bi-cart-plus me-2"></i> Achats / Entrées
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'ventes' ? 'active' : '' ?>" href="ventes.php">
                    <i class="bi bi-receipt me-2"></i> Ventes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'journal' ? 'active' : '' ?>" href="journal.php">
                    <i class="bi bi-journal-text me-2"></i> Journal quotidien
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'rapports' ? 'active' : '' ?>" href="rapports.php">
                    <i class="bi bi-bar-chart me-2"></i> Rapports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'stock' ? 'active' : '' ?>" href="stock.php">
                    <i class="bi bi-exclamation-triangle me-2"></i> Alertes stock
                </a>
            </li>
            <?php if ($user['role'] === 'admin'): ?>
            <li class="nav-item">
                <a class="nav-link text-white <?= $currentPage === 'utilisateurs' ? 'active' : '' ?>" href="utilisateurs.php">
                    <i class="bi bi-people me-2"></i> Utilisateurs
                </a>
            </li>
            <?php endif; ?>
        </ul>
        <div class="sidebar-footer p-3 mt-auto border-top border-secondary">
            <small class="text-secondary d-block mb-2"><?= e($user['nom']) ?></small>
            <a href="logout.php" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Déconnexion
            </a>
        </div>
    </nav>
    <main id="page-content" class="flex-grow-1">
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
