<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$postId = (int) ($input['post_id'] ?? $_POST['post_id'] ?? 0);

if (!$postId) {
    http_response_code(400);
    exit;
}

$feed = new Kyrios\Feed($db);
$liked = $feed->toggleLike($postId, (int) $user['id']);

$stmt = $db->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
$stmt->execute([$postId]);
$count = (int) $stmt->fetchColumn();

echo json_encode(['liked' => $liked, 'count' => $count]);
