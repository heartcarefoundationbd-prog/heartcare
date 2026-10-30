SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS heartcare
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE heartcare;

CREATE TABLE settings (
  skey   VARCHAR(64) PRIMARY KEY,
  svalue TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor','translator') DEFAULT 'editor',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug_en VARCHAR(190) NOT NULL,
  slug_bn VARCHAR(190) DEFAULT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  body_en MEDIUMTEXT,
  body_bn MEDIUMTEXT,
  meta_title VARCHAR(190) DEFAULT NULL,
  meta_description VARCHAR(320) DEFAULT NULL,
  meta_keywords VARCHAR(500) DEFAULT NULL,
  status TINYINT(1) DEFAULT 1,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE news (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug_en VARCHAR(190) NOT NULL,
  slug_bn VARCHAR(190) DEFAULT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  excerpt_en VARCHAR(400),
  excerpt_bn VARCHAR(400),
  body_en MEDIUMTEXT,
  body_bn MEDIUMTEXT,
  image VARCHAR(255) DEFAULT NULL,
  category VARCHAR(60) DEFAULT 'News',
  published_at DATE NOT NULL,
  content_language CHAR(2) DEFAULT NULL,
  meta_title VARCHAR(190) DEFAULT NULL,
  meta_description VARCHAR(320) DEFAULT NULL,
  meta_keywords VARCHAR(500) DEFAULT NULL,
  source_wp_id BIGINT UNSIGNED DEFAULT NULL UNIQUE,
  status TINYINT(1) DEFAULT 1,
  KEY idx_news_list (status, published_at, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE news_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  news_id INT NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_news_images_order (news_id, sort_order, id),
  CONSTRAINT fk_news_images_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE tags (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tags_name (name),
  UNIQUE KEY uq_tags_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE news_tags (
  news_id INT NOT NULL,
  tag_id INT NOT NULL,
  PRIMARY KEY (news_id, tag_id),
  KEY idx_news_tags_tag (tag_id),
  CONSTRAINT fk_news_tags_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE,
  CONSTRAINT fk_news_tags_tag FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE news_slug_redirects (
  old_slug VARCHAR(190) NOT NULL PRIMARY KEY,
  news_id INT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_news_slug_redirects_news (news_id),
  CONSTRAINT fk_news_slug_redirects_news FOREIGN KEY (news_id) REFERENCES news(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug_en VARCHAR(190) NOT NULL,
  slug_bn VARCHAR(190) DEFAULT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  body_en MEDIUMTEXT,
  body_bn MEDIUMTEXT,
  location_en VARCHAR(190),
  location_bn VARCHAR(190),
  image VARCHAR(255) DEFAULT NULL,
  related_news_url VARCHAR(2048) DEFAULT NULL,
  event_date DATE NOT NULL,
  meta_title VARCHAR(190) DEFAULT NULL,
  meta_description VARCHAR(320) DEFAULT NULL,
  meta_keywords VARCHAR(500) DEFAULT NULL,
  source_wp_id BIGINT UNSIGNED DEFAULT NULL UNIQUE,
  status TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE publications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug_en VARCHAR(190) NOT NULL,
  slug_bn VARCHAR(190) DEFAULT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  subtitle_en VARCHAR(190) DEFAULT NULL,
  subtitle_bn VARCHAR(190) DEFAULT NULL,
  summary_en VARCHAR(400),
  summary_bn VARCHAR(400),
  body_en MEDIUMTEXT,
  body_bn MEDIUMTEXT,
  icon VARCHAR(40) DEFAULT 'heart',
  cover_image VARCHAR(255) DEFAULT NULL,
  publication_pdf_path VARCHAR(255) DEFAULT NULL,
  publication_pdf_name VARCHAR(190) DEFAULT NULL,
  international_url VARCHAR(2048) DEFAULT NULL,
  sort INT DEFAULT 0,
  meta_title VARCHAR(190) DEFAULT NULL,
  meta_description VARCHAR(320) DEFAULT NULL,
  meta_keywords VARCHAR(500) DEFAULT NULL,
  status TINYINT(1) DEFAULT 1,
  KEY idx_publications_slug_en (slug_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE team (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_en VARCHAR(120) NOT NULL,
  name_bn VARCHAR(120) DEFAULT NULL,
  role_en VARCHAR(120),
  role_bn VARCHAR(120),
  bio_en TEXT,
  bio_bn TEXT,
  photo VARCHAR(255) DEFAULT NULL,
  sort INT DEFAULT 0,
  status TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE donations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190),
  phone VARCHAR(40),
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(30) NOT NULL,
  trx_id VARCHAR(80),
  message VARCHAR(400),
  status ENUM('pending','paid','failed') DEFAULT 'pending',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE submissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('contact','member','volunteer','partner') NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190),
  phone VARCHAR(40),
  subject VARCHAR(190),
  message TEXT,
  lang VARCHAR(5) DEFAULT 'en',
  is_read TINYINT(1) DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE newsletter (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  lang VARCHAR(5) DEFAULT 'en',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Seed: settings ----------
INSERT INTO settings (skey, svalue) VALUES
('site_name_en','Heart Care Foundation Comilla'),
('site_name_bn','হার্ট কেয়ার ফাউন্ডেশন কুমিল্লা'),
('phone','+880 1700 000000'),
('email','info@heartcarecomilla.org'),
('address_en','Kandirpar, Cumilla 3500, Bangladesh'),
('address_bn','কান্দিরপাড়, কুমিল্লা ৩৫০০, বাংলাদেশ'),
('facebook','https://facebook.com/'),
('youtube','https://youtube.com/');

-- ---------- Seed: pages ----------
INSERT INTO pages (slug_en,slug_bn,title_en,title_bn,body_en,body_bn) VALUES
('mission','mission','Our Mission','আমাদের লক্ষ্য',
 '<p>Heart Care Foundation Comilla works to reduce preventable deaths from cardiovascular disease in Cumilla and the wider Bangladesh region through awareness, free screening, and community support.</p>',
 '<p>হার্ট কেয়ার ফাউন্ডেশন কুমিল্লা সচেতনতা, বিনামূল্যে স্ক্রিনিং ও কমিউনিটি সহায়তার মাধ্যমে কুমিল্লা ও বাংলাদেশে হৃদরোগজনিত মৃত্যু কমানোর লক্ষ্যে কাজ করে।</p>'),
('about','about','About Us','আমাদের সম্পর্কে',
 '<p>We are a non-profit organisation and a member of the World Heart Federation.</p>',
 '<p>আমরা একটি অলাভজনক প্রতিষ্ঠান এবং ওয়ার্ল্ড হার্ট ফেডারেশনের সদস্য।</p>'),
('privacy','privacy','Privacy Policy','গোপনীয়তা নীতি',
 '<p>We do not sell your data. Contact us to request removal of any information you have submitted.</p>',
 '<p>আমরা আপনার তথ্য বিক্রি করি না। আপনার জমা দেওয়া তথ্য মুছে ফেলতে আমাদের সাথে যোগাযোগ করুন।</p>');

-- ---------- Seed: news ----------
INSERT INTO news (slug_en,slug_bn,title_en,title_bn,excerpt_en,excerpt_bn,body_en,body_bn,category,published_at) VALUES
('free-heart-camp-2026','free-heart-camp-2026','Free heart screening camp held in Cumilla','কুমিল্লায় বিনামূল্যে হৃদরোগ স্ক্রিনিং ক্যাম্প',
 'Over 400 people received free blood pressure and ECG screening.','৪০০-এর বেশি মানুষ বিনামূল্যে রক্তচাপ ও ইসিজি স্ক্রিনিং পেয়েছেন।',
 '<p>More than 400 residents received free blood pressure, blood sugar and ECG screening at our community camp.</p>',
 '<p>আমাদের কমিউনিটি ক্যাম্পে ৪০০-এর বেশি মানুষ বিনামূল্যে রক্তচাপ, রক্তে সুগার ও ইসিজি স্ক্রিনিং পেয়েছেন।</p>',
 'Camp','2026-09-20'),
('world-heart-day-2026','world-heart-day-2026','World Heart Day 2026 observed','বিশ্ব হৃদয় দিবস ২০২৬ পালিত',
 'Walk, rally and awareness session across Cumilla.','কুমিল্লায় শোভাযাত্রা ও সচেতনতামূলক সভা অনুষ্ঠিত।',
 '<p>We observed World Heart Day with a rally and an awareness session on heart-healthy living.</p>',
 '<p>হৃদয়-বান্ধব জীবনযাপন নিয়ে শোভাযাত্রা ও সচেতনতামূলক সভার মাধ্যমে আমরা বিশ্ব হৃদয় দিবস পালন করেছি।</p>',
 'Event','2026-09-29'),
('school-awareness-program','school-awareness-program','School awareness programme launched','স্কুল সচেতনতা কার্যক্রম শুরু',
 'Tobacco and healthy eating sessions in 12 schools.','১২টি স্কুলে তামাক ও স্বাস্থ্যকর খাদ্য নিয়ে সেশন।',
 '<p>Our team visited 12 schools to teach students about tobacco risks and healthy eating.</p>',
 '<p>আমাদের দল ১২টি স্কুলে গিয়ে শিক্ষার্থীদের তামাকের ঝুঁকি ও স্বাস্থ্যকর খাদ্য সম্পর্কে শিখিয়েছে।</p>',
 'Programme','2026-08-10');

-- ---------- Seed: events ----------
INSERT INTO events (slug_en,slug_bn,title_en,title_bn,body_en,body_bn,location_en,location_bn,event_date) VALUES
('world-heart-day','world-heart-day','World Heart Day Rally','বিশ্ব হৃদয় দিবস শোভাযাত্রা',
 '<p>Join our rally and free screening event.</p>','<p>আমাদের শোভাযাত্রা ও বিনামূল্যে স্ক্রিনিং অনুষ্ঠানে যোগ দিন।</p>',
 'Kandirpar, Cumilla','কান্দিরপাড়, কুমিল্লা','2026-09-29'),
('bp-screening-day','bp-screening-day','Free BP Screening Day','বিনামূল্যে রক্তচাপ স্ক্রিনিং দিবস',
 '<p>Walk in and get your blood pressure checked for free.</p>','<p>সরাসরি এসে বিনামূল্যে রক্তচাপ পরীক্ষা করান।</p>',
 'Foundation Office','ফাউন্ডেশন অফিস','2026-11-14'),
('stroke-day','stroke-day','World Stroke Day Seminar','বিশ্ব স্ট্রোক দিবস সেমিনার',
 '<p>Awareness seminar for families and caregivers.</p>','<p>পরিবার ও যত্নদাতাদের জন্য সচেতনতামূলক সেমিনার।</p>',
 'Cumilla Press Club','কুমিল্লা প্রেস ক্লাব','2026-10-29');

-- ---------- Seed: health topics ----------
INSERT INTO publications (slug_en,slug_bn,title_en,title_bn,summary_en,summary_bn,body_en,body_bn,icon,sort) VALUES
('hypertension','hypertension','High Blood Pressure','উচ্চ রক্তচাপ',
 'The silent killer: know your numbers.','নীরব ঘাতক: আপনার সংখ্যা জানুন।',
 '<p>Blood pressure above 140/90 mmHg is high. Get checked at least once a year and reduce salt to under 5g a day.</p>',
 '<p>রক্তচাপ ১৪০/৯০ mmHg-এর বেশি হলে তা উচ্চ। বছরে অন্তত একবার পরীক্ষা করুন এবং দিনে লবণ ৫ গ্রামের নিচে রাখুন।</p>',
 'activity',1),
('cholesterol','cholesterol','High Cholesterol','উচ্চ কোলেস্টেরল',
 'Too much fat in the blood blocks arteries.','রক্তে অতিরিক্ত চর্বি ধমনী বন্ধ করে।',
 '<p>Eat less fried food and red meat, and exercise 30 minutes most days.</p>',
 '<p>ভাজাপোড়া ও লাল মাংস কম খান, সপ্তাহের বেশিরভাগ দিন ৩০ মিনিট ব্যায়াম করুন।</p>',
 'droplet',2),
('diabetes','diabetes','Diabetes & the Heart','ডায়াবেটিস ও হৃদয়',
 'Diabetes doubles heart disease risk.','ডায়াবেটিসে হৃদরোগের ঝুঁকি দ্বিগুণ হয়।',
 '<p>Keep blood sugar controlled and check your heart health regularly.</p>',
 '<p>রক্তে সুগার নিয়ন্ত্রণে রাখুন এবং নিয়মিত হৃদযন্ত্র পরীক্ষা করান।</p>',
 'activity',3),
('heart-attack','heart-attack','Heart Attack: Signs & First Aid','হার্ট অ্যাটাক: লক্ষণ ও প্রাথমিক চিকিৎসা',
 'Chest pain, sweating, breathlessness — act fast.','বুকে ব্যথা, ঘাম, শ্বাসকষ্ট — দ্রুত ব্যবস্থা নিন।',
 '<p><strong>Call 999 or go to the nearest hospital immediately.</strong> Sit the person down, loosen tight clothes, and if they are not allergic give aspirin 300mg to chew.</p>',
 '<p><strong>অবিলম্বে ৯৯৯-এ কল করুন বা নিকটতম হাসপাতালে যান।</strong> রোগীকে বসান, আঁটসাঁট পোশাক ঢিলা করুন, অ্যালার্জি না থাকলে ৩০০ মি.গ্রা. অ্যাসপিরিন চিবিয়ে খাওয়ান।</p>',
 'alert',4),
('healthy-diet','healthy-diet','Healthy Diet (Bangladeshi Foods)','স্বাস্থ্যকর খাদ্য (বাংলাদেশি খাবার)',
 'Simple swaps for a healthier heart.','সুস্থ হৃদয়ের জন্য সহজ পরিবর্তন।',
 '<p>Choose fish, dal, vegetables and fruit. Reduce oil, sugar and deep-fried snacks.</p>',
 '<p>মাছ, ডাল, সবজি ও ফল বেছে নিন। তেল, চিনি ও ডিপ-ফ্রাইড খাবার কমান।</p>',
 'leaf',5),
('tobacco','tobacco','Tobacco & Smoking','তামাক ও ধূমপান',
 'Every cigarette damages your heart.','প্রতিটি সিগারেট আপনার হৃদয়ের ক্ষতি করে।',
 '<p>Stopping smoking halves heart attack risk within one year.</p>',
 '<p>ধূমপান ছাড়লে এক বছরের মধ্যে হার্ট অ্যাটাকের ঝুঁকি অর্ধেক হয়ে যায়।</p>',
 'ban',6);

-- ---------- Seed: team ----------
INSERT INTO team (name_en,name_bn,role_en,role_bn,sort) VALUES
('Dr. A. Rahman','ডা. এ. রহমান','President','সভাপতি',1),
('Dr. S. Akter','ডা. এস. আক্তার','General Secretary','সাধারণ সম্পাদক',2),
('Md. K. Hasan','মো. কে. হাসান','Programme Coordinator','প্রোগ্রাম সমন্বয়ক',3);


-- ---------- WHF Redesign Additions ----------

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

-- Homepage smart cards. Legacy fields remain for existing content and links.
CREATE TABLE IF NOT EXISTS home_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slot INT NOT NULL DEFAULT 0,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  subtitle_en VARCHAR(190) DEFAULT NULL,
  subtitle_bn VARCHAR(190) DEFAULT NULL,
  body_en TEXT,
  body_bn TEXT,
  content_type VARCHAR(32) DEFAULT NULL,
  category_id INT DEFAULT NULL,
  tags VARCHAR(500) DEFAULT NULL,
  image VARCHAR(255) DEFAULT NULL,
  link_path VARCHAR(2048) NOT NULL DEFAULT '',
  status TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT DEFAULT NULL,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  starts_at DATETIME DEFAULT NULL,
  ends_at DATETIME DEFAULT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_home_cards_visibility (status, is_archived, sort_order, slot),
  KEY idx_home_cards_category (category_id),
  CONSTRAINT fk_home_cards_category FOREIGN KEY (category_id) REFERENCES home_card_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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

-- Home 3 featured cards (§3.4)
CREATE TABLE IF NOT EXISTS featured_cards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slot TINYINT NOT NULL,
  caption_en VARCHAR(120) NOT NULL,
  caption_bn VARCHAR(120) DEFAULT NULL,
  image VARCHAR(255) NOT NULL,
  link_path VARCHAR(190) NOT NULL,
  status TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add fields on existing tables
ALTER TABLE news
  ADD COLUMN IF NOT EXISTS department_en VARCHAR(80) DEFAULT 'Communications',
  ADD COLUMN IF NOT EXISTS department_bn VARCHAR(80) DEFAULT 'যোগাযোগ';

ALTER TABLE events
  ADD COLUMN IF NOT EXISTS is_health_day TINYINT(1) DEFAULT 1;

ALTER TABLE publications
  ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) DEFAULT NULL;

ALTER TABLE team
  ADD COLUMN IF NOT EXISTS quote_en TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS quote_bn TEXT DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS is_leadership_message TINYINT(1) DEFAULT 0;

-- Additional settings
INSERT INTO settings (skey, svalue) VALUES
('video_youtube_id', '0wvcdR5NDrA'),
('local_screened',''),
('source_note_en','Source: World Heart Federation, 2026'),
('source_note_bn','সূত্র: ওয়ার্ল্ড হার্ট ফেডারেশন, ২০২৬')
ON DUPLICATE KEY UPDATE svalue = VALUES(svalue);

-- Seed home cards
INSERT INTO home_cards (slot,title_en,title_bn,body_en,body_bn,image,link_path) VALUES
(1,'Heart Health','হৃদরোগ তথ্য','Know the risks, signs and prevention.','ঝুঁকি, লক্ষণ ও প্রতিরোধ জানুন।','assets/images/card-heart-health.svg','heart-health'),
(2,'Our Impact','আমাদের কাজ','Camps, screenings and school programmes.','ক্যাম্প, স্ক্রিনিং ও স্কুল কার্যক্রম।','assets/images/card-impact.svg','events'),
(3,'Our Community','কমিউনিটি','Doctors, volunteers and members.','চিকিৎসক, স্বেচ্ছাসেবক ও সদস্যবৃন্দ।','assets/images/card-community.svg','team');

-- Seed featured cards
INSERT INTO featured_cards (slot,caption_en,caption_bn,image,link_path) VALUES
(1,'Most Inspiring World Heart Award','সবচেয়ে অনুপ্রেরণাদায়ক বিশ্ব হৃদয় পুরস্কার','public/images/community-hero.png','mission'),
(2,'World Heart Day — Cumilla','বিশ্ব হৃদয় দিবস — কুমিল্লা','public/images/healthawarness.jpg','events'),
(3,'Healthy Heart Awareness','সুস্থ হৃদয় সচেতনতা','public/images/worldheartdayfeatured.jpg','heart-health');

-- Set leadership quote
UPDATE team SET
  is_leadership_message = 1,
  quote_en = 'Cardiovascular disease is largely preventable. By educating families and bringing screenings to local communities across Cumilla, we can save lives every single day.',
  quote_bn = 'হৃদরোগের একটি বড় অংশই প্রতিরোধযোগ্য। সচেতনতা বৃদ্ধি ও কমিউনিটিতে বিনামূল্যে স্ক্রিনিং পৌঁছে দেওয়ার মাধ্যমে আমরা প্রতিদিন জীবন বাঁচাতে পারি।'
WHERE id = 1 LIMIT 1;
