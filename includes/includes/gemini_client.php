<?php
class GeminiClient {
    private $apiKey;
    private $model;
    private $baseUrl = 'https://generativelanguage.googleapis.com/v1beta';
    
    public function __construct() {
        $this->apiKey = GEMINI_API_KEY;
        $this->model = GEMINI_MODEL;
        
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API キーが設定されていません');
        }
    }
    
    public function analyzePage($url, $site) {
        $pageContent = $this->fetchPageContent($url);
        $prompt = $this->buildAnalysisPrompt($url, $pageContent, $site);
        
        $response = $this->callGeminiAPI($prompt);
        return $this->parseAnalysisResponse($response);
    }
    
    private function fetchPageContent($url) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: ja,en-US;q=0.7,en;q=0.3',
                    'Connection: close'
                ],
                'timeout' => 30,
                'follow_location' => true,
                'max_redirects' => 5
            ]
        ]);
        
        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new Exception("ページの取得に失敗しました: {$url}");
        }
        
        return $content;
    }
    
    private function buildAnalysisPrompt($url, $content, $site) {
        $prompt = "あなたは経験豊富なSEO専門家です。以下のWebページを詳細に分析し、SEO改善提案を日本語で提供してください。

分析対象URL: {$url}
サイト名: {$site['name']}
ドメイン: {$site['domain']}
サイト説明: " . ($site['description'] ?? '未設定') . "

以下のHTMLコンテンツを分析してください:
" . mb_substr($content, 0, 50000) . "

分析結果は以下のJSONフォーマットで出力してください。必ず有効なJSONとして出力し、各提案は具体的で実装可能なものにしてください:

```json
[
  {
    \"category\": \"meta\",
    \"priority\": \"high\",
    \"title\": \"改善提案のタイトル\",
    \"conclusion\": \"結論（コピペで実装できる具体的な内容）\",
    \"explanation\": \"詳細説明（なぜこの改善が必要か、どのような効果が期待できるか）\",
    \"implementation\": \"実装用のHTMLコードまたはCSS（該当する場合）\",
    \"difficulty\": \"easy|medium|hard\",
    \"estimated_hours\": 1.5
  }
]
```

分析観点：
1. **meta要素**: title, description, keywords, OGタグ等の最適化
2. **technical**: 表示速度、画像最適化、構造化データ等
3. **content**: コンテンツの質、キーワード密度、見出し構造等
4. **performance**: ページ読み込み速度、CLS/LCP等のCore Web Vitals
5. **mobile**: モバイルフレンドリー、レスポンシブ対応
6. **accessibility**: アクセシビリティの向上
7. **structure**: URL構造、内部リンク、サイトマップ等

優先度の判定基準：
- **high**: SEO効果が高く、すぐに実装すべきもの
- **medium**: 中程度の効果、計画的に実装
- **low**: 長期的な改善、余裕があるときに実装

実装難易度の判定基準：
- **easy**: 1時間以内で完了
- **medium**: 1-4時間程度
- **hard**: 4時間以上または技術的に複雑

必ずJSONのみを返してください。説明文は含めないでください。";

        return $prompt;
    }
    
    private function callGeminiAPI($prompt) {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";
        
        $data = [
            'contents' => [
                [
                    'parts' => [
                        [
                            'text' => $prompt
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'topK' => 40,
                'topP' => 0.95,
                'maxOutputTokens' => 8192,
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH', 
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ]
            ]
        ];
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'User-Agent: SEO-Analysis-Service/1.0'
                ],
                'content' => json_encode($data),
                'timeout' => 120
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            $error = error_get_last();
            throw new Exception("Gemini API呼び出しに失敗しました: " . ($error['message'] ?? 'Unknown error'));
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Gemini APIレスポンスのJSONパースに失敗しました");
        }
        
        if (isset($responseData['error'])) {
            throw new Exception("Gemini APIエラー: " . $responseData['error']['message']);
        }
        
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception("Gemini APIから有効なレスポンスが得られませんでした");
        }
        
        return $responseData['candidates'][0]['content']['parts'][0]['text'];
    }
    
    private function parseAnalysisResponse($response) {
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("JSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $results = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("分析結果のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        if (!is_array($results)) {
            throw new Exception("分析結果が配列ではありません");
        }
        
        $validatedResults = [];
        foreach ($results as $result) {
            if (!isset($result['title'], $result['conclusion'], $result['explanation'])) {
                continue;
            }
            
            $validatedResults[] = [
                'category' => $result['category'] ?? 'technical',
                'priority' => in_array($result['priority'] ?? '', ['high', 'medium', 'low']) ? $result['priority'] : 'medium',
                'title' => $result['title'],
                'conclusion' => $result['conclusion'],
                'explanation' => $result['explanation'],
                'implementation' => $result['implementation'] ?? null,
                'difficulty' => in_array($result['difficulty'] ?? '', ['easy', 'medium', 'hard']) ? $result['difficulty'] : 'medium',
                'estimated_hours' => is_numeric($result['estimated_hours'] ?? '') ? floatval($result['estimated_hours']) : 1.0
            ];
        }
        
        if (empty($validatedResults)) {
            throw new Exception("有効な分析結果が得られませんでした");
        }
        
        usort($validatedResults, function($a, $b) {
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
            return $priorityOrder[$a['priority']] - $priorityOrder[$b['priority']];
        });
        
        return $validatedResults;
    }
}
?>