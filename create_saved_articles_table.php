<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    
    $sql = "CREATE TABLE IF NOT EXISTS saved_articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(500) NOT NULL,
        content LONGTEXT NOT NULL,
        structure TEXT,
        site_url VARCHAR(500),
        wordpress_post_id INT NULL,
        wordpress_url VARCHAR(500),
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_created_at (created_at),
        INDEX idx_title (title(100)),
        INDEX idx_wordpress_post_id (wordpress_post_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->execute($sql);
    echo "✅ saved_articlesテーブルを作成しました。\n";
    
} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>