-- SEO改善提案サービス用データベース設計

-- サイト情報テーブル
CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'サイト名',
    domain VARCHAR(255) NOT NULL COMMENT 'ドメイン',
    description TEXT COMMENT 'サイト説明',
    ga_property_id VARCHAR(100) COMMENT 'Google Analytics プロパティID',
    gsc_property_url VARCHAR(500) COMMENT 'Google Search Console プロパティURL',
    ga_connected BOOLEAN DEFAULT FALSE COMMENT 'Google Analytics連携状態',
    gsc_connected BOOLEAN DEFAULT FALSE COMMENT 'Google Search Console連携状態',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain),
    UNIQUE KEY unique_domain (domain)
) COMMENT 'サイト情報';

-- 分析履歴テーブル
CREATE TABLE analysis_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    url VARCHAR(1000) NOT NULL COMMENT '分析対象URL',
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending' COMMENT '分析状態',
    gemini_response JSON COMMENT 'Gemini APIからのレスポンス',
    analysis_results JSON COMMENT '分析結果（構造化データ）',
    error_message TEXT COMMENT 'エラーメッセージ',
    processing_time INT COMMENT '処理時間（秒）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_id (site_id),
    INDEX idx_url (url(255)),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) COMMENT '分析履歴';

-- SEO改善提案テーブル
CREATE TABLE seo_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analysis_id INT NOT NULL COMMENT '分析履歴ID',
    category ENUM('technical', 'content', 'performance', 'mobile', 'accessibility', 'meta', 'structure') NOT NULL COMMENT '改善カテゴリ',
    priority ENUM('high', 'medium', 'low') NOT NULL COMMENT '優先度',
    title VARCHAR(500) NOT NULL COMMENT '提案タイトル',
    conclusion TEXT NOT NULL COMMENT '結論',
    explanation TEXT NOT NULL COMMENT '詳細説明',
    implementation_code TEXT COMMENT '実装コード',
    current_score DECIMAL(5,2) COMMENT '現在のスコア',
    expected_improvement DECIMAL(5,2) COMMENT '期待される改善値',
    difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium' COMMENT '実装難易度',
    estimated_hours DECIMAL(4,1) COMMENT '予想作業時間',
    status ENUM('pending', 'in_progress', 'completed', 'skipped') DEFAULT 'pending' COMMENT '対応状況',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (analysis_id) REFERENCES analysis_history(id) ON DELETE CASCADE,
    INDEX idx_analysis_id (analysis_id),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_status (status)
) COMMENT 'SEO改善提案';

-- Google Analytics データテーブル
CREATE TABLE ga_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    metric_date DATE NOT NULL COMMENT 'データ日付',
    page_url VARCHAR(1000) NOT NULL COMMENT 'ページURL',
    page_views INT DEFAULT 0 COMMENT 'ページビュー数',
    unique_page_views INT DEFAULT 0 COMMENT 'ユニークページビュー数',
    avg_time_on_page DECIMAL(8,2) DEFAULT 0 COMMENT '平均滞在時間（秒）',
    bounce_rate DECIMAL(5,2) DEFAULT 0 COMMENT '直帰率（%）',
    exit_rate DECIMAL(5,2) DEFAULT 0 COMMENT '離脱率（%）',
    conversions INT DEFAULT 0 COMMENT 'コンバージョン数',
    conversion_rate DECIMAL(5,2) DEFAULT 0 COMMENT 'コンバージョン率（%）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_date (site_id, metric_date),
    INDEX idx_page_url (page_url(255)),
    UNIQUE KEY unique_site_date_url (site_id, metric_date, page_url(255))
) COMMENT 'Google Analyticsデータ';

-- Google Search Console データテーブル
CREATE TABLE gsc_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    metric_date DATE NOT NULL COMMENT 'データ日付',
    page_url VARCHAR(1000) NOT NULL COMMENT 'ページURL',
    query_text VARCHAR(500) NOT NULL COMMENT '検索クエリ',
    impressions INT DEFAULT 0 COMMENT '表示回数',
    clicks INT DEFAULT 0 COMMENT 'クリック数',
    ctr DECIMAL(5,2) DEFAULT 0 COMMENT 'クリック率（%）',
    position DECIMAL(5,2) DEFAULT 0 COMMENT '平均検索順位',
    country VARCHAR(10) DEFAULT 'jpn' COMMENT '国コード',
    device ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop' COMMENT 'デバイス種別',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_date (site_id, metric_date),
    INDEX idx_query (query_text(255)),
    INDEX idx_page_url (page_url(255)),
    INDEX idx_position (position),
    UNIQUE KEY unique_site_date_url_query (site_id, metric_date, page_url(255), query_text(255))
) COMMENT 'Google Search Consoleデータ';

-- 分析設定テーブル
CREATE TABLE analysis_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    setting_key VARCHAR(100) NOT NULL COMMENT '設定キー',
    setting_value TEXT COMMENT '設定値',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY unique_site_setting (site_id, setting_key)
) COMMENT '分析設定';

-- システムログテーブル
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    level ENUM('info', 'warning', 'error', 'debug') NOT NULL COMMENT 'ログレベル',
    category VARCHAR(50) NOT NULL COMMENT 'ログカテゴリ',
    message TEXT NOT NULL COMMENT 'ログメッセージ',
    context JSON COMMENT 'コンテキスト情報',
    user_ip VARCHAR(45) COMMENT 'ユーザーIP',
    user_agent VARCHAR(500) COMMENT 'ユーザーエージェント',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_level (level),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
) COMMENT 'システムログ';

-- 初期データ挿入
INSERT INTO analysis_settings (site_id, setting_key, setting_value) VALUES 
(0, 'gemini_model', 'gemini-2.0-flash'),
(0, 'analysis_timeout', '300'),
(0, 'max_recommendations', '20'),
(0, 'default_language', 'ja');