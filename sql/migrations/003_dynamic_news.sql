-- HeartCare migration 003 — article metadata, original language, images and tags.
-- Additive only. Apply to a backed-up/staging database first; do not import install.sql
-- into an existing installation. Re-running this migration is safe on MySQL 5.7+
-- and MariaDB 10.3+.
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS news_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  news_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_news_images_order (news_id, sort_order, id),
  CONSTRAINT fk_news_images_news
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tags_name (name),
  UNIQUE KEY uq_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news_tags (
  news_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (news_id, tag_id),
  KEY idx_news_tags_tag (tag_id),
  CONSTRAINT fk_news_tags_news
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
  CONSTRAINT fk_news_tags_tag
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news_slug_redirects (
  old_slug VARCHAR(190) NOT NULL PRIMARY KEY,
  news_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_news_slug_redirects_news (news_id),
  CONSTRAINT fk_news_slug_redirects_news
    FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conditional column additions keep the migration repeatable on MySQL 5.7,
-- which does not support ALTER TABLE ... ADD COLUMN IF NOT EXISTS.
SET @hc_schema = DATABASE();

SET @hc_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @hc_schema AND TABLE_NAME = 'news' AND COLUMN_NAME = 'content_language') = 0,
  'ALTER TABLE news ADD COLUMN content_language CHAR(2) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_sql = IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA = @hc_schema AND TABLE_NAME = 'news' AND INDEX_NAME = 'idx_news_list') = 0,
  'ALTER TABLE news ADD INDEX idx_news_list (status, published_at, id)',
  'SELECT 1'
);
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_table = 'news';

-- SEO columns are added individually so partially migrated databases can be repaired
-- safely by re-running this file.
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_title')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_title VARCHAR(190) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_description')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_description VARCHAR(320) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_keywords')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_keywords VARCHAR(500) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_table = 'pages';
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_title')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_title VARCHAR(190) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_description')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_description VARCHAR(320) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_keywords')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_keywords VARCHAR(500) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_table = 'notices';
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_title')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_title VARCHAR(190) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_description')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_description VARCHAR(320) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_keywords')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_keywords VARCHAR(500) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_table = 'events';
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_title')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_title VARCHAR(190) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_description')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_description VARCHAR(320) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_keywords')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_keywords VARCHAR(500) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_table = 'projects';
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_title')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_title VARCHAR(190) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_description')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_description VARCHAR(320) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_keywords')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_keywords VARCHAR(500) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_table = 'health_topics';
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_title')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_title VARCHAR(190) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_description')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_description VARCHAR(320) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME=@hc_table AND COLUMN_NAME='meta_keywords')=0, CONCAT('ALTER TABLE `',@hc_table,'` ADD COLUMN meta_keywords VARCHAR(500) NULL'), 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_sql = NULL;
SET @hc_schema = NULL;
SET @hc_table = NULL;
