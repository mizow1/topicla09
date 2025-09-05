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
        
        // UTF-8エンコーディングを確保
        $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        
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

## 重要な指示：

**必ず具体的で実装可能な内容にしてください:**

1. **titleタグの最適化**: 現在のtitleタグを分析し、5つの具体的な代替案を提案（キーワードを含んだ60文字以内の具体例）
2. **meta descriptionの最適化**: 現在のmeta descriptionを分析し、5つの具体的な代替案を提案（160文字以内の具体例）
3. **見出し構造の最適化**: 現在の見出し（h1, h2, h3等）を分析し、SEOと読者体験を考慮した5つの具体的な見出し構成案を提案（キーワード含有、検索意図対応、論理的階層構造）
4. **内部リンクの最適化**: ページ内容を分析し、どの既存記事・ページへのリンクを、ページ内のどこに追加すべきかを具体的に指定
5. **画像最適化**: 画像を分析し、「WebP形式、300KB以下に圧縮」のような具体的なファイル形式とサイズを提案

分析結果は以下のJSONフォーマットで出力してください。必ず有効なJSONとして出力し、各提案は5案ずつ含めてください:

```json
[
  {
    \"category\": \"meta\",
    \"priority\": \"high\",
    \"title\": \"titleタグの最適化\",
    \"conclusion\": \"現在のtitle: '〜' → 以下の5案から選択:\\n1. 具体的なtitle案1（55文字）\\n2. 具体的なtitle案2（58文字）\\n3. 具体的なtitle案3（52文字）\\n4. 具体的なtitle案4（60文字）\\n5. 具体的なtitle案5（50文字）\",
    \"explanation\": \"現在のtitleタグが〜という問題があるため、キーワード「〜」を含み、より検索意図に合致する内容に変更することで〜の効果が期待できます。\",
    \"implementation\": \"<title>選択したtitle案をここに設定</title>\",
    \"difficulty\": \"easy\",
    \"estimated_hours\": 0.5,
    \"proposals\": [
      \"具体的なtitle案1\",
      \"具体的なtitle案2\", 
      \"具体的なtitle案3\",
      \"具体的なtitle案4\",
      \"具体的なtitle案5\"
    ]
  },
  {
    \"category\": \"meta\",
    \"priority\": \"high\",
    \"title\": \"meta descriptionの最適化\",
    \"conclusion\": \"現在のmeta description: '〜' → 以下の5案から選択:\\n1. 具体的なdescription案1（155文字）\\n2. 具体的なdescription案2（160文字）\\n3. 具体的なdescription案3（150文字）\\n4. 具体的なdescription案4（158文字）\\n5. 具体的なdescription案5（152文字）\",
    \"explanation\": \"詳細説明\",
    \"implementation\": \"<meta name=\\\"description\\\" content=\\\"選択したdescription案\\\">\",
    \"difficulty\": \"easy\",
    \"estimated_hours\": 0.5,
    \"proposals\": [
      \"具体的なdescription案1\",
      \"具体的なdescription案2\",
      \"具体的なdescription案3\",
      \"具体的なdescription案4\",
      \"具体的なdescription案5\"
    ]
  },
  {
    \"category\": \"structure\",
    \"priority\": \"high\",
    \"title\": \"見出し構造の最適化\",
    \"conclusion\": \"SEOと読者体験を向上させる見出し構造を以下5案で提案。各案は検索意図に対応し、キーワードを効果的に配置した論理的階層構造です。\",
    \"explanation\": \"現在の見出し構造は検索意図への対応が不十分で、キーワードが効果的に配置されていません。提案する見出し構造は、メインキーワードを含み、ユーザーの検索意図に応える論理的な構造になっています。各見出しが読者の疑問に順序立てて答え、SEO効果も期待できます。\",
    \"implementation\": \"<h1>メインキーワードを含んだ魅力的なタイトル</h1><h2>読者の疑問に答える小見出し</h2><h3>具体的な詳細説明</h3>\",
    \"difficulty\": \"medium\",
    \"estimated_hours\": 2.0,
    \"proposals\": [
      \"h1: [メインキーワード]の完全ガイド｜[ベネフィット]\\nh2: [メインキーワード]とは？基礎知識\\nh2: [メインキーワード]の効果とメリット\\nh3: 効果1: [具体的効果]\\nh3: 効果2: [具体的効果]\\nh2: [メインキーワード]の実践方法\\nh3: 初心者向けの始め方\\nh3: 上級者向けのコツ\\nh2: よくある質問とトラブル対策\\nh2: まとめ: [メインキーワード]で得られる成果\",
      \"h1: 【2024年最新】[メインキーワード]の全知識｜[数字]つのポイント\\nh2: [メインキーワード]の基本概念\\nh2: [メインキーワード]が重要な[数字]つの理由\\nh3: 理由1: [具体的理由]\\nh3: 理由2: [具体的理由]\\nh2: [メインキーワード]の具体的手順\\nh3: ステップ1: [具体的ステップ]\\nh3: ステップ2: [具体的ステップ]\\nh2: [メインキーワード]の注意点と対策\\nh2: [メインキーワード]の将来性と展望\",
      \"h1: [メインキーワード]で[目標達成]する方法｜実践的アプローチ\\nh2: [メインキーワード]の実用的定義\\nh2: [メインキーワード]を活用するメリット\\nh2: [メインキーワード]の実践テクニック\\nh3: 基本テクニック\\nh3: 応用テクニック\\nh2: 実際の成功事例と結果\\nh3: 事例1: [具体的事例]\\nh3: 事例2: [具体的事例]\\nh2: [メインキーワード]のツールとリソース\\nh2: 次のステップとアクションプラン\",
      \"h1: [メインキーワード]の専門知識｜プロが教える[数字]のポイント\\nh2: [メインキーワード]の技術的基礎\\nh2: [メインキーワード]の高度な理論\\nh3: 理論的背景\\nh3: 最新の研究結果\\nh2: [メインキーワード]の専門的活用法\\nh3: 業界別の適用例\\nh3: 専門家レベルの技術\\nh2: [メインキーワード]の課題と解決策\\nh2: 専門家からの推奨事項\",
      \"h1: 初心者でもわかる[メインキーワード]入門｜基礎から応用まで\\nh2: [メインキーワード]とは？わかりやすく解説\\nh2: なぜ[メインキーワード]が必要なのか\\nh2: [メインキーワード]の始め方\\nh3: 準備するもの\\nh3: 最初の一歩\\nh2: [メインキーワード]でよくある疑問\\nh3: Q&A形式で解説\\nh2: [メインキーワード]の次のステップ\\nh2: 困った時の対処法とサポート\"
    ]
  },
  {
    \"category\": \"structure\",
    \"priority\": \"medium\",
    \"title\": \"内部リンクの最適化\",
    \"conclusion\": \"以下の箇所に関連記事へのリンクを追加：\\n1. 第2段落の後に『〜に関する記事』へのリンク（/related-page-1）\\n2. 〜の章の最後に『〜について詳しくはこちら』リンク（/detailed-guide）\\n3. 〜\",
    \"explanation\": \"詳細説明\",
    \"implementation\": \"<a href=\\\"/related-article\\\">関連記事タイトル</a>\",
    \"difficulty\": \"easy\",
    \"estimated_hours\": 1.0
  },
  {
    \"category\": \"technical\",
    \"priority\": \"medium\", 
    \"title\": \"画像最適化\",
    \"conclusion\": \"以下の画像を最適化：\\n1. hero-image.jpg → WebP形式、300KB以下に圧縮\\n2. content-image.png → WebP形式、150KB以下に圧縮\\n3. thumbnail.jpg → WebP形式、50KB以下に圧縮\",
    \"explanation\": \"詳細説明\",
    \"implementation\": \"<img src=\\\"image.webp\\\" alt=\\\"代替テキスト\\\" width=\\\"600\\\" height=\\\"400\\\" loading=\\\"lazy\\\">\",
    \"difficulty\": \"medium\",
    \"estimated_hours\": 2.0
  }
]
```

