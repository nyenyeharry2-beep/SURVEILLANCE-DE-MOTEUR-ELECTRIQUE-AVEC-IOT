<?php
// Page de téléchargement KYRIOS — uploadez ce fichier dans htdocs/ sur InfinityFree
header('Content-Type: text/html; charset=utf-8');

$base = 'https://github.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/releases/download/kyrios-v1.0.0/';

$files = [
    'apk'  => ['label' => 'APK Android KYRIOS', 'file' => 'KYRIOS-v1.0.0.apk', 'icon' => '📱', 'size' => '81 Mo'],
    'sql'  => ['label' => 'Base MySQL (InfinityFree)', 'file' => 'kyrios_mysql.sql', 'icon' => '🗄️', 'size' => '13 Ko'],
    'db'   => ['label' => 'Base SQLite (PC local)', 'file' => 'kyrios.db', 'icon' => '💾', 'size' => '216 Ko'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>KYRIOS — Téléchargements</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:#0a0a0f;color:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{max-width:460px;width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:24px;padding:28px}
.logo{width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#6366f1,#a855f7);display:flex;align-items:center;justify-content:center;font-size:26px;font-weight:700;margin:0 auto 14px}
h1{text-align:center;font-size:26px}
.sub{text-align:center;color:rgba(255,255,255,.5);font-size:13px;margin:6px 0 24px}
a.dl{display:flex;align-items:center;gap:12px;background:#6366f1;color:#fff;text-decoration:none;padding:14px 18px;border-radius:14px;margin-bottom:10px;font-weight:600}
a.dl:hover{background:#818cf8}
a.dl.sec{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12)}
a.dl.sec:hover{background:rgba(255,255,255,.14)}
.size{margin-left:auto;font-size:12px;opacity:.7;font-weight:400}
.info{background:rgba(99,102,241,.15);border-radius:12px;padding:14px;margin-top:20px;font-size:13px;line-height:1.7;color:rgba(255,255,255,.75)}
.tip{margin-top:14px;font-size:12px;color:rgba(255,255,255,.4);text-align:center;line-height:1.5}
</style>
</head>
<body>
<div class="card">
  <div class="logo">K</div>
  <h1>KYRIOS</h1>
  <p class="sub">Messagerie & Réseau Social</p>

  <?php foreach ($files as $f): ?>
  <a class="dl <?= $f === $files['sql'] || $f === $files['db'] ? 'sec' : '' ?>"
     href="<?= $base . $f['file'] ?>"
     download="<?= $f['file'] ?>">
    <span><?= $f['icon'] ?></span>
    <span><?= $f['label'] ?></span>
    <span class="size"><?= $f['size'] ?></span>
  </a>
  <?php endforeach; ?>

  <div class="info">
    <strong>Compte demo</strong><br>
    Email : me@kyrios.app<br>
    Mot de passe : Kyrios2026!
  </div>
  <p class="tip">Sur Android : maintenez le bouton APK → "Télécharger le lien"</p>
</div>
</body>
</html>
