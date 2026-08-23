<?php
require_once __DIR__ . '/bootstrap.php';

if ($auth->user()) {
    header('Location: /index.php');
    exit;
}

$error = '';
$selectedRole = $_POST['role'] ?? 'client';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => trim($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'full_name' => trim($_POST['full_name'] ?? ''),
        'role' => $_POST['role'] ?? 'client',
        'phone' => trim($_POST['phone'] ?? ''),
        'shop_name' => trim($_POST['shop_name'] ?? ''),
        'shop_description' => trim($_POST['shop_description'] ?? ''),
    ];

    if (strlen($data['password']) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($data['role'] === 'vendeur' && empty($data['shop_name'])) {
        $error = 'Le nom de boutique est requis pour les vendeurs.';
    } else {
        $result = $auth->register($data);
        if ($result['success']) {
            header('Location: /index.php');
            exit;
        }
        $error = $result['error'];
    }
    $selectedRole = $data['role'];
}

$config = appConfig();
$googleAuthUrl = googleAuthUrl($config, 'register');
$googleConfigured = !empty($config['google']['client_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription · Kyrios My Boutique</title>
    <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-logo">
            <img src="/assets/img/logo.svg" alt="Kyrios My Boutique">
            <h1>Rejoindre Kyrios</h1>
            <p>Choisissez votre profil et commencez à acheter ou vendre</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="registerForm">
            <label style="font-size:0.85rem;font-weight:600;margin-bottom:8px;display:block;">Je suis :</label>
            <div class="role-selector">
                <label class="role-option <?= $selectedRole === 'client' ? 'selected' : '' ?>" data-role="client">
                    <input type="radio" name="role" value="client" <?= $selectedRole === 'client' ? 'checked' : '' ?>>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span>Client</span>
                </label>
                <label class="role-option <?= $selectedRole === 'vendeur' ? 'selected' : '' ?>" data-role="vendeur">
                    <input type="radio" name="role" value="vendeur" <?= $selectedRole === 'vendeur' ? 'checked' : '' ?>>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span>Vendeur</span>
                </label>
                <label class="role-option <?= $selectedRole === 'livreur' ? 'selected' : '' ?>" data-role="livreur">
                    <input type="radio" name="role" value="livreur" <?= $selectedRole === 'livreur' ? 'checked' : '' ?>>
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h4m-4 0a2 2 0 100 4h4a2 2 0 100-4m-4 0V8"/></svg>
                    <span>Livreur</span>
                </label>
            </div>

            <div class="form-group">
                <label for="full_name">Nom complet</label>
                <input type="text" id="full_name" name="full_name" class="form-control" required placeholder="Jean Dupont">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="votre@email.com">
            </div>
            <div class="form-group">
                <label for="phone">Téléphone (optionnel)</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+33 6 12 34 56 78">
            </div>
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="6" placeholder="Min. 6 caractères">
            </div>

            <div id="sellerFields" style="display:<?= $selectedRole === 'vendeur' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label for="shop_name">Nom de la boutique *</label>
                    <input type="text" id="shop_name" name="shop_name" class="form-control" placeholder="Ma Super Boutique">
                </div>
                <div class="form-group">
                    <label for="shop_description">Description de la boutique</label>
                    <textarea id="shop_description" name="shop_description" class="form-control" rows="2" placeholder="Décrivez votre activité..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <?php if ($googleAuthUrl): ?>
        <div class="divider">ou</div>
        <a href="<?= e($googleAuthUrl) ?>" class="btn btn-google">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            S'inscrire avec Google
        </a>
        <?php endif; ?>

        <p style="text-align:center;margin-top:20px;font-size:0.9rem;color:var(--text-muted);">
            Déjà un compte ? <a href="/login.php">Se connecter</a>
        </p>
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
