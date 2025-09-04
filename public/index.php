<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

$url = $_GET['url'] ?? '';
$route = explode('/', trim($url, '/'));
$controller = $route[0] ?? 'home';
$action = $route[1] ?? 'index';

function render($view, $data = []) {
    extract($data);
    ob_start();
    include "../views/{$view}.php";
    $content = ob_get_clean();
    include '../views/layout.php';
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

switch ($controller) {
    case 'home':
    case '':
        render('home');
        break;
        
    case 'sites':
        include '../controllers/sites.php';
        break;
        
    case 'analysis':
        include '../controllers/analysis.php';
        break;
        
    case 'analytics':
        include '../controllers/analytics.php';
        break;
        
    case 'api':
        include '../controllers/api.php';
        break;
        
    default:
        http_response_code(404);
        render('404');
        break;
}
?>