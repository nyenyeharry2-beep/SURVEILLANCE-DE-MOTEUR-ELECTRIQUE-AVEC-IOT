<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

final class AuthService
{
    public function __construct(private readonly PDO $db, private readonly array $config)
    {
    }

    public function register(array $input): array
    {
        $username = trim($input['username'] ?? '');
        $email = trim($input['email'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $password = $input['password'] ?? '';
        $displayName = trim($input['display_name'] ?? $username);

        if ($username === '' || $password === '') {
            Response::error('username and password are required', 422);
        }

        if ($email === '' && $phone === '') {
            Response::error('email or phone is required', 422);
        }

        if (strlen($password) < 8) {
            Response::error('password must be at least 8 characters', 422);
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO users (username, email, phone, password_hash) VALUES (:username, :email, :phone, :password_hash)'
            );
            $stmt->execute([
                'username' => $username,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'password_hash' => $hash,
            ]);
            $userId = (int) $this->db->lastInsertId();

            $profile = $this->db->prepare(
                'INSERT INTO profiles (user_id, display_name) VALUES (:user_id, :display_name)'
            );
            $profile->execute(['user_id' => $userId, 'display_name' => $displayName]);

            $this->db->commit();
        } catch (\PDOException $e) {
            $this->db->rollBack();
            if (str_contains($e->getMessage(), 'Duplicate')) {
                Response::error('username, email or phone already exists', 409);
            }
            throw $e;
        }

        $token = $this->issueToken($userId);
        return ['user' => $this->findUserById($userId), 'token' => $token];
    }

    public function login(array $input): array
    {
        $identifier = trim($input['identifier'] ?? '');
        $password = $input['password'] ?? '';

        if ($identifier === '' || $password === '') {
            Response::error('identifier and password are required', 422);
        }

        $stmt = $this->db->prepare(
            'SELECT * FROM users WHERE username = :id OR email = :id OR phone = :id LIMIT 1'
        );
        $stmt->execute(['id' => $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            Response::error('invalid credentials', 401);
        }

        if ($user['status'] !== 'active') {
            Response::error('account is not active', 403);
        }

        $this->db->prepare('UPDATE users SET last_login = NOW() WHERE id = :id')
            ->execute(['id' => $user['id']]);

        $token = $this->issueToken((int) $user['id']);
        unset($user['password_hash']);

        return ['user' => $this->hydrateUser($user), 'token' => $token];
    }

    public function authenticate(): array
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
            Response::error('missing authorization token', 401);
        }

        $tokenHash = hash('sha256', $matches[1]);
        $stmt = $this->db->prepare(
            'SELECT u.* FROM auth_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = :hash AND t.expires_at > NOW() LIMIT 1'
        );
        $stmt->execute(['hash' => $tokenHash]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('invalid or expired token', 401);
        }

        unset($user['password_hash']);
        return $this->hydrateUser($user);
    }

    private function issueToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $ttl = (int) $this->config['app']['token_ttl_hours'];

        $stmt = $this->db->prepare(
            'INSERT INTO auth_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL :hours HOUR))'
        );
        $stmt->execute(['user_id' => $userId, 'token_hash' => $hash, 'hours' => $ttl]);

        return $token;
    }

    private function findUserById(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();
        unset($user['password_hash']);
        return $this->hydrateUser($user);
    }

    private function hydrateUser(array $user): array
    {
        $stmt = $this->db->prepare('SELECT display_name, bio FROM profiles WHERE user_id = :id LIMIT 1');
        $stmt->execute(['id' => $user['id']]);
        $profile = $stmt->fetch() ?: ['display_name' => $user['username'], 'bio' => null];

        $counts = $this->db->prepare(
            'SELECT
                (SELECT COUNT(*) FROM posts WHERE user_id = :id) AS posts_count,
                (SELECT COUNT(*) FROM followers WHERE following_id = :id) AS followers_count,
                (SELECT COUNT(*) FROM followers WHERE follower_id = :id) AS following_count'
        );
        $counts->execute(['id' => $user['id']]);
        $stats = $counts->fetch();

        return array_merge($user, $profile, $stats ?: []);
    }
}
