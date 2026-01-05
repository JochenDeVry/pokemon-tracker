CREATE DATABASE IF NOT EXISTS pokemon_cards;
USE pokemon_cards;

CREATE TABLE IF NOT EXISTS cards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    serial_number VARCHAR(50) UNIQUE NOT NULL,
    card_name VARCHAR(255) NOT NULL,
    set_name VARCHAR(255),
    card_number VARCHAR(20),
    rarity VARCHAR(50),
    condition_card VARCHAR(20) DEFAULT 'Near Mint',
    quantity INT DEFAULT 1,
    purchase_price DECIMAL(10, 2),
    current_price DECIMAL(10, 2),
    cardmarket_url VARCHAR(500),
    image_url VARCHAR(500),
    last_price_update TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_serial (serial_number),
    INDEX idx_set (set_name),
    INDEX idx_name (card_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some sample data for testing
INSERT INTO cards (serial_number, card_name, set_name, card_number, rarity, quantity, notes) VALUES
('SWSH01-001', 'Pikachu', 'Sword & Shield Base Set', '001/202', 'Common', 1, 'Eerste kaart in collectie'),
('SWSH01-025', 'Charizard V', 'Sword & Shield Base Set', '025/202', 'Ultra Rare', 2, 'Mooie conditie');
