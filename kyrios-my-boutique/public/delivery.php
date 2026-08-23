<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
if ($user['role'] !== 'livreur') {
    header('Location: /index.php');
    exit;
}

$messaging = new Kyrios\Messaging($db);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

$stmt = $db->prepare(
    'SELECT o.*, pr.title AS product_title, c.full_name AS client_name, s.shop_name AS seller_name
     FROM orders o
     JOIN products pr ON pr.id = o.product_id
     JOIN users c ON c.id = o.client_id
     JOIN users s ON s.id = o.seller_id
     WHERE o.status IN ("confirmed", "shipped") AND (o.livreur_id IS NULL OR o.livreur_id = ?)
     ORDER BY o.created_at DESC'
);
$stmt->execute([$user['id']]);
$deliveries = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $update = $db->prepare('UPDATE orders SET livreur_id = ?, status = "shipped" WHERE id = ? AND livreur_id IS NULL');
    $update->execute([$user['id'], (int) $_POST['order_id']]);
    header('Location: /delivery.php');
    exit;
}

$pageTitle = 'Livraisons';
$currentPage = 'delivery';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:280px 1fr;">
    <?php require __DIR__ . '/includes/sidebar-left.php'; ?>

    <main>
        <div class="card" style="padding:20px;">
            <h2>🚚 Livraisons disponibles</h2>

            <?php foreach ($deliveries as $delivery): ?>
            <div style="border:1px solid var(--border);border-radius:var(--radius);padding:16px;margin-top:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <strong>#<?= $delivery['id'] ?> — <?= e($delivery['product_title']) ?></strong>
                        <p style="font-size:0.85rem;color:var(--text-muted);margin-top:4px;">
                            Client: <?= e($delivery['client_name']) ?> · Vendeur: <?= e($delivery['seller_name']) ?>
                        </p>
                        <p style="font-size:0.85rem;"><?= e($delivery['delivery_address'] ?? 'Adresse non renseignée') ?></p>
                    </div>
                    <div style="text-align:right;">
                        <div class="product-price"><?= number_format((float)$delivery['total_price'], 2, ',', ' ') ?> €</div>
                        <?php if (!$delivery['livreur_id']): ?>
                        <form method="POST" style="margin-top:8px;">
                            <input type="hidden" name="order_id" value="<?= $delivery['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm">Accepter la livraison</button>
                        </form>
                        <?php else: ?>
                        <span class="badge badge-delivery">En cours</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($deliveries)): ?>
            <div style="text-align:center;padding:40px;color:var(--text-muted);">
                <p style="font-size:3rem;">📭</p>
                <p>Aucune livraison disponible pour le moment.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
