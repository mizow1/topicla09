<?php
// データベース設定
define('DB_HOST', 'mysql80.mizy.sakura.ne.jp');
define('DB_NAME', 'mizy_topicla09');
define('DB_USER', 'mizy');
define('DB_PASS', '8rjcp4ck');

// アプリケーション設定
define('APP_NAME', 'SEO改善提案サービス');
define('APP_URL', 'https://mizy.sakura.ne.jp/topicla09/');

// .envファイルから設定を読み込み
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

// Gemini API設定
define('GEMINI_MODEL', $_ENV['GEMINI_MODEL'] ?? 'gemini-2.0-flash');
define('GEMINI_API_KEY', $_ENV['GEMINI_API_KEY'] ?? '');

// セッション設定
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);

session_start();
?>