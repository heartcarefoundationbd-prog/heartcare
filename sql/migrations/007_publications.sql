-- Rename the existing topic records without changing IDs or content, then
-- add publication metadata and files. Safe to re-run on MySQL/MariaDB.
SET @pub_schema = DATABASE();
SET @pub_sql = IF(
  (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications')=0
  AND (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='health_topics')=1,
  'RENAME TABLE health_topics TO publications',
  'SELECT 1'
);
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;

SET @pub_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications' AND COLUMN_NAME='subtitle_en')=0, 'ALTER TABLE publications ADD COLUMN subtitle_en VARCHAR(190) DEFAULT NULL', 'SELECT 1');
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;
SET @pub_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications' AND COLUMN_NAME='subtitle_bn')=0, 'ALTER TABLE publications ADD COLUMN subtitle_bn VARCHAR(190) DEFAULT NULL', 'SELECT 1');
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;
SET @pub_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications' AND COLUMN_NAME='publication_pdf_path')=0, 'ALTER TABLE publications ADD COLUMN publication_pdf_path VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;
SET @pub_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications' AND COLUMN_NAME='publication_pdf_name')=0, 'ALTER TABLE publications ADD COLUMN publication_pdf_name VARCHAR(190) DEFAULT NULL', 'SELECT 1');
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;
SET @pub_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications' AND COLUMN_NAME='international_url')=0, 'ALTER TABLE publications ADD COLUMN international_url VARCHAR(2048) DEFAULT NULL', 'SELECT 1');
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;
SET @pub_sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@pub_schema AND TABLE_NAME='publications' AND INDEX_NAME='idx_publications_slug_en')=0, 'ALTER TABLE publications ADD INDEX idx_publications_slug_en (slug_en)', 'SELECT 1');
PREPARE pub_stmt FROM @pub_sql; EXECUTE pub_stmt; DEALLOCATE PREPARE pub_stmt;
SET @pub_schema = NULL;
SET @pub_sql = NULL;
