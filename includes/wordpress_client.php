<?php

class WordPressClient {
    private $apiBase;
    private $username;
    private $applicationPassword;
    
    public function __construct() {
        // 設定は.envファイルから読み込む
        $this->apiBase = $_ENV['WP_API_BASE'] ?? '';
        $this->username = $_ENV['WP_USERNAME'] ?? '';
        $this->applicationPassword = $_ENV['WP_APP_PASSWORD'] ?? '';
    }
    
    /**
     * 新規記事を作成
     */
    public function createPost($title, $content, $structure = '') {
        if (empty($this->apiBase) || empty($this->username) || empty($this->applicationPassword)) {
            return [
                'success' => false,
                'error' => 'WordPress API設定が不完全です。.envファイルを確認してください。'
            ];
        }
        
        try {
            // MarkdownをHTMLに変換
            $htmlContent = $this->markdownToHtml($content);
            
            // 構造がある場合は先頭に追加
            if (!empty($structure)) {
                $htmlContent = "<div class=\"article-structure\"><h3>記事構成</h3><pre>" . htmlspecialchars($structure) . "</pre></div>\n\n" . $htmlContent;
            }
            
            $postData = [
                'title' => $title,
                'content' => $htmlContent,
                'status' => 'draft', // 下書きとして作成
                'format' => 'standard'
            ];
            
            $response = $this->makeRequest('POST', '/wp/v2/posts', $postData);
            
            if ($response && isset($response['id'])) {
                return [
                    'success' => true,
                    'post_id' => $response['id'],
                    'post_url' => $response['link'] ?? '',
                    'edit_url' => $this->getEditUrl($response['id'])
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'WordPress記事の作成に失敗しました'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'WordPress API呼び出しエラー: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 既存記事を更新
     */
    public function updatePost($wordpressUrl, $title, $content, $structure = '') {
        if (empty($this->apiBase) || empty($this->username) || empty($this->applicationPassword)) {
            return [
                'success' => false,
                'error' => 'WordPress API設定が不完全です。.envファイルを確認してください。'
            ];
        }
        
        try {
            // URLから記事IDを抽出
            $postId = $this->extractPostIdFromUrl($wordpressUrl);
            if (!$postId) {
                return [
                    'success' => false,
                    'error' => '無効なWordPress URLです'
                ];
            }
            
            // MarkdownをHTMLに変換（構造は追加しない）
            $htmlContent = $this->markdownToHtml($content);
            
            // 構造からh1タグの内容をタイトルに適用
            $finalTitle = $title;
            if (!empty($structure)) {
                $h1Title = $this->extractH1FromStructure($structure);
                if ($h1Title) {
                    $finalTitle = $h1Title;
                }
            }
            
            $postData = [
                'title' => $finalTitle,
                'content' => $htmlContent
            ];
            
            $response = $this->makeRequest('POST', "/wp/v2/posts/{$postId}", $postData);
            
            if ($response && isset($response['id'])) {
                return [
                    'success' => true,
                    'post_id' => $response['id'],
                    'post_url' => $response['link'] ?? $wordpressUrl
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'WordPress記事の更新に失敗しました'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'WordPress API呼び出しエラー: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * WordPress REST API リクエストを実行
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = rtrim($this->apiBase, '/') . $endpoint;
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->applicationPassword),
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL エラー: ' . $error);
        }
        
        if ($httpCode >= 400) {
            $errorResponse = json_decode($response, true);
            $errorMessage = $errorResponse['message'] ?? "HTTP エラー: {$httpCode}";
            throw new Exception($errorMessage);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * URLから記事IDを抽出
     */
    private function extractPostIdFromUrl($url) {
        // 一般的なWordPressのURL形式に対応
        // 例: https://example.com/?p=123 または https://example.com/post-slug/
        
        // ?p=123 形式
        if (preg_match('/[?&]p=(\d+)/', $url, $matches)) {
            return intval($matches[1]);
        }
        
        // パーマリンク形式の場合は、APIを使って記事IDを取得
        try {
            $response = $this->makeRequest('GET', '/wp/v2/posts?per_page=100');
            if ($response && is_array($response)) {
                foreach ($response as $post) {
                    if (isset($post['link']) && $post['link'] === $url) {
                        return $post['id'];
                    }
                }
            }
        } catch (Exception $e) {
            // エラーの場合はfalseを返す
        }
        
        return false;
    }
    
    /**
     * 編集URLを生成
     */
    private function getEditUrl($postId) {
        $baseUrl = str_replace('/wp-json', '', $this->apiBase);
        return $baseUrl . "/wp-admin/post.php?post={$postId}&action=edit";
    }
    
    /**
     * 簡易MarkdownをHTMLに変換
     */
    private function markdownToHtml($markdown) {
        $html = $markdown;
        
        // 見出しの変換
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // 太字
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        
        // イタリック
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // リスト
        $html = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);
        
        // 段落の処理
        $paragraphs = explode("\n\n", $html);
        $htmlParagraphs = [];
        
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph)) continue;
            
            // 既にHTMLタグがある場合はそのまま
            if (preg_match('/^<(h[1-6]|ul|ol|li|div)/', $paragraph)) {
                $htmlParagraphs[] = $paragraph;
            } else {
                // 改行をbrタグに変換
                $paragraph = str_replace("\n", '<br>', $paragraph);
                $htmlParagraphs[] = '<p>' . $paragraph . '</p>';
            }
        }
        
        return implode("\n\n", $htmlParagraphs);
    }
    
    /**
     * 構造からh1タグの内容を抽出
     */
    private function extractH1FromStructure($structure) {
        // <h1>タイトル</h1> 形式から抽出
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $structure, $matches)) {
            return trim(strip_tags($matches[1]));
        }
        
        // h1: タイトル 形式から抽出
        if (preg_match('/^h1:\s*(.+?)(?:\n|$)/m', $structure, $matches)) {
            return trim($matches[1]);
        }
        
        // # タイトル 形式から抽出
        if (preg_match('/^#\s+(.+?)(?:\n|$)/m', $structure, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
}
?>