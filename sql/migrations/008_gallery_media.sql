-- Add albums and YouTube media to the existing Gallery without removing photos.
-- Re-runs preserve administrator edits; unique YouTube IDs prevent duplicate cards.
SET NAMES utf8mb4;
SET @gallery_schema = DATABASE();

SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND COLUMN_NAME='media_type')=0, 'ALTER TABLE gallery_items ADD COLUMN media_type VARCHAR(16) NOT NULL DEFAULT ''photo''', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;
SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND COLUMN_NAME='category')=0, 'ALTER TABLE gallery_items ADD COLUMN category VARCHAR(32) NOT NULL DEFAULT ''activities''', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;
SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND COLUMN_NAME='album_en')=0, 'ALTER TABLE gallery_items ADD COLUMN album_en VARCHAR(190) DEFAULT NULL', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;
SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND COLUMN_NAME='album_bn')=0, 'ALTER TABLE gallery_items ADD COLUMN album_bn VARCHAR(190) DEFAULT NULL', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;
SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND COLUMN_NAME='youtube_id')=0, 'ALTER TABLE gallery_items ADD COLUMN youtube_id CHAR(11) DEFAULT NULL', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;

ALTER TABLE gallery_items MODIFY image VARCHAR(255) DEFAULT NULL;

SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND INDEX_NAME='uq_gallery_youtube_id')=0, 'ALTER TABLE gallery_items ADD UNIQUE KEY uq_gallery_youtube_id (youtube_id)', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;
SET @gallery_sql = IF((SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@gallery_schema AND TABLE_NAME='gallery_items' AND INDEX_NAME='idx_gallery_category_order')=0, 'ALTER TABLE gallery_items ADD INDEX idx_gallery_category_order (status, category, sort, id)', 'SELECT 1');
PREPARE gallery_stmt FROM @gallery_sql; EXECUTE gallery_stmt; DEALLOCATE PREPARE gallery_stmt;

INSERT IGNORE INTO gallery_items
  (title_en, title_bn, image, sort, status, media_type, category, youtube_id)
VALUES
  ('World Heart Day 2026 | Free Heart Camp & Women''s Heart Health Seminar', NULL, NULL, 10, 1, 'video', 'featured', 'dSTF2YgGTRE'),
  ('World Polio Day 2026 Special Message | Rotary D65 & PolioPlus Committee Member Dr. Triptish Ghose', NULL, NULL, 20, 1, 'video', 'activities', 'oD5uOcbS6ms'),
  ('Know Your Number, Protect Your Heart ❤️ | Heart Health Awareness', NULL, NULL, 30, 1, 'video', 'health', 'oAAdiHfuoYo'),
  ('অল্প বয়সে উচ্চ রক্তচাপ! II HYPERTENSION IN YOUNG AGE!', NULL, NULL, 40, 1, 'video', 'health', 'heStdhqm1Ec'),
  ('MOVE FOR HEART | PLAY FOR HEART | FOOTBALL FOR HEART', NULL, NULL, 50, 1, 'video', 'world-heart-day', 'gpp0o2NJieU'),
  ('Football For Heart – World Heart Day 2025 Event', NULL, NULL, 60, 1, 'video', 'activities', 'SBvj5SkRge8'),
  ('রোটারির বন্যা সহায়তা কার্যক্রম ও জরুরি স্বাস্থ্যসেবা', NULL, NULL, 70, 1, 'video', 'activities', 'pfrQBAE1N4M'),
  ('USE HEART FOR EVERY HEART | Free Cardiac Health Camp', NULL, NULL, 80, 1, 'video', 'activities', '5ci9VcQY4GA'),
  ('Heart Care Foundation Theme Song & Awareness Melody', NULL, NULL, 90, 1, 'video', 'cultural', 'lq_jCPpdCHo'),
  ('USE HEART KNOW HEART | World Heart Day Campaign', NULL, NULL, 100, 1, 'video', 'world-heart-day', 'wLuHv8Fm-3g'),
  ('হার্ট কেয়ার ফাউন্ডেশন, হৃদরোগীদের আশার আলো', NULL, NULL, 110, 1, 'video', 'activities', 'dCdrGjKgrZ0'),
  ('হৃদরোগ বিশ্বের এক নম্বর ঘাতক ব্যাধি— কারণ ও প্রতিকার', NULL, NULL, 120, 1, 'video', 'health', '1u-AEyL0ktY'),
  ('৩০-৪০ বছর বয়সীদের হার্ট অ্যাটাক ঝুঁকি !', NULL, NULL, 130, 1, 'video', 'heart-care', 'UJwutxMfaJo'),
  ('হার্ট সুস্থ রাখতে কী করণীয় | Use Heart To Manage Cardiovascular Diseases', NULL, NULL, 140, 1, 'video', 'health', 'er9Vhsqe42U');

SET @gallery_schema = NULL;
SET @gallery_sql = NULL;
