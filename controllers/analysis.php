<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/gemini_client.php';

$db = Database::getInstance();

switch ($action) {
    case 'index':
    case '':
        $sites = $db->fetchAll("SELECT * FROM sites ORDER BY name ASC");
        $siteId = $_GET['site_id'] ?? null;
        
        // 最近の分析履歴を取得
        $recentAnalyses = $db->fetchAll("
            SELECT ah.*, s.name as site_name, s.domain,
                   COUNT(sr.id) as recommendation_count
            FROM analysis_history ah 
            JOIN sites s ON ah.site_id = s.id 
            LEFT JOIN seo_recommendations sr ON ah.id = sr.analysis_id
            WHERE ah.status = 'completed'
            GROUP BY ah.id
            ORDER BY ah.created_at DESC 
            LIMIT 5
        ");
        
        render('analysis/index', [
            'sites' => $sites, 
            'selectedSiteId' => $siteId,
            'recentAnalyses' => $recentAnalyses
        ]);
        break;
        
    case 'run':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // JSON形式またはPOSTデータに対応
            if (isset($_POST['url'])) {
                // FormDataの場合
                $url = trim($_POST['url'] ?? '');
                $siteId = intval($_POST['site_id'] ?? 0);
            } else {
                // JSONの場合
                $input = json_decode(file_get_contents('php://input'), true);
                $url = trim($input['url'] ?? '');
                $siteId = intval($input['site_id'] ?? 0);
            }
            
            if (empty($url) || !$siteId) {
                jsonResponse(['success' => false, 'error' => 'URLとサイトが必要です']);
            }
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                jsonResponse(['success' => false, 'error' => '有効なURLを入力してください']);
            }
            
            $site = $db->fetchOne("SELECT * FROM sites WHERE id = ?", [$siteId]);
            if (!$site) {
                jsonResponse(['success' => false, 'error' => 'サイトが見つかりません']);
            }
            
            try {
                $analysisId = $db->execute(
                    "INSERT INTO analysis_history (site_id, url, status) VALUES (?, ?, 'processing')",
                    [$siteId, $url]
                );
                $analysisId = $db->lastInsertId();
                
                $startTime = microtime(true);
                $geminiClient = new GeminiClient();
                $analysisResults = $geminiClient->analyzePage($url, $site);
                $endTime = microtime(true);
                
                $processingTime = round($endTime - $startTime, 2);
                
                $db->execute(
                    "UPDATE analysis_history SET status = 'completed', analysis_results = ?, processing_time = ? WHERE id = ?",
                    [json_encode($analysisResults), $processingTime, $analysisId]
                );
                
                foreach ($analysisResults as $result) {
                    $db->execute(
                        "INSERT INTO seo_recommendations (analysis_id, category, priority, title, conclusion, explanation, implementation_code, difficulty, estimated_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $analysisId,
                            $result['category'] ?? 'technical',
                            $result['priority'] ?? 'medium',
                            $result['title'],
                            $result['conclusion'],
                            $result['explanation'],
                            $result['implementation'] ?? null,
                            $result['difficulty'] ?? 'medium',
                            $result['estimated_hours'] ?? 1.0
                        ]
                    );
                }
                
                // 分析結果をレスポンスに含める
                jsonResponse([
                    'success' => true, 
                    'analysis_id' => $analysisId,
                    'results' => $analysisResults
                ]);
                
            } catch (Exception $e) {
                $db->execute(
                    "UPDATE analysis_history SET status = 'failed', error_message = ? WHERE id = ?",
                    [$e->getMessage(), $analysisId ?? 0]
                );
                
                jsonResponse(['success' => false, 'error' => '分析中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'result':
        $analysisId = intval($route[2] ?? 0);
        if (!$analysisId) {
            redirect(url('analysis'));
        }
        
        $analysis = $db->fetchOne("
            SELECT ah.*, s.name as site_name, s.domain 
            FROM analysis_history ah 
            JOIN sites s ON ah.site_id = s.id 
            WHERE ah.id = ?
        ", [$analysisId]);
        
        if (!$analysis) {
            $_SESSION['error'] = '分析結果が見つかりません';
            redirect(url('analysis'));
        }
        
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
        
        render('analysis/result', [
            'analysis' => $analysis,
            'recommendations' => $recommendations
        ]);
        break;
        
    case 'history':
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $siteFilter = intval($_GET['site_id'] ?? 0);
        $statusFilter = $_GET['status'] ?? '';
        
        $whereConditions = [];
        $params = [];
        
        if ($siteFilter) {
            $whereConditions[] = "ah.site_id = ?";
            $params[] = $siteFilter;
        }
        
        if ($statusFilter) {
            $whereConditions[] = "ah.status = ?";
            $params[] = $statusFilter;
        }
        
        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
        
        $analyses = $db->fetchAll("
            SELECT ah.*, s.name as site_name, s.domain,
                   COUNT(sr.id) as recommendation_count
            FROM analysis_history ah 
            JOIN sites s ON ah.site_id = s.id 
            LEFT JOIN seo_recommendations sr ON ah.id = sr.analysis_id
            {$whereClause}
            GROUP BY ah.id
            ORDER BY ah.created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ", $params);
        
        $totalCount = $db->fetchOne("
            SELECT COUNT(*) as count 
            FROM analysis_history ah 
            JOIN sites s ON ah.site_id = s.id 
            {$whereClause}
        ", $params)['count'];
        
        $sites = $db->fetchAll("SELECT id, name FROM sites ORDER BY name");
        
        render('analysis/history', [
            'analyses' => $analyses,
            'sites' => $sites,
            'currentPage' => $page,
            'totalPages' => ceil($totalCount / $limit),
            'filters' => [
                'site_id' => $siteFilter,
                'status' => $statusFilter
            ]
        ]);
        break;
        
    default:
        redirect(url('analysis'));
        break;
}
?>