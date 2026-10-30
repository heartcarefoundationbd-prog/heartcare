-- Smart homepage cards. Additive and repeatable on MySQL 5.7+ / MariaDB 10.3+.
-- Existing card IDs, text, media paths, links, and publication state are retained.
SET NAMES utf8mb4;
SET @hc_schema = DATABASE();

CREATE TABLE IF NOT EXISTS home_card_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL,
  name_en VARCHAR(100) NOT NULL,
  name_bn VARCHAR(100) DEFAULT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_home_card_category_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO home_card_categories (slug, name_en, name_bn, sort_order) VALUES
  ('mission', 'Mission', 'মিশন', 1),
  ('programs', 'Programs', 'কার্যক্রম', 2),
  ('impact', 'Impact', 'প্রভাব', 3),
  ('campaigns', 'Campaigns', 'প্রচারণা', 4),
  ('community', 'Community', 'কমিউনিটি', 5),
  ('health-services', 'Health services', 'স্বাস্থ্যসেবা', 6),
  ('volunteer', 'Volunteer', 'স্বেচ্ছাসেবক', 7),
  ('research', 'Research', 'গবেষণা', 8);

SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='subtitle_en')=0, 'ALTER TABLE home_cards ADD COLUMN subtitle_en VARCHAR(190) NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='subtitle_bn')=0, 'ALTER TABLE home_cards ADD COLUMN subtitle_bn VARCHAR(190) NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='content_type')=0, 'ALTER TABLE home_cards ADD COLUMN content_type VARCHAR(32) NULL DEFAULT NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='category_id')=0, 'ALTER TABLE home_cards ADD COLUMN category_id INT NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='tags')=0, 'ALTER TABLE home_cards ADD COLUMN tags VARCHAR(500) NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='sort_order')=0, 'ALTER TABLE home_cards ADD COLUMN sort_order INT NULL DEFAULT NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='is_featured')=0, 'ALTER TABLE home_cards ADD COLUMN is_featured TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='starts_at')=0, 'ALTER TABLE home_cards ADD COLUMN starts_at DATETIME NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='ends_at')=0, 'ALTER TABLE home_cards ADD COLUMN ends_at DATETIME NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND COLUMN_NAME='is_archived')=0, 'ALTER TABLE home_cards ADD COLUMN is_archived TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

ALTER TABLE home_cards
  MODIFY slot INT NOT NULL DEFAULT 0,
  MODIFY title_en VARCHAR(190) NOT NULL,
  MODIFY title_bn VARCHAR(190) NULL,
  MODIFY body_en TEXT NULL,
  MODIFY body_bn TEXT NULL,
  MODIFY image VARCHAR(255) NULL,
  MODIFY link_path VARCHAR(2048) NOT NULL DEFAULT '';

SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND INDEX_NAME='idx_home_cards_visibility')=0, 'ALTER TABLE home_cards ADD INDEX idx_home_cards_visibility (status, is_archived, sort_order, slot)', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND INDEX_NAME='idx_home_cards_category')=0, 'ALTER TABLE home_cards ADD INDEX idx_home_cards_category (category_id)', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = IF((SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=@hc_schema AND TABLE_NAME='home_cards' AND CONSTRAINT_NAME='fk_home_cards_category')=0, 'ALTER TABLE home_cards ADD CONSTRAINT fk_home_cards_category FOREIGN KEY (category_id) REFERENCES home_card_categories(id) ON DELETE SET NULL', 'SELECT 1');
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

CREATE TABLE IF NOT EXISTS home_card_stats (
  id INT AUTO_INCREMENT PRIMARY KEY,
  card_id INT NOT NULL,
  label_en VARCHAR(120) NOT NULL,
  label_bn VARCHAR(120) DEFAULT NULL,
  value VARCHAR(64) NOT NULL,
  icon_key VARCHAR(32) NOT NULL DEFAULT 'heart',
  color_key VARCHAR(24) NOT NULL DEFAULT 'brand',
  animate_counter TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_home_card_stats_order (card_id, sort_order, id),
  CONSTRAINT fk_home_card_stats_card FOREIGN KEY (card_id) REFERENCES home_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS home_card_buttons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  card_id INT NOT NULL,
  label_en VARCHAR(80) NOT NULL,
  label_bn VARCHAR(80) DEFAULT NULL,
  url VARCHAR(2048) NOT NULL,
  button_type VARCHAR(24) NOT NULL DEFAULT 'primary',
  sort_order INT NOT NULL DEFAULT 0,
  KEY idx_home_card_buttons_order (card_id, sort_order, id),
  CONSTRAINT fk_home_card_buttons_card FOREIGN KEY (card_id) REFERENCES home_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS home_card_media (
  card_id INT NOT NULL PRIMARY KEY,
  visual_type VARCHAR(16) NOT NULL DEFAULT 'icon',
  icon_key VARCHAR(32) NOT NULL DEFAULT 'heart',
  image_path VARCHAR(255) DEFAULT NULL,
  video_path VARCHAR(255) DEFAULT NULL,
  poster_path VARCHAR(255) DEFAULT NULL,
  KEY idx_home_card_media_type (visual_type),
  CONSTRAINT fk_home_card_media_card FOREIGN KEY (card_id) REFERENCES home_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS home_card_styles (
  card_id INT NOT NULL PRIMARY KEY,
  background_type VARCHAR(16) NOT NULL DEFAULT 'solid',
  background_color CHAR(7) NOT NULL DEFAULT '#FFFFFF',
  gradient_start CHAR(7) NOT NULL DEFAULT '#FFFFFF',
  gradient_end CHAR(7) NOT NULL DEFAULT '#F7F7F8',
  pattern_key VARCHAR(16) NOT NULL DEFAULT 'none',
  theme VARCHAR(16) NOT NULL DEFAULT 'light',
  border_radius VARCHAR(16) NOT NULL DEFAULT 'large',
  shadow VARCHAR(16) NOT NULL DEFAULT 'soft',
  spacing VARCHAR(16) NOT NULL DEFAULT 'comfortable',
  overlay_color CHAR(7) NOT NULL DEFAULT '#1B1B1F',
  overlay_opacity TINYINT UNSIGNED NOT NULL DEFAULT 30,
  CONSTRAINT fk_home_card_styles_card FOREIGN KEY (card_id) REFERENCES home_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS home_card_animations (
  card_id INT NOT NULL PRIMARY KEY,
  animation_type VARCHAR(16) NOT NULL DEFAULT 'none',
  trigger_type VARCHAR(16) NOT NULL DEFAULT 'scroll',
  duration_ms SMALLINT UNSIGNED NOT NULL DEFAULT 500,
  delay_ms SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  hover_effect VARCHAR(16) NOT NULL DEFAULT 'elevation',
  heartbeat TINYINT(1) NOT NULL DEFAULT 0,
  CONSTRAINT fk_home_card_animations_card FOREIGN KEY (card_id) REFERENCES home_cards(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @hc_schema = NULL;
SET @hc_sql = NULL;
