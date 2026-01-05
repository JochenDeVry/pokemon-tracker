<?php

class Auth {
    private static $sessionKey = 'pokemon_user';
    
    /**
     * Start session if not already started
     */
    public static function init() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Login user
     */
    public static function login($user) {
        self::init();
        $_SESSION[self::$sessionKey] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'display_name' => $user['display_name'],
            'is_public' => $user['is_public']
        ];
        return true;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::init();
        unset($_SESSION[self::$sessionKey]);
        session_destroy();
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public static function check() {
        self::init();
        return isset($_SESSION[self::$sessionKey]);
    }
    
    /**
     * Get current user
     */
    public static function user() {
        self::init();
        return $_SESSION[self::$sessionKey] ?? null;
    }
    
    /**
     * Get current user ID
     */
    public static function userId() {
        $user = self::user();
        return $user ? $user['id'] : null;
    }
    
    /**
     * Require authentication (return 401 if not logged in)
     */
    public static function require() {
        if (!self::check()) {
            http_response_code(401);
            echo json_encode([
                'success' => false, 
                'message' => 'Niet ingelogd. Log in om verder te gaan.',
                'requires_auth' => true
            ]);
            exit;
        }
        return true;
    }
    
    /**
     * Check if user can access another user's data
     */
    public static function canViewUser($targetUserId) {
        $currentUserId = self::userId();
        
        // User can always view own data
        if ($currentUserId == $targetUserId) {
            return true;
        }
        
        // Check if target user is public
        require_once __DIR__ . '/../models/User.php';
        $userModel = new User();
        $targetUser = $userModel->getById($targetUserId);
        
        return $targetUser && $targetUser['is_public'] == 1;
    }
}
