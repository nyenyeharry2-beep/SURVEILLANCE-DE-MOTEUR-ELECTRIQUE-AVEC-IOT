<?php
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json');

$user = $auth->user();
if (!$user) {
    http_response_code(401);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$convId = (int) ($input['conversation_id'] ?? 0);
$content = trim($input['content'] ?? '');

if (!$convId || empty($content)) {
    http_response_code(400);
    exit;
}

$messaging = new Kyrios\Messaging($db);
$msgId = $messaging->sendMessage($convId, (int) $user['id'], $content);

echo json_encode(['success' => true, 'message_id' => $msgId]);
