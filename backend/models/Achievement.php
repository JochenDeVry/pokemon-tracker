<?php

class Achievement {
    
    /**
     * Get all achievements for a user based on their stats
     */
    public static function getUserAchievements($stats) {
        $achievements = [];
        
        $totalCards = $stats['total_cards'] ?? 0;
        $totalValue = $stats['total_value'] ?? 0;
        
        // Collection size achievements
        $achievements[] = self::checkAchievement(
            'Beginner Verzamelaar',
            'Voeg je eerste kaart toe aan je collectie',
            $totalCards >= 1,
            '🎴',
            'bronze'
        );
        
        $achievements[] = self::checkAchievement(
            'Starter Deck',
            'Verzamel 10 kaarten',
            $totalCards >= 10,
            '📦',
            'bronze'
        );
        
        $achievements[] = self::checkAchievement(
            'Serieuze Verzamelaar',
            'Verzamel 50 kaarten',
            $totalCards >= 50,
            '📚',
            'silver'
        );
        
        $achievements[] = self::checkAchievement(
            'Expert Verzamelaar',
            'Verzamel 100 kaarten',
            $totalCards >= 100,
            '🏆',
            'gold'
        );
        
        $achievements[] = self::checkAchievement(
            'Master Verzamelaar',
            'Verzamel 250 kaarten',
            $totalCards >= 250,
            '👑',
            'platinum'
        );
        
        $achievements[] = self::checkAchievement(
            'Legendary Collector',
            'Verzamel 500 kaarten',
            $totalCards >= 500,
            '⭐',
            'legendary'
        );
        
        // Value-based achievements
        $achievements[] = self::checkAchievement(
            'Eerste Investering',
            'Collectie waarde van €10',
            $totalValue >= 10,
            '💰',
            'bronze'
        );
        
        $achievements[] = self::checkAchievement(
            'Waardevolle Collectie',
            'Collectie waarde van €50',
            $totalValue >= 50,
            '💎',
            'silver'
        );
        
        $achievements[] = self::checkAchievement(
            'Schatten Verzamelaar',
            'Collectie waarde van €100',
            $totalValue >= 100,
            '💍',
            'gold'
        );
        
        $achievements[] = self::checkAchievement(
            'Rijke Verzamelaar',
            'Collectie waarde van €250',
            $totalValue >= 250,
            '🏅',
            'platinum'
        );
        
        $achievements[] = self::checkAchievement(
            'Fortuin in Kaarten',
            'Collectie waarde van €500',
            $totalValue >= 500,
            '🎖️',
            'legendary'
        );
        
        $achievements[] = self::checkAchievement(
            'Pokemon Magnaat',
            'Collectie waarde van €1000',
            $totalValue >= 1000,
            '🔱',
            'legendary'
        );
        
        return $achievements;
    }
    
    private static function checkAchievement($title, $description, $unlocked, $icon, $tier) {
        return [
            'title' => $title,
            'description' => $description,
            'unlocked' => $unlocked,
            'icon' => $icon,
            'tier' => $tier
        ];
    }
}
