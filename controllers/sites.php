<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';

// デバッグ：sitesコントローラーが呼ばれたことをログ出力
error_log("Sites Controller accessed - Action: " . ($action ?? 'not_set') . " - Method: " . $_SERVER['REQUEST_METHOD']);

$db = Database::getInstance();

switch ($action) {
    case 'index':
    case '':
        $sites = $db->fetchAll("SELECT * FROM sites ORDER BY created_at DESC");
        
        // 最近の分析履歴を取得
        $analysisHistory = $db->fetchAll("
            SELECT ah.*, s.name as site_name 
            FROM analysis_history ah 
            JOIN sites s ON ah.site_id = s.id 
            WHERE ah.status = 'completed'
            ORDER BY ah.created_at DESC 
            LIMIT 10
        ");
        
        error_log("About to call render with sites count: " . count($sites));
        render('sites/index', [
            'sites' => $sites,
            'analysisHistory' => $analysisHistory
        ]);
        error_log("This should not appear if render() exits properly");
        break;
        
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $domain = trim($_POST['domain'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name) || empty($domain)) {
                $_SESSION['error'] = 'サイト名とドメインは必須です';
                redirect(url('sites/add'));
            }
            
            // ドメインの正規化
            $domain = preg_replace('/^https?:\/\//', '', $domain);
            $domain = rtrim($domain, '/');
            
            try {
                $sql = "INSERT INTO sites (name, domain, description) VALUES (?, ?, ?)";
                $db->execute($sql, [$name, $domain, $description]);
                
                $_SESSION['success'] = 'サイトが追加されました';
                redirect(url('sites'));
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error'] = 'このドメインは既に登録されています';
                } else {
                    $_SESSION['error'] = 'データベースエラーが発生しました';
                }
                redirect(url('sites/add'));
            }
        } else {
            render('sites/add');
        }
        break;
        
    case 'edit':
        $id = $route[2] ?? 0;
        if (!$id) {
            redirect(url('sites'));
        }
        
        $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$id]);
        if (!$site) {
            $_SESSION['error'] = 'サイトが見つかりません';
            redirect(url('sites'));
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $domain = trim($_POST['domain'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name) || empty($domain)) {
                $_SESSION['error'] = 'サイト名とドメインは必須です';
                redirect(url("sites/edit/{$id}"));
            }
            
            $domain = preg_replace('/^https?:\/\//', '', $domain);
            $domain = rtrim($domain, '/');
            
            try {
                $sql = "UPDATE sites SET name = ?, domain = ?, description = ? WHERE id = ?";
                $db->execute($sql, [$name, $domain, $description, $id]);
                
                $_SESSION['success'] = 'サイト情報が更新されました';
                redirect(url('sites'));
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error'] = 'このドメインは既に登録されています';
                } else {
                    $_SESSION['error'] = 'データベースエラーが発生しました';
                }
                redirect(url("sites/edit/{$id}"));
            }
        } else {
            // このサイトの分析履歴を取得
            $analyses = $db->fetchAll("
                SELECT * FROM analysis_history 
                WHERE site_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ", [$site['id']]);
            
            render('sites/edit', [
                'site' => $site,
                'analyses' => $analyses
            ]);
        }
        break;
        
    case 'delete':
        $id = $route[2] ?? 0;
        if (!$id) {
            redirect(url('sites'));
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db->execute("DELETE FROM sites WHERE id = ?", [$id]);
            $_SESSION['success'] = 'サイトが削除されました';
        }
        
        redirect(url('sites'));
        break;
        
    case 'analytics':
        $id = $route[2] ?? 0;
        if (!$id) {
            redirect(url('sites'));
        }
        
        $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$id]);
        if (!$site) {
            $_SESSION['error'] = 'サイトが見つかりません';
            redirect(url('sites'));
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ga_property_id = trim($_POST['ga_property_id'] ?? '');
            $gsc_property_url = trim($_POST['gsc_property_url'] ?? '');
            
            $sql = "UPDATE sites SET ga_property_id = ?, gsc_property_url = ?, ga_connected = ?, gsc_connected = ? WHERE id = ?";
            $db->execute($sql, [
                $ga_property_id, 
                $gsc_property_url,
                !empty($ga_property_id) ? 1 : 0,
                !empty($gsc_property_url) ? 1 : 0,
                $id
            ]);
            
            $_SESSION['success'] = '分析ツール連携設定が更新されました';
            redirect(url('sites'));
        } else {
            render('sites/analytics', ['site' => $site]);
        }
        break;
        
    default:
        redirect(url('sites'));
        break;
}
?>