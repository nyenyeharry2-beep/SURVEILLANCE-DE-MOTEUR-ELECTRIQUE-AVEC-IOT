<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

function jsonResponse($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError(string $msg, int $code = 400): void {
    jsonResponse(['error' => $msg], $code);
}

function getBody(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

function uuid(): string {
    return bin2hex(random_bytes(16));
}

function createToken(string $userId, string $username): string {
    $header = base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64url_encode(json_encode([
        'id' => $userId, 'username' => $username,
        'iat' => time(), 'exp' => time() + 86400 * 30
    ]));
    $sig = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function verifyToken(?string $token): ?array {
    if (!$token) return null;
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $payload, $sig] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode(base64url_decode($payload), true);
    if (!$data || ($data['exp'] ?? 0) < time()) return null;
    return $data;
}

function base64url_encode(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

function authUser(): array {
    $token = null;
    if (isset($_SERVER['HTTP_AUTHORIZATION']) && str_starts_with($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ')) {
        $token = substr($_SERVER['HTTP_AUTHORIZATION'], 7);
    }
    $user = verifyToken($token);
    if (!$user) jsonError('Token invalide', 401);
    return $user;
}

function formatUser(array $u): array {
    return [
        'id' => $u['id'], 'email' => $u['email'], 'username' => $u['username'],
        'displayName' => $u['display_name'], 'avatarUrl' => $u['avatar_url'],
        'bio' => $u['bio'] ?? '', 'isOnline' => (bool)($u['is_online'] ?? 0),
    ];
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = preg_replace('#^/api/#', '', $uri);
$uri = preg_replace('#^/infinityfree/api/#', '', $uri);
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Health
    if ($uri === 'health' && $method === 'GET') {
        jsonResponse(['status' => 'ok', 'app' => 'KYRIOS', 'host' => 'InfinityFree']);
    }

    // Login
    if ($uri === 'auth/login' && $method === 'POST') {
        $body = getBody();
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$body['email'] ?? '']);
        $user = $stmt->fetch();
        if (!$user || !password_verify($body['password'] ?? '', $user['password_hash'])) {
            jsonError('Identifiants invalides', 401);
        }
        db()->prepare('UPDATE users SET is_online = 1, last_seen = NOW() WHERE id = ?')->execute([$user['id']]);
        jsonResponse(['token' => createToken($user['id'], $user['username']), 'user' => formatUser($user)]);
    }

    // Register
    if ($uri === 'auth/register' && $method === 'POST') {
        $body = getBody();
        $id = uuid();
        $hash = password_hash($body['password'] ?? '', PASSWORD_BCRYPT);
        db()->prepare('INSERT INTO users (id, email, password_hash, username, display_name) VALUES (?, ?, ?, ?, ?)')
            ->execute([$id, $body['email'], $hash, $body['username'], $body['displayName'] ?? $body['username']]);
        jsonResponse(['token' => createToken($id, $body['username']), 'user' => ['id' => $id, 'email' => $body['email'], 'username' => $body['username'], 'displayName' => $body['displayName'] ?? $body['username']]]);
    }

    // Me
    if ($uri === 'auth/me' && $method === 'GET') {
        $auth = authUser();
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$auth['id']]);
        jsonResponse(formatUser($stmt->fetch()));
    }

    // Conversations
    if ($uri === 'conversations' && $method === 'GET') {
        $auth = authUser();
        $stmt = db()->prepare("
            SELECT c.*,
                (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_time,
                (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.created_at > COALESCE(cm.last_read_at, '1970-01-01') AND m.sender_id != ?) as unread
            FROM conversations c
            JOIN conversation_members cm ON c.id = cm.conversation_id
            WHERE cm.user_id = ? ORDER BY last_time DESC
        ");
        $stmt->execute([$auth['id'], $auth['id']]);
        $rows = array_map(fn($c) => [
            'id' => $c['id'], 'name' => $c['name'] ?? 'Chat', 'avatar' => $c['avatar_url'],
            'isGroup' => (bool)$c['is_group'], 'lastMessage' => $c['last_message'],
            'time' => $c['last_time'], 'unread' => (int)$c['unread'],
        ], $stmt->fetchAll());
        jsonResponse($rows);
    }

    // Messages
    if (preg_match('#^conversations/([^/]+)/messages$#', $uri, $m)) {
        $auth = authUser();
        $convId = $m[1];
        if ($method === 'GET') {
            $stmt = db()->prepare('SELECT m.*, u.display_name, u.avatar_url FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.created_at ASC');
            $stmt->execute([$convId]);
            $msgs = [];
            foreach ($stmt->fetchAll() as $msg) {
                $media = db()->prepare('SELECT media_url FROM message_media WHERE message_id = ? ORDER BY sort_order')->execute([$msg['id']]) ? [] : [];
                $mStmt = db()->prepare('SELECT media_url FROM message_media WHERE message_id = ? ORDER BY sort_order');
                $mStmt->execute([$msg['id']]);
                $images = array_column($mStmt->fetchAll(), 'media_url');
                $rStmt = db()->prepare('SELECT emoji, COUNT(*) as count FROM message_reactions WHERE message_id = ? GROUP BY emoji');
                $rStmt->execute([$msg['id']]);
                $reactions = array_map(fn($r) => ['emoji' => $r['emoji'], 'count' => (int)$r['count']], $rStmt->fetchAll());
                $msgs[] = [
                    'id' => $msg['id'], 'senderId' => $msg['sender_id'], 'senderName' => $msg['display_name'],
                    'senderAvatar' => $msg['avatar_url'], 'text' => $msg['content'],
                    'images' => $images ?: null, 'reactions' => $reactions ?: null,
                    'time' => $msg['created_at'], 'isOwn' => $msg['sender_id'] === $auth['id'],
                ];
            }
            db()->prepare('UPDATE conversation_members SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?')->execute([$convId, $auth['id']]);
            jsonResponse($msgs);
        }
        if ($method === 'POST') {
            $body = getBody();
            $id = uuid();
            db()->prepare('INSERT INTO messages (id, conversation_id, sender_id, content) VALUES (?, ?, ?, ?)')->execute([$id, $convId, $auth['id'], $body['content'] ?? '']);
            $uStmt = db()->prepare('SELECT display_name, avatar_url FROM users WHERE id = ?');
            $uStmt->execute([$auth['id']]);
            $u = $uStmt->fetch();
            jsonResponse(['id' => $id, 'senderId' => $auth['id'], 'senderName' => $u['display_name'], 'senderAvatar' => $u['avatar_url'], 'text' => $body['content'], 'time' => date('c'), 'isOwn' => true]);
        }
    }

    // Posts
    if ($uri === 'posts' && $method === 'GET') {
        authUser();
        $stmt = db()->query("SELECT p.*, u.display_name, u.username, u.avatar_url,
            (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as likes,
            (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comments
            FROM posts p JOIN users u ON p.author_id = u.id ORDER BY p.created_at DESC");
        jsonResponse(array_map(fn($p) => [
            'id' => $p['id'], 'author' => $p['display_name'], 'handle' => '@'.$p['username'],
            'avatar' => $p['avatar_url'], 'caption' => $p['caption'], 'image' => $p['image_url'],
            'likes' => (int)$p['likes'], 'comments' => (int)$p['comments'], 'time' => $p['created_at'],
        ], $stmt->fetchAll()));
    }

    // Stories
    if ($uri === 'stories' && $method === 'GET') {
        authUser();
        $stmt = db()->query("SELECT s.*, u.display_name, u.avatar_url FROM stories s JOIN users u ON s.user_id = u.id WHERE s.expires_at > NOW() ORDER BY s.created_at DESC");
        jsonResponse(array_map(fn($s) => [
            'id' => $s['id'], 'userId' => $s['user_id'], 'name' => $s['display_name'],
            'avatar' => $s['avatar_url'], 'mediaUrl' => $s['media_url'], 'isLive' => (bool)$s['is_live'],
        ], $stmt->fetchAll()));
    }

    // Communities
    if ($uri === 'communities' && $method === 'GET') {
        authUser();
        $stmt = db()->query('SELECT c.*, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id) as members FROM communities c');
        jsonResponse(array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name'], 'icon' => $c['icon'], 'color' => $c['color'], 'members' => (int)$c['members']], $stmt->fetchAll()));
    }

    // Calls
    if ($uri === 'calls' && $method === 'GET') {
        $auth = authUser();
        $stmt = db()->prepare("SELECT c.*, u.display_name, u.avatar_url FROM calls c JOIN users u ON (c.caller_id = u.id AND c.caller_id != ?) OR (c.callee_id = u.id AND c.callee_id != ?) WHERE c.caller_id = ? OR c.callee_id = ? ORDER BY c.started_at DESC LIMIT 20");
        $stmt->execute([$auth['id'], $auth['id'], $auth['id'], $auth['id']]);
        jsonResponse(array_map(fn($c) => [
            'id' => $c['id'], 'name' => $c['display_name'], 'avatar' => $c['avatar_url'],
            'type' => $c['direction'], 'time' => $c['started_at'],
            'duration' => $c['duration_seconds'] ? sprintf('%d:%02d', intdiv($c['duration_seconds'], 60), $c['duration_seconds'] % 60) : '',
        ], $stmt->fetchAll()));
    }

    // User profile
    if (preg_match('#^users/([^/]+)$#', $uri, $m) && $method === 'GET') {
        authUser();
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$m[1]]);
        $u = $stmt->fetch();
        if (!$u) jsonError('Utilisateur introuvable', 404);
        $followers = db()->prepare('SELECT COUNT(*) as c FROM follows WHERE following_id = ?')->execute([$m[1]]) ? 0 : 0;
        $fStmt = db()->prepare('SELECT COUNT(*) as c FROM follows WHERE following_id = ?'); $fStmt->execute([$m[1]]); $followers = $fStmt->fetch()['c'];
        $foStmt = db()->prepare('SELECT COUNT(*) as c FROM follows WHERE follower_id = ?'); $foStmt->execute([$m[1]]); $following = $foStmt->fetch()['c'];
        $pStmt = db()->prepare('SELECT COUNT(*) as c FROM posts WHERE author_id = ?'); $pStmt->execute([$m[1]]); $posts = $pStmt->fetch()['c'];
        jsonResponse([...formatUser($u), 'stats' => ['followers' => (int)$followers, 'following' => (int)$following, 'posts' => (int)$posts]]);
    }

    jsonError('Route introuvable', 404);

} catch (PDOException $e) {
    jsonError('Erreur base de données: vérifiez config.php', 500);
} catch (Exception $e) {
    jsonError($e->getMessage(), 500);
}
