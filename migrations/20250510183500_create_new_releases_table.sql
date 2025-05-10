-- 新刊情報キャッシュ用テーブル
CREATE TABLE IF NOT EXISTS new_releases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    release_year INT NOT NULL,
    release_month INT NOT NULL,
    release_day_info VARCHAR(50) NOT NULL, -- e.g., "1", "15", "上旬", "下旬"
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    publisher VARCHAR(255),
    raw_price VARCHAR(50), -- 価格はテキストで保持（「未定」などもあるため）
    source_article_url VARCHAR(512), -- スクレイピング元の記事URL
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_year_month (release_year, release_month) -- 検索用インデックス
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- データが重複しないようにユニークキーを追加（任意、タイトルと著者と発売日で判断など）
-- ALTER TABLE new_releases ADD UNIQUE INDEX uk_release_item (release_year, release_month, title(100), author(100), release_day_info(20));
-- 注意: title や author が長すぎる場合は、ユニークキーの長さを調整してください。