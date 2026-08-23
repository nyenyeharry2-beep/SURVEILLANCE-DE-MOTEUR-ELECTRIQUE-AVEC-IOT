<?php

namespace Kyrios;

use PDO;

class Payment
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function createOrder($clientId, $productId, $data)
    {
        $productModel = new Product($this->db);
        $product = $productModel->getById($productId);

        if (!$product || $product['stock'] < 1) {
            return ['success' => false, 'error' => 'Produit indisponible.'];
        }

        $method = in_array($data['payment_method'] ?? '', ['mobile_money', 'cash', 'stripe'], true)
            ? $data['payment_method'] : 'cash';

        $paymentStatus = ($method === 'cash') ? 'pending' : 'pending';
        $reference = strtoupper(substr($method, 0, 2)) . date('Ymd') . rand(1000, 9999);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO orders (client_id, seller_id, product_id, quantity, total_price,
                 delivery_address, phone_number, payment_method, payment_status, payment_reference, status)
                 VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, "pending")'
            );
            $stmt->execute([
                $clientId,
                $product['seller_id'],
                $productId,
                $product['price'],
                $data['address'] ?? null,
                $data['phone'] ?? null,
                $method,
                $paymentStatus,
                $reference,
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $pay = $this->db->prepare(
                'INSERT INTO payments (order_id, amount, method, status, reference, phone_number, operator)
                 VALUES (?, ?, ?, "pending", ?, ?, ?)'
            );
            $pay->execute([
                $orderId,
                $product['price'],
                $method,
                $reference,
                $data['phone'] ?? null,
                $data['operator'] ?? null,
            ]);

            $this->db->prepare('UPDATE products SET stock = stock - 1 WHERE id = ? AND stock > 0')
                ->execute([$productId]);

            $this->db->commit();

            return [
                'success' => true,
                'order_id' => $orderId,
                'reference' => $reference,
                'method' => $method,
                'amount' => $product['price'],
                'product' => $product,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Erreur lors de la commande.'];
        }
    }

    public function getClientOrders($clientId)
    {
        $stmt = $this->db->prepare(
            'SELECT o.*, pr.title AS product_title, pr.image_url AS product_image,
                    s.shop_name, s.full_name AS seller_name
             FROM orders o
             JOIN products pr ON pr.id = o.product_id
             JOIN users s ON s.id = o.seller_id
             WHERE o.client_id = ?
             ORDER BY o.created_at DESC'
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    public function markPaid($orderId, $sellerId)
    {
        $stmt = $this->db->prepare(
            'UPDATE orders SET payment_status = "paid", status = "confirmed"
             WHERE id = ? AND seller_id = ?'
        );
        $stmt->execute([$orderId, $sellerId]);
        return $stmt->rowCount() > 0;
    }

    public function createStripeSession($orderId, $amount, $productTitle, $config)
    {
        if (empty($config['stripe']['secret_key'])) {
            return ['success' => false, 'error' => 'Stripe non configuré.'];
        }

        $params = [
            'mode' => 'payment',
            'success_url' => $config['app_url'] . '/orders.php?paid=1&order=' . $orderId,
            'cancel_url' => $config['app_url'] . '/checkout.php?cancelled=1',
            'line_items[0][price_data][currency]' => 'eur',
            'line_items[0][price_data][product_data][name]' => $productTitle,
            'line_items[0][price_data][unit_amount]' => (int) round($amount * 100),
            'line_items[0][quantity]' => 1,
            'metadata[order_id]' => $orderId,
        ];

        $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $config['stripe']['secret_key'] . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (empty($data['url'])) {
            return ['success' => false, 'error' => 'Erreur Stripe.'];
        }

        return ['success' => true, 'url' => $data['url'], 'session_id' => $data['id'] ?? ''];
    }
}
