<?php
require_once __DIR__ . '/models/Achievement.php';

$stats = [
    'total_cards' => 5,
    'total_value' => 75.5,
    'total_quantity' => 8,
    'unique_sets' => 3
];

$achievements = Achievement::getUserAchievements($stats);
echo json_encode($achievements, JSON_PRETTY_PRINT);
