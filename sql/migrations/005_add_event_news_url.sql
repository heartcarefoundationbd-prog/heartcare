-- Add an optional related news URL to existing event records without changing
-- publication status, event dates, or any existing content.
SET @hc_schema = DATABASE();
SET @hc_sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = @hc_schema AND TABLE_NAME = 'events' AND COLUMN_NAME = 'related_news_url') = 0,
  'ALTER TABLE events ADD COLUMN related_news_url VARCHAR(2048) NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE hc_stmt FROM @hc_sql;
EXECUTE hc_stmt;
DEALLOCATE PREPARE hc_stmt;
SET @hc_sql = NULL;
SET @hc_schema = NULL;
