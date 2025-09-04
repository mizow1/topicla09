<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

$db = Database::getInstance();

switch ($action) {
    case 'index':
    case '':
        $sites = $db->fetchAll("SELECT * FROM sites ORDER BY created_at DESC");
        render('sites/index', ['sites' => $sites]);
        break;
        
    case 'add':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $domain = trim($_POST['domain'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name) || empty($domain)) {
                $_SESSION['error'] = 'サイト名とドメインは必須です';
                redirect('/sites/add');
            }
            
            // ドメインの正規化
            $domain = preg_replace('/^https?:\/\//', '', $domain);
            $domain = rtrim($domain, '/');
            
            try {
                $sql = "INSERT INTO sites (name, domain, description) VALUES (?, ?, ?)";
                $db->execute($sql, [$name, $domain, $description]);
                
                $_SESSION['success'] = 'サイトが追加されました';
                redirect('/sites');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error'] = 'このドメインは既に登録されています';
                } else {
                    $_SESSION['error'] = 'データベースエラーが発生しました';
                }
                redirect('/sites/add');
            }
        } else {
            render('sites/add');
        }
        break;
        
    case 'edit':
        $id = $route[2] ?? 0;
        if (!$id) {
            redirect('/sites');
        }
        
        $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$id]);
        if (!$site) {
            $_SESSION['error'] = 'サイトが見つかりません';
            redirect('/sites');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $domain = trim($_POST['domain'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name) || empty($domain)) {
                $_SESSION['error'] = 'サイト名とドメインは必須です';
                redirect("/sites/edit/{$id}");
            }
            
            $domain = preg_replace('/^https?:\/\//', '', $domain);
            $domain = rtrim($domain, '/');
            
            try {
                $sql = "UPDATE sites SET name = ?, domain = ?, description = ? WHERE id = ?";
                $db->execute($sql, [$name, $domain, $description, $id]);
                
                $_SESSION['success'] = 'サイト情報が更新されました';
                redirect('/sites');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $_SESSION['error'] = 'このドメインは既に登録されています';
                } else {
                    $_SESSION['error'] = 'データベースエラーが発生しました';
                }
                redirect("/sites/edit/{$id}");
            }
        } else {
            render('sites/edit', ['site' => $site]);
        }
        break;
        
    case 'delete':
        $id = $route[2] ?? 0;
        if (!$id) {
            redirect('/sites');
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db->execute("DELETE FROM sites WHERE id = ?", [$id]);
            $_SESSION['success'] = 'サイトが削除されました';
        }
        
        redirect('/sites');
        break;
        
    case 'analytics':
        $id = $route[2] ?? 0;
        if (!$id) {
            redirect('/sites');
        }
        
        $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$id]);
        if (!$site) {
            $_SESSION['error'] = 'サイトが見つかりません';
            redirect('/sites');
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
            redirect('/sites');
        } else {
            render('sites/analytics', ['site' => $site]);
        }
        break;
        
    default:
        redirect('/sites');
        break;
}
?>