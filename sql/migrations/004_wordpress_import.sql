-- Track imported WordPress IDs so repeat imports update existing records.
-- Nullable unique keys leave manually-created content unaffected.
SET @hc_schema = DATABASE();

SET @hc_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @hc_schema AND TABLE_NAME = 'news' AND COLUMN_NAME = 'source_wp_id') = 0,
  'ALTER TABLE news ADD COLUMN source_wp_id BIGINT UNSIGNED NULL, ADD UNIQUE KEY uq_news_source_wp_id (source_wp_id)',
  'SELECT 1'
);
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;

SET @hc_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @hc_schema AND TABLE_NAME = 'events' AND COLUMN_NAME = 'source_wp_id') = 0,
  'ALTER TABLE events ADD COLUMN source_wp_id BIGINT UNSIGNED NULL, ADD UNIQUE KEY uq_events_source_wp_id (source_wp_id)',
  'SELECT 1'
);
PREPARE hc_stmt FROM @hc_sql; EXECUTE hc_stmt; DEALLOCATE PREPARE hc_stmt;
