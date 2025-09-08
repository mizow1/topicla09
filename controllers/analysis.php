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
                        "INSERT INTO seo_recommendations (analysis_id, category, priority, title, conclusion, explanation, implementation_code, proposals, difficulty, estimated_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                        [
                            $analysisId,
                            $result['category'] ?? 'technical',
                            $result['priority'] ?? 'medium',
                            $result['title'],
                            $result['conclusion'],
                            $result['explanation'],
                            $result['implementation'] ?? null,
                            json_encode($result['proposals'] ?? []),
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
                CASE category
                    WHEN 'meta' THEN 1
                    WHEN 'technical' THEN 2
                    WHEN 'content' THEN 3
                    WHEN 'structure' THEN 4
                    WHEN 'performance' THEN 5
                    WHEN 'mobile' THEN 6
                    WHEN 'accessibility' THEN 7
                    ELSE 8
                END,
                CASE 
                    WHEN title LIKE '%title%' OR title LIKE '%タイトル%' THEN 1
                    WHEN title LIKE '%meta description%' OR title LIKE '%メタディスクリプション%' THEN 2
                    ELSE 3
                END,
                title
        ", [$analysisId]);
        
        // proposalsをJSONデコード
        foreach ($recommendations as &$rec) {
            if (!empty($rec['proposals'])) {
                $rec['proposals'] = json_decode($rec['proposals'], true) ?? [];
            } else {
                $rec['proposals'] = [];
            }
        }
        
        render('analysis/result', [
            'analysis' => $analysis,
            'recommendations' => $recommendations
        ]);
        break;
        
    case 'regenerate-proposals':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['category']) || empty($input['currentProposals'])) {
                jsonResponse(['success' => false, 'error' => 'パラメータが不足しています']);
            }
            
            try {
                $geminiClient = new GeminiClient();
                $newProposals = $geminiClient->regenerateProposals(
                    $input['category'],
                    $input['title'] ?? '',
                    $input['currentProposals']
                );
                
                jsonResponse([
                    'success' => true,
                    'proposals' => $newProposals
                ]);
                
            } catch (Exception $e) {
                jsonResponse(['success' => false, 'error' => '提案生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;

    case 'generate-content':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // エラー出力をバッファリングしてクリーンなJSONレスポンスを保証
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['headingStructure']) || empty($input['siteUrl'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'パラメータが不足しています']);
                }
                
                // 元ページの内容を取得してコンテキストとして利用
                $siteUrl = $input['siteUrl'];
                $headingStructure = $input['headingStructure'];
                
                $geminiClient = new GeminiClient();
                $content = $geminiClient->generateContentFromHeadings($headingStructure, $siteUrl);
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'content' => $content,
                    'headingStructure' => $headingStructure
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => '本文作成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;

    case 'generate-topic-cluster-from-analysis':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['analysisId']) || empty($input['siteUrl'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => '分析IDとサイトURLが必要です']);
                }
                
                $analysisId = intval($input['analysisId']);
                $siteUrl = $input['siteUrl'];
                $isRegenerate = $input['regenerate'] ?? false;
                $currentProposals = $input['currentProposals'] ?? [];
                
                // 分析結果の取得
                $analysis = $db->fetchOne("SELECT * FROM analysis_history WHERE id = ?", [$analysisId]);
                if (!$analysis) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => '分析結果が見つかりません']);
                }
                
                $geminiClient = new GeminiClient();
                $result = $geminiClient->generateTopicClusterFromAnalysis(
                    $siteUrl, 
                    $analysis, 
                    $isRegenerate, 
                    $currentProposals
                );
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'proposals' => $result['proposals'],
                    'extractedKeywords' => $result['extractedKeywords']
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => 'トピッククラスター生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'generate-article-structures':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['articleTitle'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => '記事タイトルが必要です']);
                }
                
                $articleTitle = $input['articleTitle'];
                $topic = $input['topic'] ?? '';
                $isRegenerate = $input['regenerate'] ?? false;
                $currentStructures = $input['currentStructures'] ?? [];
                
                $geminiClient = new GeminiClient();
                $structures = $geminiClient->generateArticleStructures($articleTitle, $topic, $isRegenerate, $currentStructures);
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'structures' => $structures
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => '記事構成生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'generate-article-content':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['headingStructure'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => '見出し構造が必要です']);
                }
                
                $articleTitle = $input['articleTitle'] ?? '';
                $headingStructure = $input['headingStructure'];
                $topic = $input['topic'] ?? '';
                
                $geminiClient = new GeminiClient();
                $content = $geminiClient->generateArticleContentForCluster($headingStructure, $articleTitle, $topic);
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'content' => $content,
                    'headingStructure' => $headingStructure
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => '記事本文作成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'generate-internal-link-optimization':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['analysisId']) || empty($input['siteUrl'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => '分析IDとサイトURLが必要です']);
                }
                
                $analysisId = intval($input['analysisId']);
                $siteUrl = $input['siteUrl'];
                $isRegenerate = $input['regenerate'] ?? false;
                $currentProposals = $input['currentProposals'] ?? [];
                
                // 分析結果の取得
                $analysis = $db->fetchOne("SELECT * FROM analysis_history WHERE id = ?", [$analysisId]);
                if (!$analysis) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => '分析結果が見つかりません']);
                }
                
                $geminiClient = new GeminiClient();
                $result = $geminiClient->generateInternalLinkOptimization(
                    $siteUrl, 
                    $analysis,
                    $isRegenerate, 
                    $currentProposals
                );
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'existingPages' => $result['existingPages'],
                    'newPageProposals' => $result['newPageProposals']
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => '内部リンク最適化生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'regenerate-article-structure':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['articleTitle']) || empty($input['currentStructure'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'パラメータが不足しています']);
                }
                
                $articleTitle = $input['articleTitle'];
                $currentStructure = $input['currentStructure'];
                $topic = $input['topic'] ?? '';
                
                $geminiClient = new GeminiClient();
                $newStructure = $geminiClient->regenerateArticleStructure($articleTitle, $topic, $currentStructure);
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'structure' => $newStructure
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => '記事構成再生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'regenerate-cluster-article':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || !isset($input['proposalIndex']) || !isset($input['clusterIndex'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'パラメータが不足しています']);
                }
                
                $proposalIndex = intval($input['proposalIndex']);
                $clusterIndex = intval($input['clusterIndex']);
                $currentTitle = $input['currentTitle'] ?? '';
                $topic = $input['topic'] ?? '';
                
                $geminiClient = new GeminiClient();
                $newTitle = $geminiClient->regenerateClusterArticle($currentTitle, $topic);
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'newTitle' => $newTitle
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => 'クラスター記事再生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'regenerate-single-topic-cluster':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || !isset($input['proposalIndex']) || empty($input['currentProposal'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'パラメータが不足しています']);
                }
                
                $proposalIndex = intval($input['proposalIndex']);
                $currentProposal = $input['currentProposal'];
                $topic = $input['topic'] ?? '';
                
                $geminiClient = new GeminiClient();
                $newProposal = $geminiClient->regenerateTopicCluster($currentProposal, $topic);
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'newProposal' => $newProposal
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => 'トピッククラスター再生成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'save-article':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['title']) || empty($input['content'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'タイトルと本文が必要です']);
                }
                
                $title = $input['title'];
                $content = $input['content'];
                $structure = $input['structure'] ?? '';
                $siteUrl = $input['siteUrl'] ?? '';
                
                // 記事をデータベースに保存
                $articleId = $db->execute(
                    "INSERT INTO saved_articles (title, content, structure, site_url, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())",
                    [$title, $content, $structure, $siteUrl]
                );
                
                $articleId = $db->lastInsertId();
                
                ob_clean();
                jsonResponse([
                    'success' => true,
                    'articleId' => $articleId,
                    'message' => '記事を保存しました'
                ]);
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => '記事保存中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'create-wordpress-post':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['title']) || empty($input['content'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'タイトルと本文が必要です']);
                }
                
                $title = $input['title'];
                $content = $input['content'];
                $structure = $input['structure'] ?? '';
                
                // WordPress APIを使用して記事を作成
                require_once __DIR__ . '/../includes/wordpress_client.php';
                
                $wpClient = new WordPressClient();
                $result = $wpClient->createPost($title, $content, $structure);
                
                if ($result['success']) {
                    // 作成された記事も保存しておく
                    $db->execute(
                        "INSERT INTO saved_articles (title, content, structure, wordpress_post_id, wordpress_url, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                        [$title, $content, $structure, $result['post_id'], $result['post_url']]
                    );
                    
                    ob_clean();
                    jsonResponse([
                        'success' => true,
                        'postId' => $result['post_id'],
                        'postUrl' => $result['post_url'],
                        'message' => 'WordPress記事を作成しました'
                    ]);
                } else {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => $result['error']]);
                }
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => 'WordPress記事作成中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
        break;
        
    case 'update-wordpress-post':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            ob_start();
            error_reporting(0);
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                if (!$input || empty($input['wordpressUrl']) || empty($input['content'])) {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => 'WordPressのURLと本文が必要です']);
                }
                
                $wordpressUrl = $input['wordpressUrl'];
                $title = $input['title'];
                $content = $input['content'];
                $structure = $input['structure'] ?? '';
                
                // WordPress APIを使用して記事を更新
                require_once __DIR__ . '/../includes/wordpress_client.php';
                
                $wpClient = new WordPressClient();
                $result = $wpClient->updatePost($wordpressUrl, $title, $content, $structure);
                
                if ($result['success']) {
                    ob_clean();
                    jsonResponse([
                        'success' => true,
                        'postId' => $result['post_id'],
                        'message' => 'WordPress記事を更新しました'
                    ]);
                } else {
                    ob_clean();
                    jsonResponse(['success' => false, 'error' => $result['error']]);
                }
                
            } catch (Exception $e) {
                ob_clean();
                jsonResponse(['success' => false, 'error' => 'WordPress記事更新中にエラーが発生しました: ' . $e->getMessage()]);
            }
        }
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