<?php
// シンプルなテーブル作成スクリプト
$host = 'mysql80.mizy.sakura.ne.jp';
$dbname = 'mizy_topicla09';
$username = 'mizy';
$password = '8rjcp4ck';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
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
    
    $pdo->exec($sql);
    echo "✅ saved_articlesテーブルを作成しました。\n";
    
    // テーブルが正常に作成されたか確認
    $result = $pdo->query("SHOW TABLES LIKE 'saved_articles'")->fetch();
    if ($result) {
        echo "✅ テーブルの存在を確認しました。\n";
    } else {
        echo "❌ テーブルが見つかりません。\n";
    }
    
} catch (PDOException $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>