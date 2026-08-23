<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$postId = (int) ($input['post_id'] ?? 0);
$content = trim($input['content'] ?? '');

if (!$postId || empty($content)) {
    http_response_code(400);
    exit;
}

$feed = new Kyrios\Feed($db);
$commentId = $feed->addComment($postId, (int) $user['id'], $content);

echo json_encode([
    'success' => true,
    'comment' => [
        'id' => $commentId,
        'full_name' => $user['full_name'],
        'content' => $content,
    ],
]);
