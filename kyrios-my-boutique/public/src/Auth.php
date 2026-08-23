<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

class Auth
{
    public function __construct(private PDO $db) {}

    public function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function user(): ?array
    {
        $this->startSession();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function login(int $userId): void
    {
        $this->startSession();
        $_SESSION['user_id'] = $userId;
    }

    public function logout(): void
    {
        $this->startSession();
        unset($_SESSION['user_id']);
        session_destroy();
    }

    public function register(array $data): array
    {
        $allowedRoles = ['client', 'vendeur', 'livreur'];
        $role = in_array($data['role'] ?? '', $allowedRoles, true) ? $data['role'] : 'client';

        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Cet email est déjà utilisé.'];
        }

        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (email, password_hash, full_name, role, phone, shop_name, shop_description)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['email'],
            $passwordHash,
            $data['full_name'],
            $role,
            $data['phone'] ?? null,
            $role === 'vendeur' ? ($data['shop_name'] ?? null) : null,
            $role === 'vendeur' ? ($data['shop_description'] ?? null) : null,
        ]);

        $userId = (int) $this->db->lastInsertId();
        $this->login($userId);

        return ['success' => true, 'user_id' => $userId];
    }

    public function loginWithCredentials(string $email, string $password): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'] ?? '')) {
            return ['success' => false, 'error' => 'Email ou mot de passe incorrect.'];
        }

        $this->login((int) $user['id']);
        return ['success' => true];
    }

    public function loginOrRegisterGoogle(array $googleUser, string $role = 'client'): array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE google_id = ? OR email = ?');
        $stmt->execute([$googleUser['sub'], $googleUser['email']]);
        $existing = $stmt->fetch();

        if ($existing) {
            if (empty($existing['google_id'])) {
                $update = $this->db->prepare('UPDATE users SET google_id = ?, avatar_url = COALESCE(avatar_url, ?) WHERE id = ?');
                $update->execute([$googleUser['sub'], $googleUser['picture'] ?? null, $existing['id']]);
            }
            $this->login((int) $existing['id']);
            return ['success' => true, 'user_id' => (int) $existing['id'], 'new' => false];
        }

        $allowedRoles = ['client', 'vendeur', 'livreur'];
        $role = in_array($role, $allowedRoles, true) ? $role : 'client';

        $stmt = $this->db->prepare(
            'INSERT INTO users (email, google_id, full_name, avatar_url, role)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $googleUser['email'],
            $googleUser['sub'],
            $googleUser['name'],
            $googleUser['picture'] ?? null,
            $role,
        ]);

        $userId = (int) $this->db->lastInsertId();
        $this->login($userId);

        return ['success' => true, 'user_id' => $userId, 'new' => true];
    }

    public function requireAuth(): array
    {
        $user = $this->user();
        if (!$user) {
            header('Location: /login.php');
            exit;
        }
        return $user;
    }

    public function roleLabel(string $role): string
    {
        return match ($role) {
            'vendeur' => 'Vendeur',
            'livreur' => 'Livreur',
            default => 'Client',
        };
    }
}
