<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$content = trim($_POST['content'] ?? '');
if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Contenu requis']);
    exit;
}

$productId = !empty($_POST['product_id']) ? (int) $_POST['product_id'] : null;

$feed = new Kyrios\Feed($db);
$postId = $feed->createPost((int) $user['id'], $content, $productId);

echo json_encode(['success' => true, 'post_id' => $postId]);
