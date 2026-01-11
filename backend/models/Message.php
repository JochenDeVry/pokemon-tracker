<?php
require_once __DIR__ . '/../config/Database.php';

class Message {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Send message
    public function send($senderId, $receiverId, $message) {
        $stmt = $this->conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, message) 
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$senderId, $receiverId, $message]);
    }

    // Get conversation between two users
    public function getConversation($userId, $friendId, $limit = 50) {
        $stmt = $this->conn->prepare("
            SELECT m.*, 
                   u.username as sender_username,
                   u.display_name as sender_display_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId, $limit]);
        $messages = $stmt->fetchAll();
        return array_reverse($messages); // Oldest first
    }

    // Get all conversations for a user (with last message)
    public function getConversations($userId) {
        $stmt = $this->conn->prepare("
            SELECT 
                u.id as friend_id,
                u.username as friend_username,
                u.display_name as friend_display_name,
                m.message as last_message,
                m.created_at as last_message_time,
                m.sender_id as last_sender_id,
                COALESCE(SUM(CASE WHEN m2.receiver_id = ? AND m2.is_read = 0 THEN 1 ELSE 0 END), 0) as unread_count
            FROM (
                SELECT DISTINCT
                    CASE 
                        WHEN sender_id = ? THEN receiver_id
                        ELSE sender_id
                    END as friend_id
                FROM messages
                WHERE sender_id = ? OR receiver_id = ?
            ) conversations
            JOIN users u ON u.id = conversations.friend_id
            LEFT JOIN messages m ON m.id = (
                SELECT m3.id 
                FROM messages m3
                WHERE (m3.sender_id = ? AND m3.receiver_id = u.id)
                   OR (m3.sender_id = u.id AND m3.receiver_id = ?)
                ORDER BY m3.created_at DESC
                LIMIT 1
            )
            LEFT JOIN messages m2 ON (m2.sender_id = u.id AND m2.receiver_id = ?)
            GROUP BY u.id, u.username, u.display_name, m.message, m.created_at, m.sender_id
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Mark messages as read
    public function markAsRead($userId, $friendId) {
        $stmt = $this->conn->prepare("
            UPDATE messages 
            SET is_read = 1 
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
        ");
        return $stmt->execute([$userId, $friendId]);
    }

    // Get unread count
    public function getUnreadCount($userId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as count 
            FROM messages 
            WHERE receiver_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    // Get new messages since timestamp
    public function getNewMessages($userId, $friendId, $sinceTimestamp) {
        $stmt = $this->conn->prepare("
            SELECT m.*, 
                   u.username as sender_username,
                   u.display_name as sender_display_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
                OR (m.sender_id = ? AND m.receiver_id = ?))
              AND m.created_at > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId, $sinceTimestamp]);
        return $stmt->fetchAll();
    }
}
