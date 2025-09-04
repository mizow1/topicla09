<?php
// エラーレポートとoutput buffering設定
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_start();

require_once 'includes/config.php';
require_once 'includes/database.php';

$url = $_GET['url'] ?? '';
$route = explode('/', trim($url, '/'));
$controller = $route[0] ?? 'home';
$action = $route[1] ?? 'index';

// デバッグ情報をログに出力
error_log("Main Router - URL: " . $url);
error_log("Main Router - Controller: " . $controller);
error_log("Main Router - Action: " . $action);
error_log("Main Router - Method: " . $_SERVER['REQUEST_METHOD']);

function render($view, $data = []) {
    error_log("=== RENDER START ===");
    error_log("Render function called with view: " . $view);
    error_log("Data keys: " . implode(', ', array_keys($data)));
    
    // バッファリングをクリア
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    extract($data);
    ob_start();
    error_log("About to include views/{$view}.php");
    include "views/{$view}.php";
    $content = ob_get_clean();
    error_log("Content captured, length: " . strlen($content));
    error_log("Content preview: " . substr($content, 0, 100));
    error_log("Including layout.php with content variable");
    include 'views/layout.php';
    error_log("=== RENDER END - EXITING ===");
    exit;
}

function redirect($url) {
    header("Location: {$url}");
    exit;
}

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function url($path = '') {
    $baseUrl = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $baseUrl . ($path ? '/' . $path : '');
}

switch ($controller) {
    case 'home':
    case '':
        // ホーム用のデータを取得
        $db = Database::getInstance();
        $recentAnalyses = $db->fetchAll("
            SELECT ah.*, s.name as site_name, s.domain
            FROM analysis_history ah 
            JOIN sites s ON ah.site_id = s.id 
            WHERE ah.status = 'completed'
            ORDER BY ah.created_at DESC 
            LIMIT 5
        ");
        render('home', ['recentAnalyses' => $recentAnalyses]);
        break;
        
    case 'sites':
        include 'controllers/sites.php';
        break;
        
    case 'analysis':
        include 'controllers/analysis.php';
        break;
        
    case 'analytics':
        include 'controllers/analytics.php';
        break;
        
    case 'api':
        include 'controllers/api.php';
        break;
        
    default:
        http_response_code(404);
        render('404');
        break;
}
?>