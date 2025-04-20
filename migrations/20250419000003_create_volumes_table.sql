-- 漫画の巻数管理用テーブル作成
CREATE TABLE IF NOT EXISTS volumes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comic_id INT NOT NULL,
    volume_number INT NOT NULL,
    title VARCHAR(255),
    release_date DATE,
    is_owned BOOLEAN DEFAULT FALSE,
    purchase_date DATE,
    price DECIMAL(10, 2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (comic_id) REFERENCES comics(id) ON DELETE CASCADE,
    UNIQUE KEY (comic_id, volume_number)
);