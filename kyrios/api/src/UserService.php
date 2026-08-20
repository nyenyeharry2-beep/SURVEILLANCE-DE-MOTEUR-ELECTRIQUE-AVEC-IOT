<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

final class UserService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.profile_photo, p.display_name, p.bio
             FROM users u
             JOIN profiles p ON p.user_id = u.id
             WHERE u.status = "active"
               AND (u.username LIKE :q OR p.display_name LIKE :q OR u.email LIKE :q)
             ORDER BY u.username ASC
             LIMIT :limit'
        );
        $like = '%' . $query . '%';
        $stmt->bindValue('q', $like);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getProfile(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.username, u.profile_photo, u.created_at,
                    p.display_name, p.bio,
                    (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS posts_count,
                    (SELECT COUNT(*) FROM followers WHERE following_id = u.id) AS followers_count,
                    (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) AS following_count
             FROM users u
             JOIN profiles p ON p.user_id = u.id
             WHERE u.id = :id AND u.status = "active"
             LIMIT 1'
        );
        $stmt->execute(['id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::error('user not found', 404);
        }

        return $user;
    }

    public function updateProfile(int $userId, array $input): array
    {
        $displayName = trim($input['display_name'] ?? '');
        $bio = trim($input['bio'] ?? '');

        if ($displayName !== '') {
            $this->db->prepare('UPDATE profiles SET display_name = :name WHERE user_id = :id')
                ->execute(['name' => $displayName, 'id' => $userId]);
        }

        if (array_key_exists('bio', $input)) {
            $this->db->prepare('UPDATE profiles SET bio = :bio WHERE user_id = :id')
                ->execute(['bio' => $bio !== '' ? $bio : null, 'id' => $userId]);
        }

        return $this->getProfile($userId);
    }
}
