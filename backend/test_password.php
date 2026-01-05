<?php
require_once __DIR__ . '/config/Database.php';

$db = (new Database())->getConnection();
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute(['admin']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Hash: " . $user['password_hash'] . "\n";
    echo "Hash length: " . strlen($user['password_hash']) . "\n";
    
    $password = 'admin123';
    echo "\nTesting password: $password\n";
    $result = password_verify($password, $user['password_hash']);
    echo "Password verify result: " . ($result ? "TRUE" : "FALSE") . "\n";
    
    // Test with other password
    $password2 = 'password';
    echo "\nTesting password: $password2\n";
    $result2 = password_verify($password2, $user['password_hash']);
    echo "Password verify result: " . ($result2 ? "TRUE" : "FALSE") . "\n";
} else {
    echo "User not found!\n";
}
