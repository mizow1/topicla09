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
    
    public function generateTopicClusterFromAnalysis($siteUrl, $analysis, $isRegenerate = false, $currentProposals = []) {
        // 分析対象ページからコンテンツを取得
        try {
            $pageContent = $this->fetchPageContent($siteUrl);
        } catch (Exception $e) {
            throw new Exception("分析対象ページの取得に失敗しました: " . $e->getMessage());
        }
        
        // ページからメインキーワードを抽出
        $extractedKeywords = $this->extractMainKeywordsFromContent($pageContent, $siteUrl);
        
        if (empty($extractedKeywords)) {
            throw new Exception("ページからキーワードを抽出できませんでした");
        }
        
        // 抽出したキーワードを基にトピッククラスターを生成
        $mainTopic = $extractedKeywords[0]; // 最も重要なキーワードをメイントピックとする
        
        $regenerateNote = '';
        if ($isRegenerate && !empty($currentProposals)) {
            $regenerateNote = "\n\n**重要：以下の提案と重複しない、新しい5つの提案を作成してください:**\n" . 
                             json_encode($currentProposals, JSON_UNESCAPED_UNICODE);
        }
        
        $prompt = "あなたは経験豊富なSEO専門家です。以下の分析対象ページから抽出した情報を基に、トピッククラスターによるSEO戦略を提案してください。

**分析対象URL:** {$siteUrl}
**抽出されたメインキーワード:** " . implode(', ', $extractedKeywords) . "
**メイントピック:** {$mainTopic}

**ページ内容（抜粋）:**
" . mb_substr(strip_tags($pageContent), 0, 3000) . "

**トピッククラスターとは:**
- 1つのピラー記事（包括的なメイン記事）を中心に
- 複数の関連するクラスター記事（詳細な個別記事）を作成し
- 内部リンクで相互に連結することでSEO効果を高める手法

**要求事項:**
1. 抽出したキーワードを活用した5つの異なるアプローチの提案
2. 各提案には以下を含める：
   - 1つのピラー記事タイトル（抽出キーワードを含む包括的で権威性のある内容）
   - 5-7つのクラスター記事タイトル（具体的でロングテールキーワードを含む）

**ピラー記事の特徴:**
- メインキーワード「{$mainTopic}」を必ず含む包括的なタイトル
- 3000-5000文字規模の大型コンテンツ想定
- 業界全体を俯瞰する権威性のあるタイトル

**クラスター記事の特徴:**
- ピラー記事のサブトピックを詳細に扱う
- 抽出されたキーワード群を組み合わせたロングテールキーワードを含む
- 実用的で検索需要のあるタイトル
- 1500-2500文字規模のコンテンツ想定{$regenerateNote}

以下のJSONフォーマットで5つの提案を出力してください：

```json
[
  {
    \"pillarTitle\": \"【2024年完全版】{$mainTopic}の全知識｜初心者から上級者まで完全攻略ガイド\",
    \"clusterTitles\": [
      \"{$mainTopic}とは？基礎知識を初心者向けにわかりやすく解説\",
      \"{$mainTopic}のメリット・デメリット｜導入前に知っておくべき全情報\",
      \"{$mainTopic}の始め方｜初心者でも失敗しない5つのステップ\",
      \"{$mainTopic}のツール比較｜おすすめ10選を徹底レビュー\",
      \"{$mainTopic}のよくある失敗例と対策｜成功率を上げる方法\",
      \"{$mainTopic}の最新トレンド｜2024年に押さえるべき動向\"
    ]
  }
]
```

※ 上記は例です。実際には抽出されたキーワードに基づいて、より具体的で関連性の高いタイトルを5案作成してください。

必ずJSONのみを返してください。説明文は含めないでください。";

        $response = $this->callGeminiAPI($prompt);
        
        // JSONの抽出とパース
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("JSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $proposals = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("提案のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        if (!is_array($proposals)) {
            throw new Exception("提案が配列ではありません");
        }
        
        // 提案の検証とクリーンアップ
        $validatedProposals = [];
        foreach ($proposals as $proposal) {
            if (isset($proposal['pillarTitle'], $proposal['clusterTitles']) && 
                is_array($proposal['clusterTitles']) && 
                count($proposal['clusterTitles']) >= 5) {
                $validatedProposals[] = $proposal;
            }
        }
        
        if (empty($validatedProposals)) {
            throw new Exception("有効な提案が得られませんでした");
        }
        
        return [
            'proposals' => $validatedProposals,
            'extractedKeywords' => $extractedKeywords
        ];
    }
    
    private function extractMainKeywordsFromContent($pageContent, $siteUrl) {
        // HTMLタグを除去
        $textContent = strip_tags($pageContent);
        
        // titleタグとh1タグを特別に抽出
        $title = '';
        $h1 = '';
        
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $pageContent, $matches)) {
            $title = trim($matches[1]);
        }
        
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $pageContent, $matches)) {
            $h1 = trim(strip_tags($matches[1]));
        }
        
        // meta descriptionも抽出
        $metaDescription = '';
        if (preg_match('/<meta[^>]*name=["\']description["\'][^>]*content=["\']([^"\']+)["\'][^>]*>/i', $pageContent, $matches)) {
            $metaDescription = trim($matches[1]);
        }
        
        // Gemini APIを使用してキーワードを抽出
        $prompt = "あなたはSEO専門家です。以下のWebページ情報から、SEO戦略に重要なメインキーワード3-5個を抽出してください。

