<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

class Product
{
    public function __construct(private PDO $db) {}

    public function getAll(int $limit = 20, ?string $category = null): array
    {
        $sql = 'SELECT pr.*, u.full_name AS seller_name, u.shop_name, u.is_verified
                FROM products pr
                JOIN users u ON u.id = pr.seller_id
                WHERE pr.is_active = 1';
        $params = [];

        if ($category) {
            $sql .= ' AND pr.category = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY pr.created_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->db->prepare($sql);
        foreach ($params as $i => $param) {
            $type = is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($i + 1, $param, $type);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBySeller(int $sellerId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM products WHERE seller_id = ? AND is_active = 1 ORDER BY created_at DESC'
        );
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }

    public function create(int $sellerId, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO products (seller_id, title, description, price, category, stock, image_url)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $sellerId,
            $data['title'],
            $data['description'] ?? '',
            $data['price'],
            $data['category'] ?? 'general',
            $data['stock'] ?? 1,
            $data['image_url'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT pr.*, u.full_name AS seller_name, u.shop_name, u.id AS seller_user_id
             FROM products pr
             JOIN users u ON u.id = pr.seller_id
             WHERE pr.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
