UPDATE users SET password_hash = '$2y$10$XfT7Lv4ZtbETxvY9G5bDuu4CGCgWTbSUohb8jrqim3hSgWhq7iq5C' WHERE username = 'admin';
SELECT id, username, email, password_hash FROM users WHERE username = 'admin';
