<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

try {
    $db = Database::getInstance();
    
    // proposalsカラムが存在するかチェック
    $columns = $db->fetchAll("SHOW COLUMNS FROM seo_recommendations LIKE 'proposals'");
    
    if (empty($columns)) {
        echo "proposalsカラムを追加中...\n";
        $db->execute("ALTER TABLE seo_recommendations ADD COLUMN proposals JSON COMMENT '提案オプション（5案のリスト）' AFTER implementation_code");
        echo "proposalsカラムが正常に追加されました。\n";
    } else {
        echo "proposalsカラムは既に存在します。\n";
    }
    
    echo "マイグレーション完了！\n";
    
} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>