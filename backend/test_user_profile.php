<?php
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Achievement.php';

$user = new User();
$userData = $user->getById(1);
unset($userData['password_hash']);
$userData['stats'] = $user->getStats(1);
$userData['achievements'] = Achievement::getUserAchievements($userData['stats']);

echo json_encode($userData, JSON_PRETTY_PRINT);
