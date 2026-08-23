<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
if ($user['role'] !== 'vendeur') {
    header('Location: /index.php');
    exit;
}

$productModel = new Kyrios\Product($db);
$messaging = new Kyrios\Messaging($db);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

$expectedFiles = [
    'lp-robe-creme.jpg' => 'Robe Little Princess — Satin Crème',
    'lp-robe-bleu.jpg' => 'Robe Little Princess — Bleu Ciel',
    'lp-robe-rose.jpg' => 'Robe Little Princess — Rose Poudré',
    'lp-robe-bleu-plisse.jpg' => 'Robe Little Princess — Bleu Plissé',
    'lp-robe-rouge.jpg' => 'Robe Little Princess — Velours Rouge',
    'lp-robe-noir.jpg' => 'Robe Little Princess — Velours Noir',
    'robe-magenta.jpg' => 'Robe de Soirée — Magenta Peplum',
    'robe-bleu-argent.jpg' => 'Robe de Soirée — Bleu Argent',
    'robe-marine.jpg' => 'Robe de Soirée — Bleu Marine',
    'ensemble-marine.jpg' => 'Ensemble Brodé — Bleu Marine',
    'ensemble-olive.jpg' => 'Ensemble Satin — Vert Olive',
    'ensemble-creme.jpg' => 'Ensemble Satin — Crème Brodé',
    'robe-floral-menthe.jpg' => 'Robe Florale — Menthe Grand Palace',
    'robe-floral-lavande.jpg' => 'Robe Florale — Lavande Grand Palace',
    'ensemble-noir.jpg' => 'Ensemble Brodé — Noir Paon',
    'ensemble-bleu-satin.jpg' => 'Ensemble Satin — Bleu Nuit',
    'chemise-marron.jpg' => 'Chemise Boutique — Marron Pois',
    'chemise-bleu-fleur.jpg' => 'Chemise Boutique — Bleu Floral',
    'chemise-tribal.jpg' => 'Chemise Boutique — Motif Tribal',
    'chemise-etoile.jpg' => 'Chemise Boutique — Étoiles Brodées',
    'chemise-kaki.jpg' => 'Chemise Utility — Kaki',
    'sneakers-or-noir.jpg' => 'Sneakers Platform — Blanc Or Noir',
    'sneakers-or-rose.jpg' => 'Sneakers Platform — Blanc Or Rose',
    'sneakers-or-marron.jpg' => 'Sneakers Platform — Blanc Or Marron',
];

$results = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['photos']['name']) || !is_array($_FILES['photos']['name'])) {
        $error = 'Sélectionnez au moins une photo.';
    } else {
        foreach ($_FILES['photos']['name'] as $i => $name) {
            if (empty($name)) {
                continue;
            }

            $file = [
                'name' => $_FILES['photos']['name'][$i],
                'type' => $_FILES['photos']['type'][$i],
                'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                'error' => $_FILES['photos']['error'][$i],
                'size' => $_FILES['photos']['size'][$i],
            ];

            $original = strtolower(basename($name));
            $target = isset($expectedFiles[$original]) ? $original : null;

            if (!$target) {
                $results[] = ['file' => $name, 'ok' => false, 'msg' => 'Nom non reconnu — voir GUIDE-PHOTOS.txt'];
                continue;
            }

            $upload = Kyrios\Upload::productImageNamed($file, $target);
            if (!$upload['success']) {
                $results[] = ['file' => $name, 'ok' => false, 'msg' => $upload['error']];
                continue;
            }

            $product = $productModel->getByImageFilename($target);
            if ($product && (int) $product['seller_id'] === (int) $user['id']) {
                $productModel->updateImage((int) $product['id'], (int) $user['id'], $upload['url']);
                $results[] = ['file' => $name, 'ok' => true, 'msg' => 'Lié à : ' . $product['title']];
            } else {
                $results[] = ['file' => $name, 'ok' => true, 'msg' => 'Photo enregistrée (produit catalogue requis)'];
            }
        }
    }
}

$pageTitle = 'Upload photos produits';
$currentPage = 'seller';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:280px 1fr;">
    <?php require __DIR__ . '/includes/sidebar-left.php'; ?>

    <main>
        <div class="card" style="padding:20px;margin-bottom:16px;">
            <h2>📸 Upload photos produits (bulk)</h2>
            <p style="color:var(--text-muted);margin:12px 0;">
                Renommez vos photos <strong>exactement</strong> comme dans la liste ci-dessous, puis uploadez-les toutes en une fois.
                Les photos seront automatiquement liées aux produits du catalogue.
            </p>
            <p style="margin-bottom:16px;">
                <a href="/seller.php" class="btn btn-secondary btn-sm">← Retour mes produits</a>
            </p>

            <?php if ($error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
            <?php endif; ?>

            <?php if ($results): ?>
            <div class="alert alert-success" style="margin-bottom:16px;">
                <strong><?= count(array_filter($results, fn($r) => $r['ok'])) ?> / <?= count($results) ?> photo(s) traitée(s)</strong>
                <ul style="margin:8px 0 0;padding-left:20px;">
                    <?php foreach ($results as $r): ?>
                    <li style="color:<?= $r['ok'] ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= e($r['file']) ?> — <?= e($r['msg']) ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Sélectionner vos photos (plusieurs fichiers)</label>
                    <input type="file" name="photos[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple required>
                    <small style="color:var(--text-muted);">JPG, PNG, WEBP — max 5 Mo par photo</small>
                </div>
                <button type="submit" class="btn btn-primary">Uploader et lier au catalogue</button>
            </form>
        </div>

        <div class="card" style="padding:20px;">
            <h3>📋 Noms de fichiers attendus (24 produits)</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:16px;font-size:0.9rem;">
                <?php foreach ($expectedFiles as $file => $label): ?>
                <?php
                    $exists = file_exists(__DIR__ . '/uploads/products/' . $file);
                    $size = $exists ? filesize(__DIR__ . '/uploads/products/' . $file) : 0;
                    $isReal = $exists && $size > 50000;
                ?>
                <div style="padding:8px 12px;background:var(--bg-secondary);border-radius:8px;display:flex;justify-content:space-between;align-items:center;">
                    <span><code><?= e($file) ?></code><br><small><?= e($label) ?></small></span>
                    <span style="font-size:1.2rem;"><?= $isReal ? '✅' : '⏳' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