**URL:** {$siteUrl}
**Titleタグ:** {$title}
**H1タグ:** {$h1}
**Meta Description:** {$metaDescription}
**ページ本文（抜粋）:** " . mb_substr($textContent, 0, 5000) . "

**抽出条件:**
1. 検索ボリュームが期待できるキーワード
2. ページの主要トピックを表すキーワード
3. トピッククラスター戦略に適用可能なキーワード
4. ビジネス価値の高いキーワード

**出力形式:**
重要度順にキーワードのみをカンマ区切りで出力してください。

例: キーワード1, キーワード2, キーワード3

キーワードのみを返してください。説明は不要です。";

        try {
            $response = $this->callGeminiAPI($prompt);
            
            // レスポンスからキーワードを抽出
            $response = trim($response);
            
            // 改行や余分な文字を除去
            $response = preg_replace('/[\r\n]+/', '', $response);
            
            // カンマ区切りでキーワードを分割
            $keywords = array_map('trim', explode(',', $response));
            
            // 空のキーワードを除去
            $keywords = array_filter($keywords, function($keyword) {
                return !empty($keyword) && mb_strlen($keyword) >= 2 && mb_strlen($keyword) <= 30;
            });
            
            // 最大5個までに制限
            $keywords = array_slice($keywords, 0, 5);
            
            if (empty($keywords)) {
                // フォールバック：titleタグから抽出
                if (!empty($title)) {
                    $fallbackKeywords = [];
                    $words = preg_split('/[\s\|｜\-\–\—\[\]【】\(\)（）]+/', $title);
                    foreach ($words as $word) {
                        $cleanWord = trim($word);
                        if (mb_strlen($cleanWord) >= 2 && mb_strlen($cleanWord) <= 20) {
                            $fallbackKeywords[] = $cleanWord;
                            if (count($fallbackKeywords) >= 3) break;
                        }
                    }
                    return $fallbackKeywords;
                }
                
                throw new Exception("キーワードの抽出に失敗しました");
            }
            
            return array_values($keywords);
            
        } catch (Exception $e) {
            // フォールバック処理
            $fallbackKeywords = [];
            if (!empty($title)) {
                $words = preg_split('/[\s\|｜\-\–\—\[\]【】\(\)（）]+/', $title);
                foreach ($words as $word) {
                    $cleanWord = trim($word);
                    if (mb_strlen($cleanWord) >= 2 && mb_strlen($cleanWord) <= 20) {
                        $fallbackKeywords[] = $cleanWord;
                        if (count($fallbackKeywords) >= 3) break;
                    }
                }
            }
            
            if (empty($fallbackKeywords)) {
                throw new Exception("キーワードの抽出に失敗しました: " . $e->getMessage());
            }
            
            return $fallbackKeywords;
        }
    }

    public function generateTopicCluster($topic, $isRegenerate = false, $currentProposals = []) {
        $regenerateNote = '';
        if ($isRegenerate && !empty($currentProposals)) {
            $regenerateNote = "\n\n**重要：以下の提案と重複しない、新しい5つの提案を作成してください:**\n" . 
                             json_encode($currentProposals, JSON_UNESCAPED_UNICODE);
        }
        
        $prompt = "あなたは経験豊富なSEO専門家です。「{$topic}」というメイントピックについて、トピッククラスターによるSEO戦略を提案してください。

**トピッククラスターとは:**
- 1つのピラー記事（包括的なメイン記事）を中心に
- 複数の関連するクラスター記事（詳細な個別記事）を作成し
- 内部リンクで相互に連結することでSEO効果を高める手法

**要求事項:**
1. 5つの異なるアプローチの提案
2. 各提案には以下を含める：
   - 1つのピラー記事タイトル（包括的で権威性のある内容）
   - 5-7つのクラスター記事タイトル（具体的でロングテールキーワードを含む）

**ピラー記事の特徴:**
- メインキーワードを含む包括的なタイトル
- 3000-5000文字規模の大型コンテンツ想定
- 業界全体を俯瞰する権威性のあるタイトル

**クラスター記事の特徴:**
- ピラー記事のサブトピックを詳細に扱う
- ロングテールキーワードを含む
- 実用的で検索需要のあるタイトル
- 1500-2500文字規模のコンテンツ想定{$regenerateNote}

以下のJSONフォーマットで5つの提案を出力してください：

```json
[
  {
    \"pillarTitle\": \"【2024年完全版】{$topic}の全知識｜初心者から上級者まで完全攻略ガイド\",
    \"clusterTitles\": [
      \"{$topic}とは？基礎知識を初心者向けにわかりやすく解説\",
      \"{$topic}のメリット・デメリット｜導入前に知っておくべき全情報\",
      \"{$topic}の始め方｜初心者でも失敗しない5つのステップ\",
      \"{$topic}のツール比較｜おすすめ10選を徹底レビュー\",
      \"{$topic}のよくある失敗例と対策｜成功率を上げる方法\",
      \"{$topic}の最新トレンド｜2024年に押さえるべき動向\"
    ]
  },
  {
    \"pillarTitle\": \"プロが教える{$topic}マスターガイド｜効果的な運用と成果の最大化\",
    \"clusterTitles\": [
      \"{$topic}の戦略設計｜目標設定から実行プランまで\",
      \"{$topic}のKPI設定と測定方法｜成果を可視化する指標\",
      \"{$topic}の予算計画｜コスト効率を最大化するアプローチ\",
      \"{$topic}のチーム体制｜役割分担と運用フロー\",
      \"{$topic}の改善サイクル｜PDCAで継続的に成果を向上させる方法\",
      \"{$topic}の成功事例｜業界別ケーススタディ\"
    ]
  },
  {
    \"pillarTitle\": \"{$topic}で結果を出す実践メソッド｜即効性のあるテクニック集\",
    \"clusterTitles\": [
      \"{$topic}の基本テクニック10選｜すぐに使える実用的手法\",
      \"{$topic}の応用テクニック｜上級者向け高度な戦術\",
      \"{$topic}の時短術｜効率を3倍にする自動化のコツ\",
      \"{$topic}のトラブルシューティング｜よくある問題の解決策\",
      \"{$topic}の品質向上｜プロレベルの仕上がりにする方法\",
      \"{$topic}の継続のコツ｜モチベーション維持と習慣化\"
    ]
  },
  {
    \"pillarTitle\": \"{$topic}業界分析レポート｜市場動向と将来予測\",
    \"clusterTitles\": [
      \"{$topic}市場規模と成長予測｜2024年最新データ分析\",
      \"{$topic}の競合分析｜主要プレイヤーの戦略比較\",
      \"{$topic}の技術革新｜最新テクノロジーの影響と可能性\",
      \"{$topic}の法的規制｜コンプライアンスで注意すべきポイント\",
      \"{$topic}のグローバルトレンド｜海外市場との比較分析\",
      \"{$topic}の投資動向｜資金調達と企業評価の現状\"
    ]
  },
  {
    \"pillarTitle\": \"{$topic}スターターキット｜ゼロから始める完全ロードマップ\",
    \"clusterTitles\": [
      \"{$topic}の準備段階｜開始前にチェックすべき項目\",
      \"{$topic}の初期設定｜最適な環境構築の手順\",
      \"{$topic}の学習計画｜効率的なスキルアップ方法\",
      \"{$topic}の実践演習｜手を動かして身につける基礎スキル\",
      \"{$topic}の成果測定｜初心者でもできる効果検証\",
      \"{$topic}の次のステップ｜中級者への進路ガイド\"
    ]
  }
]
```

必ずJSONのみを返してください。説明文は含めないでください。";

        $response = $this->callGeminiAPI($prompt);
        
        // JSONの抽出とパース
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("JSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $proposals = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("提案のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        if (!is_array($proposals)) {
            throw new Exception("提案が配列ではありません");
        }
        
        // 提案の検証とクリーンアップ
        $validatedProposals = [];
        foreach ($proposals as $proposal) {
            if (isset($proposal['pillarTitle'], $proposal['clusterTitles']) && 
                is_array($proposal['clusterTitles']) && 
                count($proposal['clusterTitles']) >= 5) {
                $validatedProposals[] = $proposal;
            }
        }
        
        if (empty($validatedProposals)) {
            throw new Exception("有効な提案が得られませんでした");
        }
        
        return $validatedProposals;
    }
    
    public function generateArticleStructures($articleTitle, $topic, $isRegenerate = false, $currentStructures = []) {
        $regenerateNote = '';
        if ($isRegenerate && !empty($currentStructures)) {
            $regenerateNote = "\n\n**重要：以下の構成と重複しない、新しい5つの構成案を作成してください:**\n" . 
                             json_encode(array_column($currentStructures, 'headings'), JSON_UNESCAPED_UNICODE);
        }
        
        $prompt = "あなたは経験豊富なSEOライターです。「{$articleTitle}」というタイトルの記事について、SEO最適化された見出し構造を5パターン提案してください。

**記事タイトル:** {$articleTitle}
**関連トピック:** {$topic}

**要求事項:**
1. 5つの異なるアプローチの構成案を作成
2. 各構成は実際のh1, h2, h3タグ形式で出力
3. SEO効果と読者体験の両方を考慮
4. 実用的で具体的な見出し文
5. 論理的な情報階層構造{$regenerateNote}

**構成タイプの例:**
- 完全ガイド型：包括的で網羅性重視
- ステップ型：手順を段階的に説明
- 比較・選択型：複数の選択肢を比較検討
- 問題解決型：課題とその解決策を提示
- 初心者向け型：基礎から応用まで段階的

以下のJSONフォーマットで5つの構成案を出力してください：

```json
[
  {
    \"type\": \"完全ガイド型\",
    \"headings\": \"<h1>{$articleTitle}</h1>\\n<h2>基礎知識と重要性</h2>\\n<h3>定義と概要</h3>\\n<h3>なぜ重要なのか</h3>\\n<h2>具体的な方法とテクニック</h2>\\n<h3>基本的なアプローチ</h3>\\n<h3>応用テクニック</h3>\\n<h2>実践例とケーススタディ</h2>\\n<h3>成功事例の分析</h3>\\n<h3>失敗例から学ぶ教訓</h3>\\n<h2>よくある質問と解決策</h2>\\n<h2>まとめと次のステップ</h2>\"
  },
  {
    \"type\": \"ステップ型\",
    \"headings\": \"<h1>{$articleTitle}</h1>\\n<h2>開始前の準備</h2>\\n<h3>必要な知識・スキル</h3>\\n<h3>準備すべきツール・リソース</h3>\\n<h2>ステップ1: 基礎の理解</h2>\\n<h3>重要な概念の習得</h3>\\n<h3>基本操作の練習</h3>\\n<h2>ステップ2: 実践への応用</h2>\\n<h3>実際の作業手順</h3>\\n<h3>注意点とコツ</h3>\\n<h2>ステップ3: 最適化と改善</h2>\\n<h3>品質向上のポイント</h3>\\n<h3>効率化のテクニック</h3>\\n<h2>成果の測定と評価</h2>\\n<h2>次のレベルへの発展</h2>\"
  },
  {
    \"type\": \"問題解決型\",
    \"headings\": \"<h1>{$articleTitle}</h1>\\n<h2>現状の課題と問題点</h2>\\n<h3>よくある悩みと困りごと</h3>\\n<h3>従来のアプローチの限界</h3>\\n<h2>解決策の全体像</h2>\\n<h3>アプローチの基本方針</h3>\\n<h3>期待できる効果</h3>\\n<h2>具体的な解決方法</h2>\\n<h3>即効性のある対策</h3>\\n<h3>根本的な改善策</h3>\\n<h2>実装時の注意点</h2>\\n<h3>よくある失敗パターン</h3>\\n<h3>回避すべきリスク</h3>\\n<h2>成功のための継続的改善</h2>\"
  },
  {
    \"type\": \"比較・選択型\",
    \"headings\": \"<h1>{$articleTitle}</h1>\\n<h2>選択肢の全体像</h2>\\n<h3>主要な選択肢の概要</h3>\\n<h3>選択基準の重要ポイント</h3>\\n<h2>選択肢A：詳細分析</h2>\\n<h3>特徴とメリット</h3>\\n<h3>デメリットと注意点</h3>\\n<h2>選択肢B：詳細分析</h2>\\n<h3>特徴とメリット</h3>\\n<h3>デメリットと注意点</h3>\\n<h2>選択肢C：詳細分析</h2>\\n<h3>特徴とメリット</h3>\\n<h3>デメリットと注意点</h3>\\n<h2>シーン別おすすめ選択</h2>\\n<h2>最適な判断基準と決め方</h2>\"
  },
  {
    \"type\": \"初心者向け型\",
    \"headings\": \"<h1>{$articleTitle}</h1>\\n<h2>初心者が知っておくべき基礎</h2>\\n<h3>専門用語の理解</h3>\\n<h3>基本的な仕組み</h3>\\n<h2>始める前の心構え</h2>\\n<h3>よくある初心者の誤解</h3>\\n<h3>現実的な目標設定</h3>\\n<h2>初心者でもできる簡単な方法</h2>\\n<h3>最初の一歩</h3>\\n<h3>続けるためのコツ</h3>\\n<h2>レベルアップのための学習</h2>\\n<h3>次に身につけるべきスキル</h3>\\n<h3>おすすめの学習リソース</h3>\\n<h2>困った時のサポート情報</h2>\"
  }
]
```

必ずJSONのみを返してください。説明文は含めないでください。";

        $response = $this->callGeminiAPI($prompt);
        
        // JSONの抽出とパース
        $jsonStart = strpos($response, '[');
        $jsonEnd = strrpos($response, ']');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("JSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $structures = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("構成のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        if (!is_array($structures)) {
            throw new Exception("構成が配列ではありません");
        }
        
        // 構成の検証とクリーンアップ
        $validatedStructures = [];
        foreach ($structures as $structure) {
            if (isset($structure['type'], $structure['headings'])) {
                // HTMLタグをプレーンテキストに変換して表示用に整形
                $headings = $structure['headings'];
                $headings = str_replace('\\n', "\n", $headings);
                $headings = html_entity_decode($headings);
                
                $validatedStructures[] = [
                    'type' => $structure['type'],
                    'headings' => $headings
                ];
            }
        }
        
        if (empty($validatedStructures)) {
            throw new Exception("有効な構成案が得られませんでした");
        }
        
        return $validatedStructures;
    }
    
    public function generateArticleContentForCluster($headingStructure, $articleTitle, $topic) {
        $prompt = "あなたは経験豊富なコンテンツライターです。以下の見出し構造に基づいて、SEO最適化された高品質な記事本文を作成してください。

**記事タイトル:** {$articleTitle}
**メイントピック:** {$topic}
**見出し構造:**
{$headingStructure}

**重要な要求事項:**
1. **SEO最適化:** メインキーワードとロングテールキーワードを自然に含める
2. **実用性:** 読者が実際に行動できる具体的な情報を提供
3. **権威性:** 専門性を示しつつ、信頼できる内容
4. **読みやすさ:** 論理的な構成で理解しやすい文章
5. **ボリューム:** 各見出しに適切なボリューム（200-500文字程度）の本文

**作成指針:**
- 各見出しレベル（h1, h2, h3）に対応した詳細な本文を作成
- 具体例、データ、事例を豊富に含める  
- 読者の疑問に先回りして答える
- アクションを促す実用的なアドバイス
- 最新の情報とトレンドを反映
- 検索意図に完全に対応した内容

**出力形式:**
Markdown形式で出力してください。見出しは#、##、###を使用し、本文は適切な段落で分けてください。

例：
# メインタイトル
ここにh1に対応する導入文を詳細に記述

## サブタイトル1  
ここにh2に対応する詳細な説明と具体例

### 詳細項目1
ここにh3に対応する実用的で具体的な内容

このような形式で、全ての見出しに対応する充実した本文をMarkdown形式で作成してください。";

        $response = $this->callGeminiAPI($prompt);
        return trim($response);
    }
    
    public function generateInternalLinkOptimization($siteUrl, $analysis, $isRegenerate = false, $currentProposals = []) {
        $regeneratePrompt = '';
        if ($isRegenerate && !empty($currentProposals)) {
            $regeneratePrompt = "\n\n既存の提案と重複しない新しい提案を生成してください。\n既存提案: " . json_encode($currentProposals, JSON_UNESCAPED_UNICODE);
        }
        
        $prompt = "あなたは内部リンク最適化の専門家です。以下の分析結果を基に、現在のページの具体的なコンテンツを考慮した詳細な内部リンク最適化を提案してください。

分析対象URL: {$siteUrl}
分析結果: " . json_encode($analysis, JSON_UNESCAPED_UNICODE) . $regeneratePrompt . "

以下の3つのカテゴリに分けて提案してください：

## 1. 既存ページとの内部リンク
サイト内の既存ページをクロールして、親和性の高いページとの内部リンクを提案
現在のページのどの部分（見出しや段落の後）にリンクを挿入すべきかも含めてください

## 2. 新規作成すべきページ
現在存在しないが、作成することでSEO効果を高められるページを提案

## 3. 具体的なリンク挿入箇所の提案
現在のページコンテンツの具体的な箇所にどのようにリンクを追加すべきかを詳細に提案

結果をJSONで出力してください：
```json
{
  \"existingPages\": [
    {
      \"title\": \"既存ページタイトル\",
      \"url\": \"https://example.com/page\",
      \"reason\": \"リンクする理由\",
      \"linkText\": \"推奨アンカーテキスト\",
      \"insertLocation\": \"挿入推奨箇所（例：'h2見出し「〇〇について」の直後の段落'）\",
      \"contextBefore\": \"リンク前の文脈（例：'このような課題を解決するために'）\",
      \"contextAfter\": \"リンク後の文脈（例：'の詳細な手順を確認できます'）\"
    }
  ],
  \"newPageProposals\": [
    {
      \"title\": \"新規ページタイトル\",
      \"description\": \"ページ概要\",
      \"category\": \"カテゴリ\",
      \"keywords\": [\"キーワード1\", \"キーワード2\"],
      \"suggestedLinkLocation\": \"現在のページのどの部分にリンクを追加すべきか\"
    }
  ],
  \"linkInsertionDetails\": [
    {
      \"sectionTitle\": \"対象セクション名\",
      \"insertAfter\": \"この文の後に挿入\",
      \"suggestedText\": \"挿入すべき文章とリンクテキストの例\",
      \"linkType\": \"existing\" または \"new\",
      \"targetPage\": \"リンク先ページ\",
      \"seoReason\": \"SEO観点からの効果説明\"
    }
  ]
}
```";
        
        $response = $this->callGeminiAPI($prompt);
        return $this->parseEnhancedInternalLinkResponse($response);
    }
    
    public function regenerateClusterArticle($currentTitle, $topic) {
        $prompt = "あなたはコンテンツマーケティング専門家です。

現在のクラスター記事タイトル: {$currentTitle}
トピック: {$topic}

現在のタイトルとは異なる、同じトピックに関連する新しいクラスター記事タイトルを1つ生成してください。

要件：
- SEOに効果的なタイトル
- 検索意図を満たす内容
- 既存タイトルとは異なる視点

新しいタイトルのみを出力してください：";
        
        $response = $this->callGeminiAPI($prompt);
        return trim($response);
    }
    
    public function regenerateTopicCluster($currentProposal, $topic) {
        $prompt = "あなたはトピッククラスター専門家です。

現在の提案: " . json_encode($currentProposal, JSON_UNESCAPED_UNICODE) . "
トピック: {$topic}

現在の提案とは異なる新しいトピッククラスター提案を生成してください。

結果をJSONで出力してください：
```json
{
  \"pillarTitle\": \"ピラー記事タイトル\",
  \"clusterTitles\": [
    \"クラスター記事1\",
    \"クラスター記事2\",
    \"クラスター記事3\",
    \"クラスター記事4\",
    \"クラスター記事5\"
  ]
}
```";
        
        $response = $this->callGeminiAPI($prompt);
        return $this->parseTopicClusterResponse($response);
    }
    
    public function regenerateArticleStructure($articleTitle, $topic, $currentStructure) {
        $prompt = "あなたは記事構成の専門家です。

記事タイトル: {$articleTitle}
トピック: {$topic}
現在の構成: {$currentStructure}

現在の構成とは異なる新しい記事構成を生成してください。見出し構造（h1, h2, h3等）を明確に示してください。";
        
        $response = $this->callGeminiAPI($prompt);
        return trim($response);
    }
    
    private function parseInternalLinkResponse($response) {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("内部リンク提案のJSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $result = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("内部リンク提案のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        return [
            'existingPages' => $result['existingPages'] ?? [],
            'newPageProposals' => $result['newPageProposals'] ?? []
        ];
    }
    
    private function parseEnhancedInternalLinkResponse($response) {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("内部リンク提案のJSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $result = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("内部リンク提案のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        return [
            'existingPages' => $result['existingPages'] ?? [],
            'newPageProposals' => $result['newPageProposals'] ?? [],
            'linkInsertionDetails' => $result['linkInsertionDetails'] ?? []
        ];
    }
    
    private function parseTopicClusterResponse($response) {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart === false || $jsonEnd === false) {
            throw new Exception("トピッククラスター提案のJSONが見つかりませんでした");
        }
        
        $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
        $result = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("トピッククラスター提案のJSONパースに失敗しました: " . json_last_error_msg());
        }
        
        return $result;
    }
}
?>