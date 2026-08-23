<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
if ($user['role'] !== 'vendeur') {
    header('Location: /index.php');
    exit;
}

$paymentModel = new Kyrios\Payment($db);
$messaging = new Kyrios\Messaging($db);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $paymentModel->markPaid((int) $_POST['order_id'], (int) $user['id']);
    header('Location: /seller-orders.php?confirmed=1');
    exit;
}

$stmt = $db->prepare(
    'SELECT o.*, pr.title AS product_title, c.full_name AS client_name, c.phone AS client_phone
     FROM orders o
     JOIN products pr ON pr.id = o.product_id
     JOIN users c ON c.id = o.client_id
     WHERE o.seller_id = ?
     ORDER BY o.created_at DESC'
);
$stmt->execute([$user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'Ventes';
$currentPage = 'seller';
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:280px 1fr;">
    <?php require __DIR__ . '/includes/sidebar-left.php'; ?>
    <main>
        <?php if (isset($_GET['confirmed'])): ?>
        <div class="alert alert-success">Paiement confirmé !</div>
        <?php endif; ?>

        <div class="card" style="padding:20px;">
            <h2>💰 Ventes & Paiements</h2>
            <p style="color:var(--text-muted);font-size:0.9rem;margin-bottom:16px;">
                Confirmez les paiements Mobile Money reçus sur votre compte.
            </p>

            <?php foreach ($orders as $order): ?>
            <div style="border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:12px;">
                <strong>#<?= $order['id'] ?> — <?= e($order['product_title']) ?></strong>
                <p style="font-size:0.85rem;color:var(--text-muted);">
                    Client: <?= e($order['client_name']) ?> · <?= e($order['client_phone'] ?? $order['phone_number'] ?? '') ?>
                </p>
                <div class="product-price"><?= number_format((float)$order['total_price'], 2, ',', ' ') ?> €</div>
                <p style="font-size:0.85rem;margin-top:6px;">
                    Paiement: <strong><?= e($order['payment_method'] ?? 'cash') ?></strong>
                    · Statut: <strong><?= e($order['payment_status'] ?? 'pending') ?></strong>
                    <?php if (!empty($order['payment_reference'])): ?>
                    · Réf: <code><?= e($order['payment_reference']) ?></code>
                    <?php endif; ?>
                </p>
                <?php if (($order['payment_status'] ?? '') !== 'paid'): ?>
                <form method="POST" style="margin-top:10px;">
                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                    <button type="submit" class="btn btn-primary btn-sm">✓ Confirmer paiement reçu</button>
                </form>
                <?php else: ?>
                <span class="badge badge-delivery" style="margin-top:8px;">Payé ✓</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <?php if (empty($orders)): ?>
            <p style="text-align:center;padding:40px;color:var(--text-muted);">Aucune vente pour le moment.</p>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
