# デプロイ手順書

## さくらレンタルサーバーへのデプロイ手順

### 1. データベースセットアップ

1. さくらレンタルサーバーの管理画面にログイン
2. データベース管理から新しいデータベースを作成
3. phpMyAdminまたはMySQLコマンドラインで `database.sql` を実行

```sql
-- database.sqlの内容を実行してテーブルを作成
```

### 2. ファイルアップロード

FTPクライアントまたはファイルマネージャーを使用して、`public_html` フォルダの内容をサーバーの `public_html` ディレクトリにアップロード：

```
public_html/
├── .htaccess
├── index.php
├── .env
├── css/
├── js/
├── includes/
├── controllers/
└── views/
```

### 3. 設定ファイルの編集

`public_html/includes/config.php` でデータベース接続情報が正しく設定されていることを確認：

```php
define('DB_HOST', 'mysql80.mizy.sakura.ne.jp');
define('DB_NAME', 'mizy_topicla09');
define('DB_USER', 'mizy');
define('DB_PASS', '8rjcp4ck');
```

### 4. 環境変数の設定

`public_html/.env` ファイルでGemini API設定を確認：

```
GEMINI_MODEL=gemini-2.0-flash
GEMINI_API_KEY=AIzaSyCp1n02463bR5Mnq9ptrmSWG6i4lbDeNKg
```

### 5. アクセス確認

ブラウザで以下のURLにアクセスして動作確認：

- メインページ: `https://mizy.sakura.ne.jp/topicla09/`
- サイト管理: `https://mizy.sakura.ne.jp/topicla09/sites`
- SEO分析: `https://mizy.sakura.ne.jp/topicla09/analysis`

### 6. 権限設定

必要に応じて以下のディレクトリ・ファイルの権限を設定：

- `public_html/`: 755
- PHP ファイル: 644
- `.htaccess`: 644
- `.env`: 600 (セキュリティのため)

## トラブルシューティング

### よくある問題と解決方法

1. **500 Internal Server Error**
   - `.htaccess` の設定を確認
   - PHPのエラーログを確認
   - ファイル権限を確認

2. **データベース接続エラー**
   - `includes/config.php` の設定を確認
   - データベース名、ユーザー名、パスワードを確認

3. **Gemini API エラー**
   - `.env` ファイルのAPIキーを確認
   - APIキーの有効性を確認

4. **CSS/JS が読み込まれない**
   - ファイルパスを確認
   - `.htaccess` の設定を確認

## セキュリティ設定

### 推奨設定

1. `.env` ファイルの権限を 600 に設定
2. データベースユーザーの権限を最小限に制限
3. 定期的なバックアップの設定
4. SSL証明書の設定確認

### 監視項目

- エラーログの定期確認
- データベースの容量監視
- APIキーの使用量監視

## メンテナンス

### 定期メンテナンス項目

1. データベースの最適化
2. 古い分析履歴の削除
3. ログファイルのローテーション
4. セキュリティアップデートの適用

### バックアップ

定期的に以下をバックアップ：

- データベース全体
- `public_html` ディレクトリ全体
- 設定ファイル (`.env`, `config.php`)

## 更新手順

新機能追加やバグ修正時の更新手順：

1. 開発環境でテスト完了
2. データベースの変更がある場合は事前にバックアップ
3. 新しいファイルをアップロード
4. 必要に応じてデータベーススキーマを更新
5. 動作確認テスト
6. 問題があれば即座にロールバック

## サポート情報

- さくらレンタルサーバー: https://support.sakura.ad.jp/
- PHP設定: https://help.sakura.ad.jp/rs/2150/
- MySQL設定: https://help.sakura.ad.jp/rs/2147/