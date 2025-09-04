<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

// デバッグ：analyticsコントローラーが呼ばれたことをログ出力
error_log("Analytics Controller accessed - Action: " . ($action ?? 'not_set') . " - Method: " . $_SERVER['REQUEST_METHOD']);

$db = Database::getInstance();

switch ($action) {
    case 'index':
    case '':
        // すべてのサイトの連携状況を取得
        $sites = $db->fetchAll("
            SELECT 
                id, name, domain, 
                ga_property_id, gsc_property_url,
                ga_connected, gsc_connected,
                created_at, updated_at
            FROM sites 
            ORDER BY created_at DESC
        ");
        
        // 連携済みサイト数の統計
        $totalSites = count($sites);
        $gaConnected = count(array_filter($sites, function($site) { return $site['ga_connected']; }));
        $gscConnected = count(array_filter($sites, function($site) { return $site['gsc_connected']; }));
        
        $stats = [
            'total_sites' => $totalSites,
            'ga_connected' => $gaConnected,
            'gsc_connected' => $gscConnected,
            'fully_connected' => count(array_filter($sites, function($site) { 
                return $site['ga_connected'] && $site['gsc_connected']; 
            }))
        ];
        
        render('analytics/index', ['sites' => $sites, 'stats' => $stats]);
        break;
        
    default:
        redirect(url('analytics'));
        break;
}
?>