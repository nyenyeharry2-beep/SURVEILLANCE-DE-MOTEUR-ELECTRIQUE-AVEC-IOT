<?php
/**
 * Déploiement automatique v2 — Kyrios My Boutique
 * Visitez une fois : https://kyriosboutique.page.gd/deploy-v2.php?key=kyrios2026
 * Puis supprimez ce fichier.
 */
require_once __DIR__ . '/bootstrap.php';

$secret = 'kyrios2026';
if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    die('Accès refusé.');
}

header('Content-Type: text/html; charset=utf-8');
echo '<pre style="font:14px monospace;padding:20px;">';
echo "=== Kyrios Deploy v2 ===\n\n";

$repoBase = 'https://raw.githubusercontent.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/cursor/kyrios-marketplace-57f1/kyrios-my-boutique/public';

$files = [
    'seller-upload.php',
    'deploy-v2.php',
    'src/Product.php',
    'src/Upload.php',
    'seller.php',
    'marketplace.php',
    'bootstrap.php',
];

$dir = __DIR__;
$ok = 0;
foreach ($files as $f) {
    $url = $repoBase . '/' . $f;
    $content = @file_get_contents($url);
    if ($content === false) {
        echo "✗ Échec téléchargement: $f\n";
        continue;
    }
    $target = $dir . '/' . $f;
    $subDir = dirname($target);
    if (!is_dir($subDir)) {
        mkdir($subDir, 0755, true);
    }
    if (file_put_contents($target, $content) !== false) {
        echo "✓ Mis à jour: $f\n";
        $ok++;
    } else {
        echo "✗ Échec écriture: $f\n";
    }
}

echo "\n--- Migration SQL catalog-v2 ---\n";
$sqlFile = __DIR__ . '/../deploy/catalog-kyrios-v2.sql';
if (!file_exists($sqlFile)) {
    $sqlUrl = 'https://raw.githubusercontent.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/cursor/kyrios-marketplace-57f1/kyrios-my-boutique/deploy/catalog-kyrios-v2.sql';
    $sql = @file_get_contents($sqlUrl);
} else {
    $sql = file_get_contents($sqlFile);
}

if ($sql) {
    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
    $done = 0;
    foreach ($statements as $stmt) {
        if ($stmt === '' || strpos(ltrim($stmt), '--') === 0) {
            continue;
        }
        try {
            $db->exec($stmt);
            $done++;
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                echo "! SQL: " . substr($e->getMessage(), 0, 80) . "\n";
            }
        }
    }
    echo "✓ SQL exécuté ($done requêtes)\n";
} else {
    echo "✗ Impossible de charger catalog-kyrios-v2.sql\n";
}

echo "\n--- Téléchargement photos placeholders ---\n";
$photos = [
    'lp-robe-creme.jpg','lp-robe-bleu.jpg','lp-robe-rose.jpg','lp-robe-bleu-plisse.jpg',
    'lp-robe-rouge.jpg','lp-robe-noir.jpg','robe-magenta.jpg','robe-bleu-argent.jpg',
    'robe-marine.jpg','ensemble-marine.jpg','ensemble-olive.jpg','ensemble-creme.jpg',
    'robe-floral-menthe.jpg','robe-floral-lavande.jpg','ensemble-noir.jpg','ensemble-bleu-satin.jpg',
    'chemise-marron.jpg','chemise-bleu-fleur.jpg','chemise-tribal.jpg','chemise-etoile.jpg',
    'chemise-kaki.jpg','sneakers-or-noir.jpg','sneakers-or-rose.jpg','sneakers-or-marron.jpg',
];
$photoBase = 'https://raw.githubusercontent.com/nyenyeharry2-beep/SURVEILLANCE-DE-MOTEUR-ELECTRIQUE-AVEC-IOT/cursor/kyrios-marketplace-57f1/kyrios-my-boutique/public/uploads/products/';
$photoDir = $dir . '/uploads/products';
if (!is_dir($photoDir)) {
    mkdir($photoDir, 0755, true);
}
$photosOk = 0;
foreach ($photos as $photo) {
    $data = @file_get_contents($photoBase . $photo);
    if ($data && strlen($data) > 1000) {
        file_put_contents($photoDir . '/' . $photo, $data);
        echo "✓ Photo: $photo\n";
        $photosOk++;
    } else {
        echo "✗ Photo: $photo\n";
    }
}

echo "\n=== TERMINÉ ===\n";
echo "Fichiers: $ok | Photos: $photosOk/24\n";
echo "Visitez: /seller-upload.php pour uploader vos vraies photos\n";
echo "Supprimez deploy-v2.php après usage.\n";
echo '</pre>';
