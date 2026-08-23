<?php
require_once __DIR__ . '/bootstrap.php';
$config = appConfig();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Configurer Google · Kyrios</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="auth-page">
<div class="auth-card" style="max-width:560px;">
    <div class="auth-logo">
        <img src="/assets/img/logo.svg" alt="">
        <h1>Connexion Google</h1>
        <p>Guide de configuration OAuth 2.0</p>
    </div>

    <div style="font-size:0.9rem;line-height:1.7;">
        <h3 style="margin-bottom:8px;">Étape 1 — Google Cloud Console</h3>
        <ol style="margin-left:20px;margin-bottom:16px;">
            <li>Allez sur <a href="https://console.cloud.google.com/" target="_blank">console.cloud.google.com</a></li>
            <li>Créez un projet → <strong>APIs & Services</strong> → <strong>Credentials</strong></li>
            <li><strong>Create Credentials</strong> → OAuth 2.0 Client ID → Web application</li>
        </ol>

        <h3>Étape 2 — URI autorisées</h3>
        <p><strong>Authorized redirect URI :</strong></p>
        <code style="display:block;background:var(--bg);padding:10px;border-radius:8px;margin:8px 0;word-break:break-all;">
            https://kyriosboutique.page.gd/auth/google/callback.php
        </code>

        <h3 style="margin-top:16px;">Étape 3 — Fichier .env</h3>
        <p>Ajoutez dans votre fichier <code>.env</code> sur InfinityFree :</p>
        <pre style="background:var(--bg);padding:12px;border-radius:8px;font-size:0.8rem;overflow-x:auto;">GOOGLE_CLIENT_ID=votre_id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=votre_secret
GOOGLE_REDIRECT_URI=https://kyriosboutique.page.gd/auth/google/callback.php</pre>

        <h3 style="margin-top:16px;">Étape 4 — Stripe (optionnel)</h3>
        <pre style="background:var(--bg);padding:12px;border-radius:8px;font-size:0.8rem;">STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...</pre>
        <p style="color:var(--text-muted);font-size:0.85rem;">Obtenez vos clés sur <a href="https://dashboard.stripe.com" target="_blank">dashboard.stripe.com</a></p>
    </div>

    <a href="/login.php" class="btn btn-primary btn-block" style="margin-top:20px;">← Retour connexion</a>
</div>
</div>
</body>
</html>
