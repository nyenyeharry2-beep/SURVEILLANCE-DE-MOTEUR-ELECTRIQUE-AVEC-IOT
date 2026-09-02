<?php
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (attemptLogin($email, $password)) {
        flash('success', 'Bienvenue, ' . currentUser()['nom'] . ' !');
        redirect('dashboard.php');
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}

$pageTitle = 'Connexion';
require_once __DIR__ . '/includes/header.php';
?>

<div class="login-page">
    <div class="login-card card p-4 mx-3">
        <div class="text-center mb-4">
            <img src="<?= e(appLogo()) ?>" alt="Pharmacie Nouvelle Eve" class="login-logo mb-3">
            <p class="login-tagline mb-0"><?= e(appTagline()) ?></p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required
                       value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin@pharmagest.local">
            </div>
            <div class="mb-4">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-box-arrow-in-right me-1"></i> Se connecter
            </button>
        </form>

        <p class="text-muted text-center small mt-3 mb-0">
            Compte démo : admin@pharmagest.local / admin123
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
