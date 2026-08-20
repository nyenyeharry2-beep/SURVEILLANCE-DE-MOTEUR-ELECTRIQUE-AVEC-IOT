<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Response.php';
require_once __DIR__ . '/../src/AuthService.php';
require_once __DIR__ . '/../src/UserService.php';
require_once __DIR__ . '/../src/MessageService.php';
require_once __DIR__ . '/../src/PostService.php';

use Kyrios\AuthService;
use Kyrios\Database;
use Kyrios\MessageService;
use Kyrios\PostService;
use Kyrios\Response;
use Kyrios\UserService;

$config = require __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    Response::success();
}

$db = Database::connection();
$auth = new AuthService($db, $config);
$users = new UserService($db);
$messages = new MessageService($db);
$posts = new PostService($db);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$path = preg_replace('#^/kyrios/api/public#', '', $path) ?: '/';
$path = rtrim($path, '/') ?: '/';

$body = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];

try {
    if ($path === '/' && $method === 'GET') {
        Response::success(['app' => $config['app']['name'], 'version' => '1.0.0-mvp']);
    }

    if ($path === '/auth/register' && $method === 'POST') {
        Response::success($auth->register($body), 201);
    }

    if ($path === '/auth/login' && $method === 'POST') {
        Response::success($auth->login($body));
    }

    if ($path === '/users/search' && $method === 'GET') {
        $current = $auth->authenticate();
        $query = $_GET['q'] ?? '';
        Response::success(['users' => $users->search($query)]);
    }

    if (preg_match('#^/users/(\d+)$#', $path, $m) && $method === 'GET') {
        $auth->authenticate();
        Response::success(['user' => $users->getProfile((int) $m[1])]);
    }

    if ($path === '/users/me' && $method === 'GET') {
        $current = $auth->authenticate();
        Response::success(['user' => $users->getProfile((int) $current['id'])]);
    }

    if ($path === '/users/me' && in_array($method, ['PUT', 'PATCH'], true)) {
        $current = $auth->authenticate();
        Response::success(['user' => $users->updateProfile((int) $current['id'], $body)]);
    }

    if ($path === '/conversations' && $method === 'GET') {
        $current = $auth->authenticate();
        Response::success(['conversations' => $messages->listConversations((int) $current['id'])]);
    }

    if ($path === '/conversations/direct' && $method === 'POST') {
        $current = $auth->authenticate();
        $otherId = (int) ($body['user_id'] ?? 0);
        if ($otherId <= 0) {
            Response::error('user_id is required', 422);
        }
        Response::success($messages->getOrCreateDirectConversation((int) $current['id'], $otherId), 201);
    }

    if (preg_match('#^/conversations/(\d+)/messages$#', $path, $m)) {
        $conversationId = (int) $m[1];
        $current = $auth->authenticate();

        if ($method === 'GET') {
            Response::success([
                'messages' => $messages->listMessages((int) $current['id'], $conversationId),
            ]);
        }

        if ($method === 'POST') {
            Response::success([
                'message' => $messages->sendMessage((int) $current['id'], $conversationId, $body),
            ], 201);
        }
    }

    if ($path === '/posts' && $method === 'GET') {
        $auth->authenticate();
        Response::success(['posts' => $posts->feed()]);
    }

    if ($path === '/posts' && $method === 'POST') {
        $current = $auth->authenticate();
        Response::success(['post' => $posts->create((int) $current['id'], $body)], 201);
    }

    if (preg_match('#^/posts/(\d+)/like$#', $path, $m) && $method === 'POST') {
        $current = $auth->authenticate();
        Response::success($posts->like((int) $current['id'], (int) $m[1]), 201);
    }

    if (preg_match('#^/posts/(\d+)/comments$#', $path, $m)) {
        $postId = (int) $m[1];
        if ($method === 'GET') {
            $auth->authenticate();
            Response::success(['comments' => $posts->listComments($postId)]);
        }
        if ($method === 'POST') {
            $current = $auth->authenticate();
            Response::success(['comment' => $posts->comment((int) $current['id'], $postId, $body)], 201);
        }
    }

    Response::error('route not found', 404);
} catch (Throwable $e) {
    if ($config['app']['debug']) {
        Response::error($e->getMessage(), 500, ['trace' => $e->getTraceAsString()]);
    }
    Response::error('internal server error', 500);
}
