<?php

declare(strict_types=1);

namespace Kyrios;

use PDO;

final class MessageService
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function listConversations(int $userId): array
    {
        $stmt = $this->db->prepare(
            'SELECT c.id, c.is_group, c.title, c.updated_at,
                    (SELECT message FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
                    (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC LIMIT 1) AS last_message_at,
                    (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.is_read = 0 AND m.sender_id != :uid) AS unread_count
             FROM conversations c
             JOIN conversation_members cm ON cm.conversation_id = c.id
             WHERE cm.user_id = :uid
             ORDER BY c.updated_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function getOrCreateDirectConversation(int $userId, int $otherUserId): array
    {
        if ($userId === $otherUserId) {
            Response::error('cannot create conversation with yourself', 422);
        }

        $stmt = $this->db->prepare(
            'SELECT c.id FROM conversations c
             JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = :u1
             JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id = :u2
             WHERE c.is_group = 0
             LIMIT 1'
        );
        $stmt->execute(['u1' => $userId, 'u2' => $otherUserId]);
        $existing = $stmt->fetch();

        if ($existing) {
            return ['conversation_id' => (int) $existing['id']];
        }

        $this->db->beginTransaction();
        try {
            $this->db->exec('INSERT INTO conversations (is_group) VALUES (0)');
            $conversationId = (int) $this->db->lastInsertId();

            $member = $this->db->prepare(
                'INSERT INTO conversation_members (conversation_id, user_id) VALUES (:cid, :uid)'
            );
            $member->execute(['cid' => $conversationId, 'uid' => $userId]);
            $member->execute(['cid' => $conversationId, 'uid' => $otherUserId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['conversation_id' => $conversationId];
    }

    public function listMessages(int $userId, int $conversationId, int $limit = 50): array
    {
        $this->assertMember($userId, $conversationId);

        $stmt = $this->db->prepare(
            'SELECT m.id, m.sender_id, u.username AS sender_username, m.message, m.message_type,
                    m.media_url, m.is_read, m.created_at
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = :cid
             ORDER BY m.created_at ASC
             LIMIT :limit'
        );
        $stmt->bindValue('cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $this->db->prepare(
            'UPDATE messages SET is_read = 1
             WHERE conversation_id = :cid AND sender_id != :uid AND is_read = 0'
        )->execute(['cid' => $conversationId, 'uid' => $userId]);

        return $stmt->fetchAll();
    }

    public function sendMessage(int $userId, int $conversationId, array $input): array
    {
        $this->assertMember($userId, $conversationId);

        $message = trim($input['message'] ?? '');
        $type = $input['message_type'] ?? 'text';

        if ($message === '' && ($input['media_url'] ?? '') === '') {
            Response::error('message or media_url is required', 422);
        }

        $stmt = $this->db->prepare(
            'INSERT INTO messages (conversation_id, sender_id, message, message_type, media_url)
             VALUES (:cid, :uid, :message, :type, :media_url)'
        );
        $stmt->execute([
            'cid' => $conversationId,
            'uid' => $userId,
            'message' => $message !== '' ? $message : null,
            'type' => $type,
            'media_url' => $input['media_url'] ?? null,
        ]);

        $messageId = (int) $this->db->lastInsertId();
        $this->db->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $conversationId]);

        $fetch = $this->db->prepare('SELECT * FROM messages WHERE id = :id LIMIT 1');
        $fetch->execute(['id' => $messageId]);
        return $fetch->fetch();
    }

    private function assertMember(int $userId, int $conversationId): void
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM conversation_members WHERE conversation_id = :cid AND user_id = :uid LIMIT 1'
        );
        $stmt->execute(['cid' => $conversationId, 'uid' => $userId]);
        if (!$stmt->fetch()) {
            Response::error('conversation not found or access denied', 403);
        }
    }
}
