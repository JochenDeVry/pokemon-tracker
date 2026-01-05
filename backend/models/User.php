<?php

require_once __DIR__ . '/../config/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = (new Database())->getConnection();
    }
    
    /**
     * Register a new user
     */
    public function register($username, $email, $password, $displayName = null) {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Alle velden zijn verplicht'];
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Ongeldig email adres'];
        }
        
        // Validate password length
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Wachtwoord moet minimaal 6 karakters zijn'];
        }
        
        // Check if username exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Gebruikersnaam bestaat al'];
        }
        
        // Check if email exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email adres bestaat al'];
        }
        
        // Hash password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password_hash, display_name, is_public) 
             VALUES (?, ?, ?, ?, 1)"
        );
        
        if ($stmt->execute([$username, $email, $passwordHash, $displayName ?? $username])) {
            $userId = $this->db->lastInsertId();
            $user = $this->getById($userId);
            unset($user['password_hash']);
            return ['success' => true, 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Kon account niet aanmaken'];
    }
    
    /**
     * Login user
     */
    public function login($emailOrUsername, $password) {
        // Find user by email or username
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE email = ? OR username = ?"
        );
        $stmt->execute([$emailOrUsername, $emailOrUsername]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Gebruiker niet gevonden'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Onjuist wachtwoord'];
        }
        
        // Remove sensitive data
        unset($user['password_hash']);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Get user by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all public users
     */
    public function getAllPublic() {
        $stmt = $this->db->query(
            "SELECT id, username, email, display_name, is_public, created_at 
             FROM users 
             WHERE is_public = 1 
             ORDER BY display_name, username"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update user profile
     */
    public function update($id, $data) {
        $fields = [];
        $values = [];
        
        if (isset($data['display_name'])) {
            $fields[] = "display_name = ?";
            $values[] = $data['display_name'];
        }
        
        if (isset($data['is_public'])) {
            $fields[] = "is_public = ?";
            $values[] = $data['is_public'] ? 1 : 0;
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return false;
            }
            $fields[] = "password_hash = ?";
            $values[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        if (empty($fields)) {
            return true;
        }
        
        $values[] = $id;
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }
    
    /**
     * Get user card statistics
     */
    public function getStats($userId) {
        $stmt = $this->db->prepare(
            "SELECT 
                COUNT(*) as total_cards,
                SUM(quantity) as total_quantity,
                SUM(COALESCE(current_price, purchase_price, 0) * quantity) as total_value,
                COUNT(DISTINCT set_name) as unique_sets
             FROM cards 
             WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
