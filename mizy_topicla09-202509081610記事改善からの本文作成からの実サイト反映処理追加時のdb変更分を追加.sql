-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: mysql3102.db.sakura.ne.jp
-- 生成日時: 2025 年 9 月 08 日 16:10
-- サーバのバージョン： 8.0.39
-- PHP のバージョン: 8.2.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `mizy_topicla09`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `analysis_history`
--

CREATE TABLE `analysis_history` (
  `id` int NOT NULL,
  `site_id` int NOT NULL,
  `url` varchar(1000) NOT NULL COMMENT '分析対象URL',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending' COMMENT '分析状態',
  `gemini_response` json DEFAULT NULL COMMENT 'Gemini APIからのレスポンス',
  `analysis_results` json DEFAULT NULL COMMENT '分析結果（構造化データ）',
  `error_message` text COMMENT 'エラーメッセージ',
  `processing_time` int DEFAULT NULL COMMENT '処理時間（秒）',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分析履歴';

-- --------------------------------------------------------

--
-- テーブルの構造 `analysis_settings`
--

CREATE TABLE `analysis_settings` (
  `id` int NOT NULL,
  `site_id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL COMMENT '設定キー',
  `setting_value` text COMMENT '設定値',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='分析設定';

-- --------------------------------------------------------

--
-- テーブルの構造 `ga_metrics`
--

CREATE TABLE `ga_metrics` (
  `id` int NOT NULL,
  `site_id` int NOT NULL,
  `metric_date` date NOT NULL COMMENT 'データ日付',
  `page_url` varchar(1000) NOT NULL COMMENT 'ページURL',
  `page_views` int DEFAULT '0' COMMENT 'ページビュー数',
  `unique_page_views` int DEFAULT '0' COMMENT 'ユニークページビュー数',
  `avg_time_on_page` decimal(8,2) DEFAULT '0.00' COMMENT '平均滞在時間（秒）',
  `bounce_rate` decimal(5,2) DEFAULT '0.00' COMMENT '直帰率（%）',
  `exit_rate` decimal(5,2) DEFAULT '0.00' COMMENT '離脱率（%）',
  `conversions` int DEFAULT '0' COMMENT 'コンバージョン数',
  `conversion_rate` decimal(5,2) DEFAULT '0.00' COMMENT 'コンバージョン率（%）',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Google Analyticsデータ';

-- --------------------------------------------------------

--
-- テーブルの構造 `gsc_metrics`
--

CREATE TABLE `gsc_metrics` (
  `id` int NOT NULL,
  `site_id` int NOT NULL,
  `metric_date` date NOT NULL COMMENT 'データ日付',
  `page_url` varchar(1000) NOT NULL COMMENT 'ページURL',
  `query_text` varchar(500) NOT NULL COMMENT '検索クエリ',
  `impressions` int DEFAULT '0' COMMENT '表示回数',
  `clicks` int DEFAULT '0' COMMENT 'クリック数',
  `ctr` decimal(5,2) DEFAULT '0.00' COMMENT 'クリック率（%）',
  `position` decimal(5,2) DEFAULT '0.00' COMMENT '平均検索順位',
  `country` varchar(10) DEFAULT 'jpn' COMMENT '国コード',
  `device` enum('desktop','mobile','tablet') DEFAULT 'desktop' COMMENT 'デバイス種別',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='Google Search Consoleデータ';

-- --------------------------------------------------------

--
-- テーブルの構造 `saved_articles`
--

CREATE TABLE `saved_articles` (
  `id` int NOT NULL,
  `title` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `structure` text COLLATE utf8mb4_unicode_ci,
  `site_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `wordpress_post_id` int DEFAULT NULL,
  `wordpress_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `seo_recommendations`
--

CREATE TABLE `seo_recommendations` (
  `id` int NOT NULL,
  `analysis_id` int NOT NULL COMMENT '分析履歴ID',
  `category` enum('technical','content','performance','mobile','accessibility','meta','structure') NOT NULL COMMENT '改善カテゴリ',
  `priority` enum('high','medium','low') NOT NULL COMMENT '優先度',
  `title` varchar(500) NOT NULL COMMENT '提案タイトル',
  `conclusion` text NOT NULL COMMENT '結論',
  `explanation` text NOT NULL COMMENT '詳細説明',
  `implementation_code` text COMMENT '実装コード',
  `proposals` json DEFAULT NULL COMMENT '提案オプション（5案のリスト）',
  `current_score` decimal(5,2) DEFAULT NULL COMMENT '現在のスコア',
  `expected_improvement` decimal(5,2) DEFAULT NULL COMMENT '期待される改善値',
  `difficulty` enum('easy','medium','hard') DEFAULT 'medium' COMMENT '実装難易度',
  `estimated_hours` decimal(4,1) DEFAULT NULL COMMENT '予想作業時間',
  `status` enum('pending','in_progress','completed','skipped') DEFAULT 'pending' COMMENT '対応状況',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='SEO改善提案';

-- --------------------------------------------------------

--
-- テーブルの構造 `sites`
--

CREATE TABLE `sites` (
  `id` int NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'サイト名',
  `domain` varchar(255) NOT NULL COMMENT 'ドメイン',
  `description` text COMMENT 'サイト説明',
  `ga_property_id` varchar(100) DEFAULT NULL COMMENT 'Google Analytics プロパティID',
  `gsc_property_url` varchar(500) DEFAULT NULL COMMENT 'Google Search Console プロパティURL',
  `ga_connected` tinyint(1) DEFAULT '0' COMMENT 'Google Analytics連携状態',
  `gsc_connected` tinyint(1) DEFAULT '0' COMMENT 'Google Search Console連携状態',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='サイト情報';

-- --------------------------------------------------------

--
-- テーブルの構造 `system_logs`
--

CREATE TABLE `system_logs` (
  `id` int NOT NULL,
  `level` enum('info','warning','error','debug') NOT NULL COMMENT 'ログレベル',
  `category` varchar(50) NOT NULL COMMENT 'ログカテゴリ',
  `message` text NOT NULL COMMENT 'ログメッセージ',
  `context` json DEFAULT NULL COMMENT 'コンテキスト情報',
  `user_ip` varchar(45) DEFAULT NULL COMMENT 'ユーザーIP',
  `user_agent` varchar(500) DEFAULT NULL COMMENT 'ユーザーエージェント',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci COMMENT='システムログ';

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `analysis_history`
--
ALTER TABLE `analysis_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_site_id` (`site_id`),
  ADD KEY `idx_url` (`url`(255)),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- テーブルのインデックス `analysis_settings`
--
ALTER TABLE `analysis_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_site_setting` (`site_id`,`setting_key`);

--
-- テーブルのインデックス `ga_metrics`
--
ALTER TABLE `ga_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_site_date_url` (`site_id`,`metric_date`,`page_url`(255)),
  ADD KEY `idx_site_date` (`site_id`,`metric_date`),
  ADD KEY `idx_page_url` (`page_url`(255));

--
-- テーブルのインデックス `gsc_metrics`
--
ALTER TABLE `gsc_metrics`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_site_date_url_query` (`site_id`,`metric_date`,`page_url`(255),`query_text`(255)),
  ADD KEY `idx_site_date` (`site_id`,`metric_date`),
  ADD KEY `idx_query` (`query_text`(255)),
  ADD KEY `idx_page_url` (`page_url`(255)),
  ADD KEY `idx_position` (`position`);

--
-- テーブルのインデックス `saved_articles`
--
ALTER TABLE `saved_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_title` (`title`(100)),
  ADD KEY `idx_wordpress_post_id` (`wordpress_post_id`);

--
-- テーブルのインデックス `seo_recommendations`
--
ALTER TABLE `seo_recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_analysis_id` (`analysis_id`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- テーブルのインデックス `sites`
--
ALTER TABLE `sites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_domain` (`domain`),
  ADD KEY `idx_domain` (`domain`);

--
-- テーブルのインデックス `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_level` (`level`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `analysis_history`
--
ALTER TABLE `analysis_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `analysis_settings`
--
ALTER TABLE `analysis_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `ga_metrics`
--
ALTER TABLE `ga_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `gsc_metrics`
--
ALTER TABLE `gsc_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `saved_articles`
--
ALTER TABLE `saved_articles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `seo_recommendations`
--
ALTER TABLE `seo_recommendations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `sites`
--
ALTER TABLE `sites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- テーブルの AUTO_INCREMENT `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- ダンプしたテーブルの制約
--

--
-- テーブルの制約 `analysis_history`
--
ALTER TABLE `analysis_history`
  ADD CONSTRAINT `analysis_history_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `analysis_settings`
--
ALTER TABLE `analysis_settings`
  ADD CONSTRAINT `analysis_settings_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `ga_metrics`
--
ALTER TABLE `ga_metrics`
  ADD CONSTRAINT `ga_metrics_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `gsc_metrics`
--
ALTER TABLE `gsc_metrics`
  ADD CONSTRAINT `gsc_metrics_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE;

--
-- テーブルの制約 `seo_recommendations`
--
ALTER TABLE `seo_recommendations`
  ADD CONSTRAINT `seo_recommendations_ibfk_1` FOREIGN KEY (`analysis_id`) REFERENCES `analysis_history` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
