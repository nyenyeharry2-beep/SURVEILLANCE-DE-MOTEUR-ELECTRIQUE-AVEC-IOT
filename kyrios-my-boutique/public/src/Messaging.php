<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

class Messaging
{
    public function __construct(private PDO $db) {}

    public function getConversations(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.updated_at,
                    (SELECT content FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                    (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.sender_id != ? AND m.is_read = 0) AS unread_count,
                    u.id AS other_user_id, u.full_name, u.avatar_url, u.role
             FROM conversations c
             JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = ?
             JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id != ?
             JOIN users u ON u.id = cp2.user_id
             ORDER BY c.updated_at DESC'
        );
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getOrCreateConversation(int $userId1, int $userId2): int
    {
        $stmt = $this->db->prepare(
            'SELECT c.id FROM conversations c
             JOIN conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = ?
             JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = ?'
        );
        $stmt->execute([$userId1, $userId2]);
        $existing = $stmt->fetch();

        if ($existing) {
            return (int) $existing['id'];
        }

        $this->db->beginTransaction();
        try {
            $this->db->exec('INSERT INTO conversations (id) VALUES (NULL)');
            $convId = (int) $this->db->lastInsertId();

            $ins = $this->db->prepare('INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)');
            $ins->execute([$convId, $userId1]);
            $ins->execute([$convId, $userId2]);

            $this->db->commit();
            return $convId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getMessages(int $conversationId, int $userId): array
    {
        $check = $this->db->prepare(
            'SELECT id FROM conversation_participants WHERE conversation_id = ? AND user_id = ?'
        );
        $check->execute([$conversationId, $userId]);
        if (!$check->fetch()) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT m.*, u.full_name AS sender_name, u.avatar_url AS sender_avatar
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?
             ORDER BY m.created_at ASC'
        );
        $stmt->execute([$conversationId]);

        $markRead = $this->db->prepare(
            'UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?'
        );
        $markRead->execute([$conversationId, $userId]);

        return $stmt->fetchAll();
    }

    public function sendMessage(int $conversationId, int $senderId, string $content): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)'
        );
        $stmt->execute([$conversationId, $senderId, trim($content)]);

        $update = $this->db->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = ?');
        $update->execute([$conversationId]);

        return (int) $this->db->lastInsertId();
    }

    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM messages m
             JOIN conversation_participants cp ON cp.conversation_id = m.conversation_id AND cp.user_id = ?
             WHERE m.sender_id != ? AND m.is_read = 0'
        );
        $stmt->execute([$userId, $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function searchUsers(string $query, int $excludeUserId, int $limit = 10): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, full_name, avatar_url, role, shop_name
             FROM users
             WHERE id != ? AND is_active = 1
               AND (full_name LIKE ? OR email LIKE ? OR shop_name LIKE ?)
             LIMIT ?'
        );
        $like = '%' . $query . '%';
        $stmt->bindValue(1, $excludeUserId, PDO::PARAM_INT);
        $stmt->bindValue(2, $like);
        $stmt->bindValue(3, $like);
        $stmt->bindValue(4, $like);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
