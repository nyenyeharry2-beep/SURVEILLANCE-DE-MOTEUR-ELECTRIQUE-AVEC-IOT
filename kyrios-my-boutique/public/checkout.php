<?php
require_once __DIR__ . '/bootstrap.php';

$user = $auth->requireAuth();
$productModel = new Kyrios\Product($db);
$paymentModel = new Kyrios\Payment($db);
$messaging = new Kyrios\Messaging($db);
$unreadMessages = $messaging->getUnreadCount((int) $user['id']);
$config = appConfig();

$productId = (int) ($_GET['product_id'] ?? 0);
$product = $productModel->getById($productId);

if (!$product) {
    header('Location: /marketplace.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $paymentModel->createOrder((int) $user['id'], $productId, [
        'address' => trim($_POST['address'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'operator' => $_POST['operator'] ?? null,
        'payment_method' => $_POST['payment_method'] ?? 'cash',
    ]);

    if (!$result['success']) {
        $error = $result['error'];
    } elseif ($result['method'] === 'stripe') {
        $stripe = $paymentModel->createStripeSession(
            $result['order_id'],
            $result['amount'],
            $result['product']['title'],
            $config
        );
        if ($stripe['success']) {
            header('Location: ' . $stripe['url']);
            exit;
        }
        $error = $stripe['error'] ?? 'Paiement carte indisponible.';
    } else {
        header('Location: /orders.php?success=1&ref=' . urlencode($result['reference']));
        exit;
    }
}

$pageTitle = 'Paiement';
$currentPage = 'marketplace';
$stripeEnabled = !empty($config['stripe']['secret_key']);
require __DIR__ . '/includes/layout-top.php';
?>

<div class="app-layout" style="grid-template-columns:1fr;max-width:600px;">
<main>
    <div class="card" style="padding:24px;">
        <h2>💳 Finaliser la commande</h2>

        <?php if ($error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
        <?php endif; ?>

        <div style="display:flex;gap:16px;margin:20px 0;padding:16px;background:var(--bg);border-radius:12px;">
            <?php if ($product['image_url']): ?>
            <img src="<?= e($product['image_url']) ?>" alt="" style="width:80px;height:80px;object-fit:cover;border-radius:8px;">
            <?php else: ?>
            <div style="width:80px;height:80px;background:linear-gradient(135deg,#e0e7ff,#ede9fe);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:2rem;">🛍️</div>
            <?php endif; ?>
            <div>
                <h3><?= e($product['title']) ?></h3>
                <p style="color:var(--text-muted);font-size:0.9rem;"><?= e($product['shop_name'] ?: $product['seller_name']) ?></p>
                <div class="product-price"><?= number_format((float)$product['price'], 2, ',', ' ') ?> €</div>
            </div>
        </div>

        <form method="POST">
            <div class="form-group">
                <label>Adresse de livraison *</label>
                <textarea name="address" class="form-control" rows="2" required placeholder="Rue, ville, code postal..."></textarea>
            </div>
            <div class="form-group">
                <label>Téléphone *</label>
                <input type="tel" name="phone" class="form-control" required placeholder="+33 6 12 34 56 78">
            </div>

            <label style="font-weight:600;margin-bottom:12px;display:block;">Mode de paiement</label>

            <div class="payment-options">
                <label class="payment-option selected">
                    <input type="radio" name="payment_method" value="mobile_money" checked>
                    <div>
                        <strong>📱 Mobile Money</strong>
                        <small>Orange Money, MTN MoMo, Moov Money</small>
                    </div>
                </label>
                <div id="mobileFields" style="margin:0 0 16px 32px;">
                    <select name="operator" class="form-control">
                        <option value="orange">Orange Money</option>
                        <option value="mtn">MTN Mobile Money</option>
                        <option value="moov">Moov Money</option>
                        <option value="wave">Wave</option>
                    </select>
                    <p style="font-size:0.8rem;color:var(--text-muted);margin-top:8px;">
                        Vous recevrez les instructions de paiement après validation.
                    </p>
                </div>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="cash">
                    <div>
                        <strong>💵 Paiement à la livraison</strong>
                        <small>Payez en espèces à la réception</small>
                    </div>
                </label>

                <?php if ($stripeEnabled): ?>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="stripe">
                    <div>
                        <strong>💳 Carte bancaire (Stripe)</strong>
                        <small>Visa, Mastercard — paiement sécurisé</small>
                    </div>
                </label>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px;">
                Confirmer et payer <?= number_format((float)$product['price'], 2, ',', ' ') ?> €
            </button>
            <a href="/marketplace.php" class="btn btn-secondary btn-block" style="margin-top:8px;">Annuler</a>
        </form>
    </div>
</main>
</div>

<script>
document.querySelectorAll('.payment-option').forEach(el => {
    el.addEventListener('click', () => {
        document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        el.querySelector('input').checked = true;
        document.getElementById('mobileFields').style.display =
            el.querySelector('input').value === 'mobile_money' ? 'block' : 'none';
    });
});
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
