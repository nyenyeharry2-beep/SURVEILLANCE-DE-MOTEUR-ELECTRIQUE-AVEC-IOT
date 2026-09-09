<?php
/**
 * Fichier de test — uploadez-le dans htdocs/transport/
 * Ouvrez : https://genies.free.je/transport/test_install.php
 */
header('Content-Type: text/html; charset=utf-8');
$base = __DIR__;
$checks = [
    'index.php' => file_exists($base . '/index.php'),
    'inscription.php' => file_exists($base . '/inscription.php'),
    'admin/login.php' => file_exists($base . '/admin/login.php'),
    'config/database.php' => file_exists($base . '/config/database.php'),
    'includes/functions.php' => file_exists($base . '/includes/functions.php'),
    'database/database.sql' => file_exists($base . '/database/database.sql'),
];
$allOk = !in_array(false, $checks, true);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test installation Transport Scolaire</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; }
        .ok { color: green; } .fail { color: red; }
        h1 { color: #1a5276; }
        code { background: #f4f4f4; padding: 2px 6px; }
    </style>
</head>
<body>
    <h1>Test installation — Transport Scolaire</h1>
    <p>Dossier détecté : <code><?= htmlspecialchars($base) ?></code></p>

    <h2>Fichiers</h2>
    <ul>
    <?php foreach ($checks as $file => $ok): ?>
        <li class="<?= $ok ? 'ok' : 'fail' ?>"><?= $ok ? '✅' : '❌' ?> <?= htmlspecialchars($file) ?></li>
    <?php endforeach; ?>
    </ul>

    <?php if ($allOk): ?>
    <p class="ok"><strong>✅ Structure OK !</strong></p>
    <p><a href="admin/login.php">→ Aller à la page de connexion admin</a></p>
    <p><a href="inscription.php">→ Aller au formulaire d'inscription</a></p>
    <?php else: ?>
    <p class="fail"><strong>❌ Fichiers manquants — revoyez l'upload dans le File Manager.</strong></p>
    <?php endif; ?>

    <h2>Structure attendue sur InfinityFree</h2>
    <pre>
htdocs/
└── transport/
    ├── index.php
    ├── inscription.php
    ├── test_install.php  ← ce fichier
    ├── admin/
    │   └── login.php
    ├── config/
    ├── includes/
    └── ...
    </pre>
</body>
</html>
