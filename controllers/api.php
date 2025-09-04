<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {
    case 'sites':
        handleSitesAPI($db, $method);
        break;
        
    case 'analyze':
        if ($method === 'POST') {
            handleAnalysisAPI($db);
        } else {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
        }
        break;
        
    case 'analysis':
        $analysisId = intval($route[2] ?? 0);
        if ($analysisId) {
            handleAnalysisStatusAPI($db, $analysisId);
        } else {
            http_response_code(400);
            jsonResponse(['error' => 'Analysis ID required']);
        }
        break;
        
    default:
        http_response_code(404);
        jsonResponse(['error' => 'API endpoint not found']);
        break;
}

function handleSitesAPI($db, $method) {
    switch ($method) {
        case 'GET':
            $sites = $db->fetchAll("SELECT * FROM sites ORDER BY name ASC");
            jsonResponse(['success' => true, 'sites' => $sites]);
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            $name = trim($input['name'] ?? '');
            $domain = trim($input['domain'] ?? '');
            $description = trim($input['description'] ?? '');
            
            if (empty($name) || empty($domain)) {
                http_response_code(400);
                jsonResponse(['error' => 'サイト名とドメインは必須です']);
            }
            
            $domain = preg_replace('/^https?:\/\//', '', $domain);
            $domain = rtrim($domain, '/');
            
            try {
                $sql = "INSERT INTO sites (name, domain, description) VALUES (?, ?, ?)";
                $db->execute($sql, [$name, $domain, $description]);
                $siteId = $db->lastInsertId();
                
                $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$siteId]);
                jsonResponse(['success' => true, 'site' => $site]);
            } catch (PDOException $e) {
                http_response_code(500);
                if ($e->getCode() == 23000) {
                    jsonResponse(['error' => 'このドメインは既に登録されています']);
                } else {
                    jsonResponse(['error' => 'データベースエラーが発生しました']);
                }
            }
            break;
            
        default:
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed']);
            break;
    }
}

function handleAnalysisAPI($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    $url = trim($input['url'] ?? '');
    $siteId = intval($input['site_id'] ?? 0);
    
    if (empty($url) || !$siteId) {
        http_response_code(400);
        jsonResponse(['error' => 'URLとサイトIDが必要です']);
    }
    
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        jsonResponse(['error' => '有効なURLを入力してください']);
    }
    
    $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$siteId]);
    if (!$site) {
        http_response_code(404);
        jsonResponse(['error' => 'サイトが見つかりません']);
    }
    
    try {
        $analysisId = $db->execute(
            "INSERT INTO analysis_history (site_id, url, status) VALUES (?, ?, 'processing')",
            [$siteId, $url]
        );
        $analysisId = $db->lastInsertId();
        
        jsonResponse(['success' => true, 'analysis_id' => $analysisId, 'status' => 'processing']);
        
    } catch (Exception $e) {
        http_response_code(500);
        jsonResponse(['error' => '分析の開始に失敗しました: ' . $e->getMessage()]);
    }
}

function handleAnalysisStatusAPI($db, $analysisId) {
    $analysis = $db->fetchOne("
        SELECT ah.*, s.name as site_name 
        FROM analysis_history ah 
        JOIN sites s ON ah.site_id = s.id 
        WHERE ah.id = ?
    ", [$analysisId]);
    
    if (!$analysis) {
        http_response_code(404);
        jsonResponse(['error' => '分析が見つかりません']);
    }
    
    $response = [
        'success' => true,
        'id' => $analysis['id'],
        'status' => $analysis['status'],
        'url' => $analysis['url'],
        'site_name' => $analysis['site_name'],
        'created_at' => $analysis['created_at'],
        'processing_time' => $analysis['processing_time']
    ];
    
    if ($analysis['status'] === 'completed') {
        $recommendations = $db->fetchAll("
            SELECT * FROM seo_recommendations 
            WHERE analysis_id = ? 
            ORDER BY 
                CASE priority 
                    WHEN 'high' THEN 1 
                    WHEN 'medium' THEN 2 
                    WHEN 'low' THEN 3 
                END,
                category, title
        ", [$analysisId]);
        
        $response['recommendations'] = $recommendations;
    } elseif ($analysis['status'] === 'failed') {
        $response['error_message'] = $analysis['error_message'];
    }
    
    jsonResponse($response);
}
?>