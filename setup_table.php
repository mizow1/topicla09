<?php
// ブラウザ経由でテーブル作成を行うスクリプト
header('Content-Type: text/html; charset=UTF-8');

echo "<h2>テーブル作成スクリプト</h2>";

$host = 'mysql80.mizy.sakura.ne.jp';
$dbname = 'mizy_topicla09';
$username = 'mizy';
$password = '8rjcp4ck';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ データベース接続成功</p>";
    
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
    echo "<p>✅ saved_articlesテーブルを作成しました。</p>";
    
    // テーブルが正常に作成されたか確認
    $result = $pdo->query("SHOW TABLES LIKE 'saved_articles'")->fetch();
    if ($result) {
        echo "<p>✅ テーブルの存在を確認しました。</p>";
        
        // テーブル構造を表示
        $columns = $pdo->query("DESCRIBE saved_articles")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>テーブル構造:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>カラム名</th><th>型</th><th>NULL許可</th><th>キー</th><th>デフォルト</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ テーブルが見つかりません。</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>