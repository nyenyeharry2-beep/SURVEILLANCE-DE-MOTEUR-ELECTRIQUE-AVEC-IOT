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

$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productModel->create((int) $user['id'], [
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => (float) ($_POST['price'] ?? 0),
        'category' => $_POST['category'] ?? 'general',
        'stock' => (int) ($_POST['stock'] ?? 1),
    ]);
    $success = 'Produit ajouté avec succès !';
}

$products = $productModel->getBySeller((int) $user['id']);

$pageTitle = 'Mes produits';
$currentPage = 'seller';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:280px 1fr;">
    <?php require __DIR__ . '/includes/sidebar-left.php'; ?>

    <main>
        <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>

        <div class="card" style="padding:20px;margin-bottom:16px;">
            <h2>➕ Ajouter un produit</h2>
            <form method="POST" style="margin-top:16px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group">
                        <label>Titre</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Prix (€)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <select name="category" class="form-control">
                            <option value="mode">Mode</option>
                            <option value="accessoires">Accessoires</option>
                            <option value="chaussures">Chaussures</option>
                            <option value="electronique">Électronique</option>
                            <option value="maison">Maison</option>
                            <option value="general">Général</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" class="form-control" min="1" value="1">
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Publier le produit</button>
            </form>
        </div>

        <div class="card" style="padding:20px;">
            <h2>📦 Mes produits (<?= count($products) ?>)</h2>
            <div class="products-grid" style="margin-top:16px;">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <div class="product-card-image">🛍️</div>
                    <div class="product-card-body">
                        <h4><?= e($product['title']) ?></h4>
                        <div class="product-price"><?= number_format((float)$product['price'], 2, ',', ' ') ?> €</div>
                        <span style="font-size:0.8rem;color:var(--text-muted);">Stock: <?= $product['stock'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
