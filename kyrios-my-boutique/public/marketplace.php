<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
$productModel = new Kyrios\Product($db);
$messaging = new Kyrios\Messaging($db);

$category = $_GET['category'] ?? null;
$products = $productModel->getAll(24, $category);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

$categories = ['mode', 'accessoires', 'chaussures', 'electronique', 'maison', 'general'];

$pageTitle = 'Marketplace';
$currentPage = 'marketplace';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout">
    <?php require __DIR__ . '/includes/sidebar-left.php'; ?>

    <main>
        <div class="card" style="padding:20px;">
            <h2 style="margin-bottom:16px;">🛍️ Marketplace</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                <a href="/marketplace.php" class="btn btn-sm <?= !$category ? 'btn-primary' : 'btn-secondary' ?>">Tous</a>
                <?php foreach ($categories as $cat): ?>
                <a href="/marketplace.php?category=<?= $cat ?>" class="btn btn-sm <?= $category === $cat ? 'btn-primary' : 'btn-secondary' ?>">
                    <?= ucfirst($cat) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?= productImageHtml($product['image_url'] ?? null) ?>
                    <div class="product-card-body">
                        <h4><?= e($product['title']) ?></h4>
                        <div class="seller">
                            <?= e($product['shop_name'] ?: $product['seller_name']) ?>
                            <?= $product['is_verified'] ? ' ✓' : '' ?>
                        </div>
                        <div class="product-price"><?= number_format((float)$product['price'], 2, ',', ' ') ?> €</div>
                        <div style="display:flex;gap:8px;margin-top:10px;">
                            <a href="/messages.php?user=<?= $product['seller_id'] ?>" class="btn btn-secondary btn-sm">Contacter</a>
                            <a href="/checkout.php?product_id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">Commander</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($products)): ?>
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <p style="font-size:3rem;">📦</p>
                <p>Aucun produit disponible pour le moment.</p>
                <?php if ($user['role'] === 'vendeur'): ?>
                <a href="/seller.php" class="btn btn-primary" style="margin-top:12px;">Ajouter un produit</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <aside class="sidebar-right">
        <div class="sidebar-section">
            <h3>📋 Catégories</h3>
            <?php foreach ($categories as $cat): ?>
            <a href="/marketplace.php?category=<?= $cat ?>" style="display:block;padding:6px 0;color:var(--text);text-decoration:none;font-size:0.9rem;">
                <?= ucfirst($cat) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
