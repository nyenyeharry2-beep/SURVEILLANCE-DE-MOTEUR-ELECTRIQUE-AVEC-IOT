<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

class Feed
{
    public function __construct(private PDO $db) {}

    public function getPosts(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*, u.full_name, u.avatar_url, u.role, u.shop_name, u.is_verified,
                    pr.title AS product_title, pr.price AS product_price, pr.image_url AS product_image,
                    (SELECT COUNT(*) FROM post_likes pl WHERE pl.post_id = p.id) AS likes_count,
                    (SELECT COUNT(*) FROM post_comments pc WHERE pc.post_id = p.id) AS comments_count
             FROM posts p
             JOIN users u ON u.id = p.user_id
             LEFT JOIN products pr ON pr.id = p.product_id
             ORDER BY p.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createPost(int $userId, string $content, ?int $productId = null, ?string $imageUrl = null): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO posts (user_id, content, product_id, image_url) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $content, $productId, $imageUrl]);
        return (int) $this->db->lastInsertId();
    }

    public function toggleLike(int $postId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?');
        $stmt->execute([$postId, $userId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $del = $this->db->prepare('DELETE FROM post_likes WHERE post_id = ? AND user_id = ?');
            $del->execute([$postId, $userId]);
            return false;
        }

        $ins = $this->db->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)');
        $ins->execute([$postId, $userId]);
        return true;
    }

    public function userLiked(int $postId, int $userId): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?');
        $stmt->execute([$postId, $userId]);
        return (bool) $stmt->fetch();
    }

    public function getComments(int $postId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, u.full_name, u.avatar_url
             FROM post_comments c
             JOIN users u ON u.id = c.user_id
             WHERE c.post_id = ?
             ORDER BY c.created_at ASC'
        );
        $stmt->execute([$postId]);
        return $stmt->fetchAll();
    }

    public function addComment(int $postId, int $userId, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO post_comments (post_id, user_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$postId, $userId, $content]);
        return (int) $this->db->lastInsertId();
    }

    public function getTrendingProducts(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.*, u.full_name AS seller_name, u.shop_name
             FROM products pr
             JOIN users u ON u.id = pr.seller_id
             WHERE pr.is_active = 1
             ORDER BY pr.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSuggestedSellers(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT u.id, u.full_name, u.shop_name, u.avatar_url, u.bio, u.is_verified,
                    (SELECT COUNT(*) FROM products p WHERE p.seller_id = u.id) AS product_count
             FROM users u
             WHERE u.role = "vendeur" AND u.is_active = 1
             ORDER BY u.is_verified DESC, product_count DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
