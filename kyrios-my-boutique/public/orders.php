<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
$paymentModel = new Kyrios\Payment($db);
$messaging = new Kyrios\Messaging($db);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

if (isset($_GET['paid']) && isset($_GET['order'])) {
    $orderId = (int) $_GET['order'];
    $db->prepare('UPDATE orders SET payment_status = "paid", status = "confirmed" WHERE id = ? AND client_id = ?')
        ->execute([$orderId, $user['id']]);
    $db->prepare('UPDATE payments SET status = "paid" WHERE order_id = ?')->execute([$orderId]);
}

$orders = $paymentModel->getClientOrders((int) $user['id']);
$success = isset($_GET['success']);

$pageTitle = 'Mes commandes';
$currentPage = 'orders';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:1fr;max-width:700px;">
<main>
    <?php if ($success): ?>
    <div class="alert alert-success">
        ✅ Commande confirmée ! Référence : <strong><?= e($_GET['ref'] ?? '') ?></strong>
        <?php if (($_GET['ref'] ?? '') !== ''): ?>
        <br><small>Pour Mobile Money, envoyez le montant au numéro indiqué par le vendeur avec cette référence.</small>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="padding:20px;">
        <h2>📋 Mes commandes</h2>

        <?php foreach ($orders as $order): ?>
        <div style="border:1px solid var(--border);border-radius:12px;padding:16px;margin-top:12px;display:flex;gap:16px;">
            <?php if ($order['product_image']): ?>
            <img src="<?= e($order['product_image']) ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:8px;">
            <?php else: ?>
            <div style="width:64px;height:64px;background:var(--bg);border-radius:8px;display:flex;align-items:center;justify-content:center;">🛍️</div>
            <?php endif; ?>
            <div style="flex:1;">
                <strong><?= e($order['product_title']) ?></strong>
                <p style="font-size:0.85rem;color:var(--text-muted);">
                    Vendeur: <?= e($order['shop_name'] ?: $order['seller_name']) ?>
                </p>
                <div class="product-price" style="font-size:1rem;"><?= number_format((float)$order['total_price'], 2, ',', ' ') ?> €</div>
                <div style="margin-top:8px;display:flex;gap:8px;flex-wrap:wrap;">
                    <span class="badge badge-client"><?= e($order['payment_method'] ?? 'cash') ?></span>
                    <span class="badge <?= ($order['payment_status'] ?? '') === 'paid' ? 'badge-delivery' : 'badge-seller' ?>">
                        <?= ($order['payment_status'] ?? 'pending') === 'paid' ? 'Payé' : 'En attente' ?>
                    </span>
                    <span class="badge badge-client"><?= e($order['status']) ?></span>
                </div>
                <?php if (!empty($order['payment_reference'])): ?>
                <p style="font-size:0.8rem;margin-top:6px;">Réf: <code><?= e($order['payment_reference']) ?></code></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($orders)): ?>
        <p style="text-align:center;padding:40px;color:var(--text-muted);">Aucune commande pour le moment.</p>
        <a href="/marketplace.php" class="btn btn-primary" style="max-width:200px;margin:0 auto;display:block;">Voir la boutique</a>
        <?php endif; ?>
    </div>
</main>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
