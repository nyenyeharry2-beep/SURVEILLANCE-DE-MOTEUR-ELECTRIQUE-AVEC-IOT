<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    exit;
}

$postId = (int) ($_GET['post_id'] ?? 0);
if (!$postId) {
    echo json_encode([]);
    exit;
}

$feed = new Kyrios\Feed($db);
echo json_encode($feed->getComments($postId));
