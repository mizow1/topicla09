# SEO改善提案サービス

Gemini AIを活用したWebページSEO分析・改善提案サービスです。

## 機能概要

- **サイト管理**: 複数サイトの登録・管理
- **SEO分析**: Gemini APIによる詳細なSEO分析
- **改善提案**: 優先度付きの具体的な改善提案
- **実装支援**: コピペで使える実装コード提供
- **分析履歴**: 過去の分析結果の管理
- **Analytics連携**: Google Analytics/Search Console連携（設定保存のみ）

## 技術スタック

- **フロントエンド**: HTML5, CSS3, Bootstrap 5, Vanilla JavaScript
- **バックエンド**: PHP 8.x
- **データベース**: MySQL
- **AI**: Google Gemini API (2.0-flash)
- **デプロイ**: さくらレンタルサーバー

## セットアップ手順

### 1. データベースセットアップ

```bash
# MySQLにログイン
mysql -u your_username -p your_database

# データベース構造作成
source database.sql
```

### 2. 設定ファイル編集

`includes/config.php` でデータベース接続情報を設定:

```php
define('DB_HOST', 'mysql**.db.sakura.ne.jp');
define('DB_NAME', 'your_db_name');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### 3. 環境変数設定

`.env` ファイルでGemini API設定:

```
GEMINI_MODEL=gemini-2.0-flash
GEMINI_API_KEY=your_gemini_api_key
```

### 4. アップロード

さくらレンタルサーバーの `/public_html/` にファイルをアップロード:

```
public_html/
├── .htaccess (public/.htaccessの内容)
├── index.php (public/index.phpの内容)
├── css/
├── js/
├── includes/
├── controllers/
├── views/
└── .env
```

## ディレクトリ構造

```
topicla09/
├── public/          # 公開ディレクトリ
│   ├── index.php    # エントリーポイント
│   └── .htaccess    # URL書き換え設定
├── includes/        # 共通ライブラリ
│   ├── config.php   # 設定ファイル
│   ├── database.php # データベースクラス
│   └── gemini_client.php # Gemini API クライアント
├── controllers/     # コントローラー
│   ├── sites.php    # サイト管理
│   ├── analysis.php # SEO分析
│   └── api.php      # API エンドポイント
├── views/          # ビューファイル
│   ├── layout.php   # 共通レイアウト
│   ├── home.php     # ホームページ
│   ├── sites/       # サイト管理画面
│   └── analysis/    # 分析関連画面
├── css/            # スタイルシート
├── js/             # JavaScript
├── database.sql    # データベース構造
├── .env           # 環境変数
└── README.md      # このファイル
```

## 使用方法

### 1. サイト登録

1. 「サイト管理」→「サイト追加」
2. サイト名、ドメイン、説明を入力
3. 必要に応じてGoogle Analytics/Search Console連携設定

### 2. SEO分析実行

1. 「SEO分析」ページでサイトを選択
2. 分析対象URLを入力
3. 「SEO分析を開始」ボタンをクリック
4. 結果ページで詳細な改善提案を確認

### 3. 改善提案の活用

- 優先度（高/中/低）で並び替え
- カテゴリ別フィルタリング
- 実装コードをコピーして適用
- 難易度と予想作業時間を参考に作業計画を立案

## API エンドポイント

### サイト管理
- `GET /api/sites` - サイト一覧取得
- `POST /api/sites` - サイト追加

### 分析
- `POST /api/analyze` - 分析実行
- `GET /api/analysis/{id}` - 分析状況取得

## 分析内容

### 分析カテゴリ
- **meta**: title, description, OGタグ等の最適化
- **technical**: 表示速度、構造化データ等
- **content**: コンテンツ品質、キーワード最適化
- **performance**: Core Web Vitals等のパフォーマンス
- **mobile**: モバイルフレンドリー対応
- **accessibility**: アクセシビリティ向上
- **structure**: URL構造、内部リンク等

### 出力形式
- 結論（コピペ可能な具体的改善内容）
- 詳細説明（根拠と期待効果）
- 実装コード（HTML/CSS等）
- 優先度（high/medium/low）
- 難易度（easy/medium/hard）
- 予想作業時間

## 今後の拡張予定

- Google Analytics/Search Console実データ連携
- 定期分析スケジュール機能
- チーム管理・権限設定
- レポート出力（PDF等）
- 競合サイト比較分析
- A/Bテスト結果分析

## 注意事項

- Gemini APIの利用制限に注意
- 大量のページ分析時はレート制限を考慮
- 分析結果はAIによる提案のため、必ず内容を確認してから実装
- セキュリティ設定（.htaccessのセキュリティヘッダー等）の確認

## トラブルシューティング

### よくある問題

1. **分析がエラーになる**
   - URLが正しく設定されているか確認
   - Gemini APIキーが有効か確認
   - 対象ページがアクセス可能か確認

2. **データベース接続エラー**
   - `includes/config.php` の設定を確認
   - データベースサーバーの稼働状況を確認

3. **表示が崩れる**
   - CSS/JSファイルのパスが正しいか確認
   - ブラウザキャッシュをクリア

## ライセンス

このプロジェクトはMITライセンスの下で公開されています。