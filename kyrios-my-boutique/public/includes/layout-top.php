<?php
/** @var array $user */
/** @var string $currentPage */
/** @var int $unreadMessages */
$config = appConfig();
$unreadMessages = $unreadMessages ?? 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Accueil') ?> · Kyrios My Boutique</title>
    <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="/index.php" class="navbar-brand">
        <img src="/assets/img/logo.svg" alt="Logo">
        Kyrios My Boutique
    </a>

    <div class="navbar-search">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="search" placeholder="Rechercher produits, vendeurs..." id="globalSearch">
    </div>

    <div class="navbar-nav">
        <a href="/index.php" class="nav-item <?= ($currentPage ?? '') === 'home' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
            Accueil
        </a>
        <a href="/marketplace.php" class="nav-item <?= ($currentPage ?? '') === 'marketplace' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            Boutique
        </a>
        <a href="/messages.php" class="nav-item <?= ($currentPage ?? '') === 'messages' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            Messages
            <?php if ($unreadMessages > 0): ?><span class="nav-badge"><?= $unreadMessages ?></span><?php endif; ?>
        </a>
        <a href="/profile.php" class="nav-item <?= ($currentPage ?? '') === 'profile' ? 'active' : '' ?>">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            Profil
        </a>
    </div>

    <div class="navbar-user" onclick="location.href='/profile.php'">
        <img src="<?= avatarUrl($user['avatar_url'], $user['full_name']) ?>" alt="">
    </div>
</nav>
