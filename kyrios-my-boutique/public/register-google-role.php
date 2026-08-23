<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'client';
    $allowedRoles = ['client', 'vendeur', 'livreur'];
    if (in_array($role, $allowedRoles, true)) {
        $stmt = $db->prepare('UPDATE users SET role = ?, shop_name = ?, shop_description = ? WHERE id = ?');
        $stmt->execute([
            $role,
            $role === 'vendeur' ? ($_POST['shop_name'] ?? null) : null,
            $role === 'vendeur' ? ($_POST['shop_description'] ?? null) : null,
            $user['id'],
        ]);
    }
    header('Location: /index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choisir votre profil · Kyrios My Boutique</title>
    <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-logo">
            <img src="/assets/img/logo.svg" alt="">
            <h1>Bienvenue <?= e(explode(' ', $user['full_name'])[0]) ?> !</h1>
            <p>Choisissez comment vous souhaitez utiliser Kyrios My Boutique</p>
        </div>

        <form method="POST">
            <div class="role-selector">
                <label class="role-option selected" data-role="client">
                    <input type="radio" name="role" value="client" checked>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Client</span>
                </label>
                <label class="role-option" data-role="vendeur">
                    <input type="radio" name="role" value="vendeur">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                    <span>Vendeur</span>
                </label>
                <label class="role-option" data-role="livreur">
                    <input type="radio" name="role" value="livreur">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4m-4 0a2 2 0 100 4h4a2 2 0 100-4m-4 0V8"/></svg>
                    <span>Livreur</span>
                </label>
            </div>

            <div id="sellerFields" style="display:none;">
                <div class="form-group">
                    <label for="shop_name">Nom de la boutique</label>
                    <input type="text" id="shop_name" name="shop_name" class="form-control" placeholder="Ma Super Boutique">
                </div>
                <div class="form-group">
                    <label for="shop_description">Description</label>
                    <textarea id="shop_description" name="shop_description" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Commencer</button>
        </form>
    </div>
</div>
<script>
document.querySelectorAll('.role-option').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.role-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        el.querySelector('input').checked = true;
        document.getElementById('sellerFields').style.display = el.dataset.role === 'vendeur' ? 'block' : 'none';
    });
});
</script>
</body>
</html>
