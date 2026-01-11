-- Create test accounts for friendship testing
-- Passwords: test123 for all accounts

INSERT INTO users (username, email, password_hash, display_name, is_public, created_at) VALUES
('ash', 'ash@pokemontracker.test', '$2y$10$2MapUUlJJACNlm0Xhek7mOp2PXjY0BL/UUKDP8tZ3R9HX7B0mN1/m', 'Ash Ketchum', 0, NOW()),
('misty', 'misty@pokemontracker.test', '$2y$10$2MapUUlJJACNlm0Xhek7mOp2PXjY0BL/UUKDP8tZ3R9HX7B0mN1/m', 'Misty Williams', 0, NOW()),
('brock', 'brock@pokemontracker.test', '$2y$10$2MapUUlJJACNlm0Xhek7mOp2PXjY0BL/UUKDP8tZ3R9HX7B0mN1/m', 'Brock Harrison', 0, NOW()),
('gary', 'gary@pokemontracker.test', '$2y$10$2MapUUlJJACNlm0Xhek7mOp2PXjY0BL/UUKDP8tZ3R9HX7B0mN1/m', 'Gary Oak', 0, NOW());

-- Add some sample cards for these users
-- Ash's cards
INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'ASH-001', 'Pikachu', 'Base Set', '25', 'Rare', 'Near Mint', 2, 15.50, NOW() FROM users WHERE username = 'ash';

INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'ASH-002', 'Charizard', 'Base Set', '4', 'Rare Holo', 'Played', 1, 250.00, NOW() FROM users WHERE username = 'ash';

-- Misty's cards  
INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'MISTY-001', 'Staryu', 'Base Set', '65', 'Common', 'Near Mint', 3, 2.00, NOW() FROM users WHERE username = 'misty';

INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'MISTY-002', 'Gyarados', 'Base Set', '6', 'Rare Holo', 'Excellent', 1, 45.00, NOW() FROM users WHERE username = 'misty';

-- Brock's cards
INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'BROCK-001', 'Onix', 'Base Set', '56', 'Uncommon', 'Near Mint', 2, 5.00, NOW() FROM users WHERE username = 'brock';

-- Gary's cards
INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'GARY-001', 'Eevee', 'Jungle', '51', 'Common', 'Near Mint', 5, 8.00, NOW() FROM users WHERE username = 'gary';

INSERT INTO cards (user_id, serial_number, card_name, set_name, card_number, rarity, condition_card, quantity, current_price, created_at)
SELECT id, 'GARY-002', 'Blastoise', 'Base Set', '2', 'Rare Holo', 'Near Mint', 1, 180.00, NOW() FROM users WHERE username = 'gary';
