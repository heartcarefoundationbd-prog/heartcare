-- ============================================================================
-- HeartCare migration 001 — notices, year-wise projects, documents, gallery,
-- login throttling, PWA switch. ADDITIVE ONLY: nothing is dropped or changed.
-- Safe to run more than once. Works on MySQL 5.7+/8 and MariaDB 10.3+.
-- Select your existing database, then import this file.
-- ============================================================================
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS notices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug_en VARCHAR(190) NOT NULL,
  slug_bn VARCHAR(190) DEFAULT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  body_en MEDIUMTEXT,
  body_bn MEDIUMTEXT,
  notice_date DATE NOT NULL,
  expires_at DATE DEFAULT NULL,
  is_pinned TINYINT(1) NOT NULL DEFAULT 0,
  attachment VARCHAR(255) DEFAULT NULL,
  attachment_name VARCHAR(190) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_notices_slug_en (slug_en),
  KEY idx_notices_live (status, notice_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug_en VARCHAR(190) NOT NULL,
  slug_bn VARCHAR(190) DEFAULT NULL,
  project_year SMALLINT NOT NULL,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  summary_en VARCHAR(400) DEFAULT NULL,
  summary_bn VARCHAR(400) DEFAULT NULL,
  body_en MEDIUMTEXT,
  body_bn MEDIUMTEXT,
  image VARCHAR(255) DEFAULT NULL,
  sort INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_projects_slug_en (slug_en),
  KEY idx_projects_year (status, project_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_en VARCHAR(190) NOT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  description_en VARCHAR(400) DEFAULT NULL,
  description_bn VARCHAR(400) DEFAULT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'other',
  doc_year SMALLINT DEFAULT NULL,
  project_id INT DEFAULT NULL,
  file_path VARCHAR(255) NOT NULL,
  file_name VARCHAR(190) DEFAULT NULL,
  file_size INT NOT NULL DEFAULT 0,
  file_ext VARCHAR(8) NOT NULL DEFAULT 'pdf',
  downloads INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_documents_list (status, doc_year),
  KEY idx_documents_project (project_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gallery_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title_en VARCHAR(190) DEFAULT NULL,
  title_bn VARCHAR(190) DEFAULT NULL,
  image VARCHAR(255) NOT NULL,
  sort INT NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_gallery_list (status, sort)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip VARCHAR(45) NOT NULL,
  email VARCHAR(190) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  KEY idx_login_ip (ip, created_at),
  KEY idx_login_email (email, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional PWA switch (0 = off). Toggle it in Admin > Site settings.
INSERT IGNORE INTO settings (skey, svalue) VALUES ('pwa_enabled', '0');
