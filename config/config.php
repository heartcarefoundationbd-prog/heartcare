<?php
declare(strict_types=1);

/* ==========================================================================
   HEARTCARE — GLOBAL CONFIGURATION
   Machine-specific values (database, URL prefix, DEBUG) belong in
   config/config.local.php (copy config.local.php.example). Anything defined
   there wins; the defaults below match the original XAMPP setup.
   ========================================================================== */

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

/* 1. PATHS ---------------------------------------------------------------- */
defined('APP_ROOT')     || define('APP_ROOT', dirname(__DIR__));
defined('BASE_PATH')    || define('BASE_PATH', '/heartcare');       // '' when installed at the domain root
defined('APP_URL')      || define('APP_URL', 'http://localhost');
define('CONFIG_PATH',  APP_ROOT . '/config');
define('VIEW_PATH',    APP_ROOT . '/views');
define('STORAGE_PATH', APP_ROOT . '/storage');
define('LOG_PATH',     STORAGE_PATH . '/logs');
define('UPLOAD_PATH',  APP_ROOT . '/assets/uploads');
define('UPLOAD_URL',   BASE_PATH . '/assets/uploads');

/* 2. DATABASE ------------------------------------------------------------- */
defined('DB_HOST')    || define('DB_HOST', '127.0.0.1');
defined('DB_PORT')    || define('DB_PORT', 3306);
defined('DB_NAME')    || define('DB_NAME', 'heartcare');
defined('DB_USER')    || define('DB_USER', 'root');
defined('DB_PASS')    || define('DB_PASS', '');
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

/* 3. APPLICATION ---------------------------------------------------------- */
defined('APP_NAME')        || define('APP_NAME', 'HeartCare');
defined('DEFAULT_LANG')    || define('DEFAULT_LANG', 'en');
defined('SUPPORTED_LANGS') || define('SUPPORTED_LANGS', ['en', 'bn']);
defined('DEBUG')           || define('DEBUG', true);                // set false in production

/* Uploads */
defined('MAX_IMAGE_BYTES') || define('MAX_IMAGE_BYTES', 12 * 1024 * 1024);  // phone photos are resized on save
defined('MAX_DOC_BYTES')   || define('MAX_DOC_BYTES', 10 * 1024 * 1024);

/* 4. SECURITY ------------------------------------------------------------- */
defined('SESSION_NAME') || define('SESSION_NAME', 'HEARTCARE_SESSID');
defined('SESSION_LIFE') || define('SESSION_LIFE', 60 * 60 * 8);     // 8 hours (admins work from phones)

/* 5. ERROR REPORTING ------------------------------------------------------ */
if (DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('log_errors', '1');
    if (!is_dir(LOG_PATH)) {
        @mkdir(LOG_PATH, 0775, true);
    }
    ini_set('error_log', LOG_PATH . '/php-error.log');
}

/* 6. TIMEZONE & ENCODING -------------------------------------------------- */
date_default_timezone_set('Asia/Dhaka');
mb_internal_encoding('UTF-8');

/* 7. SESSION -------------------------------------------------------------- */
if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFE,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* 8. BOOTSTRAP ------------------------------------------------------------ */
require_once CONFIG_PATH . '/database.php';
