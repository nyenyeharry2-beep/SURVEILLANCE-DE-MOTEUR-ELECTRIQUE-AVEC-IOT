<?php
// Page d'accueil KYRIOS — test que htdocs fonctionne
header('Content-Type: text/html; charset=utf-8');
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>KYRIOS — OK</title>
<style>
body{font-family:system-ui,sans-serif;background:#0a0a0f;color:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
.box{max-width:420px;background:rgba(255,255,255,.08);border-radius:20px;padding:28px;text-align:center}
.ok{color:#22c55e;font-size:48px;margin-bottom:12px}
h1{margin:0 0 8px}
p{color:rgba(255,255,255,.6);font-size:14px;line-height:1.6}
a{color:#818cf8}
</style>
</head>
<body>
<div class="box">
<div class="ok">✓</div>
<h1>KYRIOS en ligne</h1>
<p>Le dossier <strong>htdocs</strong> fonctionne.<br>
Test API : <a href="api.php?route=health">api.php?route=health</a></p>
</div>
</body>
</html>
