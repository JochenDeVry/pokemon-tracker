<?php
require_once __DIR__ . '/../config/Database.php';

class Friendship {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // Send friend request
    public function sendRequest($userId, $friendId) {
        // Check if friendship already exists
        $stmt = $this->conn->prepare("
            SELECT id, status FROM friendships 
            WHERE (user_id = ? AND friend_id = ?) 
            OR (user_id = ? AND friend_id = ?)
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if ($existing['status'] === 'declined') {
                // Allow re-sending after decline
                $updateStmt = $this->conn->prepare("
                    UPDATE friendships 
                    SET status = 'pending', updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");
                return $updateStmt->execute([$existing['id']]);
            }
            return false; // Already exists
        }

        $stmt = $this->conn->prepare("
            INSERT INTO friendships (user_id, friend_id, status) 
            VALUES (?, ?, 'pending')
        ");
        return $stmt->execute([$userId, $friendId]);
    }

    // Accept friend request
    public function acceptRequest($userId, $friendId) {
        $stmt = $this->conn->prepare("
            UPDATE friendships 
            SET status = 'accepted', updated_at = CURRENT_TIMESTAMP 
            WHERE friend_id = ? AND user_id = ? AND status = 'pending'
        ");
        return $stmt->execute([$userId, $friendId]);
    }

    // Decline friend request
    public function declineRequest($userId, $friendId) {
        $stmt = $this->conn->prepare("
            UPDATE friendships 
            SET status = 'declined', updated_at = CURRENT_TIMESTAMP 
            WHERE friend_id = ? AND user_id = ? AND status = 'pending'
        ");
        return $stmt->execute([$userId, $friendId]);
    }

    // Remove friendship
    public function removeFriend($userId, $friendId) {
        $stmt = $this->conn->prepare("
            DELETE FROM friendships 
            WHERE (user_id = ? AND friend_id = ?) 
            OR (user_id = ? AND friend_id = ?)
        ");
        return $stmt->execute([$userId, $friendId, $friendId, $userId]);
    }

    // Get all friends (accepted)
    public function getFriends($userId) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.email, u.display_name, u.created_at,
                   f.created_at as friends_since
            FROM friendships f
            JOIN users u ON (
                CASE 
                    WHEN f.user_id = ? THEN u.id = f.friend_id
                    WHEN f.friend_id = ? THEN u.id = f.user_id
                END
            )
            WHERE (f.user_id = ? OR f.friend_id = ?)
            AND f.status = 'accepted'
            ORDER BY u.display_name ASC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    // Get pending friend requests (received)
    public function getPendingRequests($userId) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.display_name, u.email, u.created_at,
                   f.created_at as request_date, f.id as friendship_id
            FROM friendships f
            JOIN users u ON u.id = f.user_id
            WHERE f.friend_id = ? AND f.status = 'pending'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Get sent friend requests (pending)
    public function getSentRequests($userId) {
        $stmt = $this->conn->prepare("
            SELECT u.id, u.username, u.display_name, u.email,
                   f.created_at as request_date, f.id as friendship_id
            FROM friendships f
            JOIN users u ON u.id = f.friend_id
            WHERE f.user_id = ? AND f.status = 'pending'
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([$userId]);
        $requests = [];
        foreach ($stmt->fetchAll() as $row) {
            unset($row['password_hash']);
            $requests[] = $row;
        }
        
        return $requests;
    }

    // Check if two users are friends
    public function areFriends($userId, $friendId) {
        $stmt = $this->conn->prepare("
            SELECT id FROM friendships 
            WHERE ((user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?))
            AND status = 'accepted'
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        return $stmt->fetch() !== false;
    }

    // Get friendship status between two users
    public function getFriendshipStatus($userId, $friendId) {
        $stmt = $this->conn->prepare("
            SELECT status, user_id, friend_id FROM friendships 
            WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)
        ");
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        $row = $stmt->fetch();
        
        if (!$row) {
            return ['status' => 'none', 'direction' => null];
        }
        
        $direction = $row['user_id'] == $userId ? 'sent' : 'received';
        
        return [
            'status' => $row['status'],
            'direction' => $direction
        ];
    }
}
