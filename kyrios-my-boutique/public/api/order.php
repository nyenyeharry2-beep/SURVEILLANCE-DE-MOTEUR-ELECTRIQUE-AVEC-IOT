<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = (int) ($input['product_id'] ?? 0);
$address = trim($input['address'] ?? '');

if (!$productId) {
    http_response_code(400);
    echo json_encode(['error' => 'Produit requis']);
    exit;
}

$productModel = new Kyrios\Product($db);
$product = $productModel->getById($productId);

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Produit introuvable']);
    exit;
}

$stmt = $db->prepare(
    'INSERT INTO orders (client_id, seller_id, product_id, quantity, total_price, delivery_address, status)
     VALUES (?, ?, ?, 1, ?, ?, "pending")'
);
$stmt->execute([
    $user['id'],
    $product['seller_id'],
    $productId,
    $product['price'],
    $address ?: null,
]);

echo json_encode(['success' => true, 'order_id' => (int) $db->lastInsertId()]);
