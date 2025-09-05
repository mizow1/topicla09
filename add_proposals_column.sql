-- proposalsフィールドをseo_recommendationsテーブルに追加
ALTER TABLE seo_recommendations 
ADD COLUMN proposals JSON COMMENT '提案オプション（5案のリスト）' AFTER implementation_code;