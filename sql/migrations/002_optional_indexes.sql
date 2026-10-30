-- Optional speed-ups for the ORIGINAL tables. Run each line once; an error such as
-- "Duplicate key name" simply means that index already exists and can be ignored.
ALTER TABLE news          ADD INDEX idx_news_slug_en (slug_en);
ALTER TABLE news          ADD INDEX idx_news_list (status, published_at);
ALTER TABLE events        ADD INDEX idx_events_list (status, event_date);
SET @topics_table = IF(
  (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='publications')=1,
  'publications',
  'health_topics'
);
SET @topics_index_sql = CONCAT('ALTER TABLE `', @topics_table, '` ADD INDEX idx_publications_slug_en (slug_en)');
PREPARE topics_index_stmt FROM @topics_index_sql; EXECUTE topics_index_stmt; DEALLOCATE PREPARE topics_index_stmt;
SET @topics_table = NULL;
SET @topics_index_sql = NULL;
ALTER TABLE pages         ADD INDEX idx_pages_slug_en (slug_en);
ALTER TABLE submissions   ADD INDEX idx_sub_inbox (type, is_read, created_at);
