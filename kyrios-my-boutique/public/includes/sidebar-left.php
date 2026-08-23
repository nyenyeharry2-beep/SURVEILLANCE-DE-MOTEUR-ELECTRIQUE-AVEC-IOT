<aside class="sidebar-left">
    <ul class="sidebar-menu">
        <li>
            <a href="/index.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1h-2z"/></svg>
                Fil d'actualité
            </a>
        </li>
        <li>
            <a href="/marketplace.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                Marketplace
            </a>
        </li>
        <li>
            <a href="/messages.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Messagerie
            </a>
        </li>
        <?php if ($user['role'] === 'vendeur'): ?>
        <li>
            <a href="/seller.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                Mes produits
            </a>
        </li>
        <?php endif; ?>
        <?php if ($user['role'] === 'livreur'): ?>
        <li>
            <a href="/delivery.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4m-4 0a2 2 0 100 4h4a2 2 0 100-4m-4 0V8"/></svg>
                Livraisons
            </a>
        </li>
        <?php endif; ?>
        <li>
            <a href="/profile.php">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Mon profil
            </a>
        </li>
    </ul>

    <div class="sidebar-section" style="margin-top:16px;">
        <div style="display:flex;align-items:center;gap:10px;">
            <img src="<?= avatarUrl($user['avatar_url'], $user['full_name']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;">
            <div>
                <strong style="font-size:0.9rem;"><?= e($user['full_name']) ?></strong><br>
                <?= roleBadge($user['role']) ?>
            </div>
        </div>
    </div>
</aside>
