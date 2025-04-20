-- Insert initial user into users table
INSERT INTO users (username, password, email, is_admin, created_at, updated_at)
VALUES ('makkenro', '$2y$10$4GJNQ6xTUebCS.F9fsAXJOEMrVtG6ASfS/ZaDdZ9vMCZHWunqJIpK', 'makkenro@example.com', TRUE, NOW(), NOW());