**必須要件：**
- titleとmeta descriptionの提案は必ず5案ずつ含める
- 見出し構造は5つの異なる構成案を提案（検索意図別：完全ガイド型、ステップ型、実践型、専門型、初心者型など）
- 見出し構造では[メインキーワード]を実際のページキーワードに置き換えて具体的な見出し文を生成
- 見出し階層は論理的で、h1→h2→h3の順序を守る
- 内部リンクは具体的なリンク先とページ内の挿入位置を明記
- 画像最適化は具体的なファイル形式（WebP等）と目標サイズを記載
- conclusionフィールドには実装可能な具体的内容のみを記載

必ずJSONのみを返してください。説明文は含めないでください。";

        return $prompt;
    }
    
    private function callGeminiAPI($prompt) {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}";
        
        $data = [
            'contents' => [
                [
                    'role' => 'user',
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
        
        // デバッグ: プロンプトの内容を確認
        if (empty(trim($prompt))) {
            throw new Exception("callGeminiAPI: プロンプトが空です。プロンプト長: " . strlen($prompt));
        }
        
        // UTF-8エンコーディングを確保
        $prompt = mb_convert_encoding($prompt, 'UTF-8', 'auto');
        
        // データ配列を再構築（UTF-8エンコーディング後）
        $data = [
            'contents' => [
                [
                    'role' => 'user',
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
        
        $jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);
        
        // JSONエンコードエラーチェック
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSONエンコードエラー: " . json_last_error_msg());
        }
        
        // デバッグ: リクエストデータの構造を確認
        if (empty($jsonData) || $jsonData === 'null') {
            throw new Exception("JSONデータが空です。データ構造: " . print_r($data, true));
        }
        
        // デバッグ: contents部分だけ確認
        if (!isset($data['contents']) || empty($data['contents'])) {
            throw new Exception("contents配列が設定されていません。データ: " . print_r($data, true));
        }
        
        // デバッグ: text部分が正しく設定されているか確認
        if (!isset($data['contents'][0]['parts'][0]['text']) || empty(trim($data['contents'][0]['parts'][0]['text']))) {
            throw new Exception("text部分が空です。プロンプト長: " . strlen($prompt) . ", 最初の100文字: " . mb_substr($prompt, 0, 100));
        }
        
        // デバッグ: JSONデータのサイズを確認
        if (strlen($jsonData) > 1000000) { // 1MB以上
            throw new Exception("リクエストデータが大きすぎます: " . strlen($jsonData) . " bytes");
        }
        
        // cURLを使用してより詳細なエラー情報を取得
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            'User-Agent: SEO-Analysis-Service/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new Exception("Gemini API呼び出しに失敗しました: " . $curlError);
        }
        
        // 400エラーの場合、レスポンスボディを確認
        if ($httpCode === 400) {
            throw new Exception("Gemini API 400エラー (HTTP {$httpCode}): " . $response);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Gemini API HTTPエラー (HTTP {$httpCode}): " . $response);
        }
        
        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Gemini APIレスポンスのJSONパースに失敗しました。レスポンス: " . substr($response, 0, 500));
        }
        
        if (isset($responseData['error'])) {
            $errorMessage = $responseData['error']['message'] ?? 'Unknown error';
            $errorDetails = isset($responseData['error']['details']) ? json_encode($responseData['error']['details']) : '';
            throw new Exception("Gemini APIエラー: " . $errorMessage . ($errorDetails ? " 詳細: " . $errorDetails : ""));
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
                'estimated_hours' => is_numeric($result['estimated_hours'] ?? '') ? floatval($result['estimated_hours']) : 1.0,
                'proposals' => $result['proposals'] ?? []
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
    
    public function regenerateProposals($category, $title, $currentProposals) {
        $structurePrompt = "";
        if ($category === 'structure' || strpos($title, '見出し') !== false) {
            $structurePrompt = "

**見出し構造の場合の特別要件:**
- 各提案は完全な見出し構造（h1, h2, h3の階層）を含む
- メインキーワードを含んだ具体的な見出し文を生成
- 検索意図に対応した論理的な構造（完全ガイド型、ステップ型、実践型、専門型、初心者型など）
- SEOと読者体験を考慮した質の高い見出し構成
- 単なる固有名詞の羅列ではなく、読者の疑問に答える構造";
        }
        
        $prompt = "あなたはSEO専門家です。以下のカテゴリについて、既存の提案と重複しない新しい5つの高品質な提案を生成してください。

カテゴリ: {$category}
タイトル: {$title}
既存の提案: " . json_encode($currentProposals, JSON_UNESCAPED_UNICODE) . $structurePrompt . "

新しい5つの提案をJSONフォーマットで出力してください:
```json
[
  \"新しい提案1\",
  \"新しい提案2\",
  \"新しい提案3\",
  \"新しい提案4\",
  \"新しい提案5\"
]
```";

        $response = $this->callGeminiAPI($prompt);
        
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            return $currentProposals;
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $newProposals = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($newProposals)) {
            return $currentProposals;
        }
        
        return $newProposals;
    }

    public function generateContentFromHeadings($headingStructure, $siteUrl) {
        // 元ページの内容を取得
        $originalContent = '';
        try {
            $originalContent = $this->fetchPageContent($siteUrl);
            // HTMLタグを除去して主要なテキストのみ抽出
            $originalContent = strip_tags($originalContent);
            $originalContent = preg_replace('/\s+/', ' ', trim($originalContent));
            $originalContent = mb_substr($originalContent, 0, 10000); // 10,000文字まで
        } catch (Exception $e) {
            $originalContent = "元ページの取得に失敗しました。";
        }

        // 見出し構造から主要キーワードを抽出
        $mainKeywords = $this->extractKeywordsFromHeadings($headingStructure);
        
        // Web検索で競合情報を取得
        $competitorResearch = $this->performWebSearch($mainKeywords);

        $prompt = "あなたは経験豊富なコンテンツライターです。以下の見出し構造に基づいて、SEO最適化された高品質な記事本文を作成してください。

**見出し構造:**
{$headingStructure}

**元ページURL:** {$siteUrl}
**元ページ内容（参考情報）:** 
" . mb_substr($originalContent, 0, 5000) . "

**主要キーワード:** " . implode(', ', $mainKeywords) . "

**競合サイト調査結果:**
{$competitorResearch}

**重要な要求事項:**
1. **Web検索による最新情報の活用:** 主要キーワードでGoogle検索を行い、上位サイトの情報を参考に内容の品質を向上させる
2. **競合コンテンツの分析:** 同じキーワードで上位表示されている記事の構成と内容を分析し、より詳細で価値のある情報を提供
3. **SEO最適化:** メインキーワードを自然に含み、読者に価値を提供する内容
4. **ターゲットユーザーのニーズ対応:** 実用的で具体的な情報を提供
5. **現状の内容の改善:** 元ページの内容を参考にしつつ、より詳細で有用な内容に改善
6. **検索意図への対応:** ユーザーが求める情報を論理的に提供
7. **信頼性と権威性:** 具体例やデータを含む信頼できる内容

**作成指針:**
- 各見出しに対して適切なボリューム（200-500文字程度）の本文を作成
- 読者が行動を起こせる具体的な情報を含む
- 専門性を示しつつ、わかりやすい表現を使用
- 元ページの内容よりも詳細で有用な情報を提供
- 自然なキーワード使用でSEO効果を最大化
- 最新の業界情報やトレンドを含める
- 実例や事例を豊富に含める

**出力形式:**
Markdown形式で出力してください。見出しは#、##、###を使用し、本文は適切な段落で分けてください。

例：
# メインタイトル
（ここにh1に対応する導入文）

## サブタイトル1
（ここにh2に対応する詳細な説明）

### 詳細項目1
（ここにh3に対応する具体的な内容）

このような形式で、全ての見出しに対応する本文をMarkdown形式で作成してください。";

        // デバッグ: プロンプトの内容を確認
        if (empty(trim($prompt))) {
            throw new Exception("プロンプトが空です。見出し構造: " . mb_substr($headingStructure, 0, 200) . "...");
        }
        
        $response = $this->callGeminiAPI($prompt);
        
        // レスポンスをMarkdown形式として返す
        return trim($response);
    }

    private function extractKeywordsFromHeadings($headingStructure) {
        // 見出し構造からキーワードを抽出
        $keywords = [];
        
        // h1タグから主要キーワードを抽出
        if (preg_match('/<h1>(.+?)<\/h1>/', $headingStructure, $matches)) {
            $h1Content = $matches[1];
            // よく使われる助詞や接続詞を除去して主要キーワードを抽出
            $stopWords = ['の', 'に', 'は', 'が', 'を', 'と', 'で', 'や', 'から', 'まで', '、', '。', '｜', 'について', 'とは', 'する', 'ます', 'です'];
            $words = preg_split('/[\s、。｜]+/', $h1Content);
            foreach ($words as $word) {
                $cleanWord = trim($word);
                if (strlen($cleanWord) > 1 && !in_array($cleanWord, $stopWords)) {
                    $keywords[] = $cleanWord;
                }
            }
        }
        
        // h1がない場合は平文形式から抽出
        if (empty($keywords)) {
            $lines = explode("\n", $headingStructure);
            foreach ($lines as $line) {
                if (preg_match('/^h1:\s*(.+)/', $line, $matches)) {
                    $h1Content = $matches[1];
                    $stopWords = ['の', 'に', 'は', 'が', 'を', 'と', 'で', 'や', 'から', 'まで', '、', '。', '｜', 'について', 'とは', 'する', 'ます', 'です'];
                    $words = preg_split('/[\s、。｜]+/', $h1Content);
                    foreach ($words as $word) {
                        $cleanWord = trim($word);
                        if (strlen($cleanWord) > 1 && !in_array($cleanWord, $stopWords)) {
                            $keywords[] = $cleanWord;
                        }
                    }
                    break;
                }
            }
        }
        
        return array_slice(array_unique($keywords), 0, 3); // 最大3つのキーワード
    }
    
    private function performWebSearch($keywords) {
        if (empty($keywords)) {
            return "検索キーワードが取得できませんでした。";
        }
        
        $searchQuery = implode(' ', $keywords);
        $searchResults = [];
        
        try {
            // Google検索のシミュレーション（実際のWeb検索は制限があるため、今回はプロンプトで指示）
            $searchInfo = "検索キーワード「" . $searchQuery . "」に関する最新情報と競合分析を実施してください。以下の観点で情報を収集してください：\n";
            $searchInfo .= "1. 上位表示サイトの共通する構成要素\n";
            $searchInfo .= "2. ユーザーがよく検索する関連キーワード\n";
            $searchInfo .= "3. 業界の最新トレンドや統計データ\n";
            $searchInfo .= "4. 実用的な事例やケーススタディ\n";
            $searchInfo .= "5. ユーザーのよくある疑問や課題\n\n";
            $searchInfo .= "これらの情報を活用して、既存コンテンツよりも詳細で価値の高い内容を作成してください。";
            
            return $searchInfo;
            
        } catch (Exception $e) {
            return "Web検索の実行に失敗しました。一般的な業界知識を活用してコンテンツを作成します。";
        }
    }
}
?>