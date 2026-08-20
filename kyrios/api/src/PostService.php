<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

final class PostService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function feed(int $limit = 30): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.id, p.user_id, u.username, pr.display_name, u.profile_photo,
                    p.content, p.media_url, p.visibility, p.created_at,
                    (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
                    (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
             FROM posts p
             JOIN users u ON u.id = p.user_id
             JOIN profiles pr ON pr.user_id = u.id
             WHERE p.visibility = "public" AND u.status = "active"
             ORDER BY p.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(int $userId, array $input): array
    {
        $content = trim($input['content'] ?? '');
        if ($content === '') {
            Response::error('content is required', 422);
        }

        $visibility = $input['visibility'] ?? 'public';
        if (!in_array($visibility, ['public', 'followers', 'private'], true)) {
            Response::error('invalid visibility', 422);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO posts (user_id, content, media_url, visibility)
             VALUES (:uid, :content, :media_url, :visibility)'
        );
        $stmt->execute([
            'uid' => $userId,
            'content' => $content,
            'media_url' => $input['media_url'] ?? null,
            'visibility' => $visibility,
        ]);

        $postId = (int) $this->db->lastInsertId();
        return $this->findById($postId);
    }

    public function like(int $userId, int $postId): array
    {
        $this->findById($postId);

        try {
            $this->db->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (:pid, :uid)')
                ->execute(['pid' => $postId, 'uid' => $userId]);
        } catch (\PDOException $e) {
            if (str_contains($e->getMessage(), 'Duplicate')) {
                Response::error('already liked', 409);
            }
            throw $e;
        }

        return ['post_id' => $postId, 'liked' => true];
    }

    public function comment(int $userId, int $postId, array $input): array
    {
        $this->findById($postId);
        $content = trim($input['content'] ?? '');
        if ($content === '') {
            Response::error('content is required', 422);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO comments (post_id, user_id, content) VALUES (:pid, :uid, :content)'
        );
        $stmt->execute(['pid' => $postId, 'uid' => $userId, 'content' => $content]);

        $commentId = (int) $this->db->lastInsertId();
        $fetch = $this->db->prepare(
            'SELECT c.id, c.content, c.created_at, u.username, pr.display_name
             FROM comments c
             JOIN users u ON u.id = c.user_id
             JOIN profiles pr ON pr.user_id = u.id
             WHERE c.id = :id LIMIT 1'
        );
        $fetch->execute(['id' => $commentId]);
        return $fetch->fetch();
    }

    public function listComments(int $postId): array
    {
        $this->findById($postId);
        $stmt = $this->db->prepare(
            'SELECT c.id, c.content, c.created_at, u.username, pr.display_name, u.profile_photo
             FROM comments c
             JOIN users u ON u.id = c.user_id
             JOIN profiles pr ON pr.user_id = u.id
             WHERE c.post_id = :pid
             ORDER BY c.created_at ASC'
        );
        $stmt->execute(['pid' => $postId]);
        return $stmt->fetchAll();
    }

    private function findById(int $postId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $postId]);
        $post = $stmt->fetch();
        if (!$post) {
            Response::error('post not found', 404);
        }
        return $post;
    }
}
