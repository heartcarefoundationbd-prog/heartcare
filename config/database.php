<?php
declare(strict_types=1);

/**
 * HeartCare — Database connection factory.
 * Location: D:\xampp\htdocs\heartcare\config\database.php
 *
 * Requires the following constants (defined in config/config.php):
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS
 * Optional (with safe fallbacks):
 *   DB_PORT (3307), DB_CHARSET (utf8mb4), DEBUG (false)
 */

/**
 * Return a shared PDO instance (singleton per request).
 *
 * Pass $reconnect = true to force a fresh connection
 * (handy in CLI scripts or tests that switch databases).
 */
function db(bool $reconnect = false): PDO
{
    static $pdo = null;

    if ($pdo !== null && !$reconnect) {
        return $pdo;
    }

    $host    = defined('DB_HOST')    ? DB_HOST    : 'localhost';
    $name    = defined('DB_NAME')    ? DB_NAME    : '';
    $user    = defined('DB_USER')    ? DB_USER    : 'root';
    $pass    = defined('DB_PASS')    ? DB_PASS    : '';
    $port    = defined('DB_PORT')    ? (int) DB_PORT : 3307;
    $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
    $debug   = defined('DEBUG')      ? (bool) DEBUG : false;

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $host,
        $port,
        $name,
        $charset
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_PERSISTENT         => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Log the real error server-side.
        $message = sprintf(
            'DB connection failed [%s:%d/%s]: %s',
            $host,
            $port,
            $name,
            $e->getMessage()
        );
        error_log($message);

        // Never leak credentials / SQL details to the browser in production.
        if ($debug) {
            http_response_code(500);
            echo '<pre style="padding:1rem;background:#fee;border:1px solid #c00;color:#900;font-family:monospace">';
            echo "Database connection failed\n\n";
            echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            echo '</pre>';
        } else {
            http_response_code(500);
            echo 'Service temporarily unavailable. Please try again later.';
        }
        exit;
    }

    return $pdo;
}

/**
 * Convenience helper — returns the underlying PDO wrapped in a
 * small class with query shortcuts, so controllers stay tidy.
 *
 * Usage:  DB::run('SELECT * FROM users WHERE id = ?', [$id])->fetch();
 */
final class DB
{
    public static function pdo(): PDO
    {
        return db();
    }

    public static function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /** @return array<int, array<string, mixed>> */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = [], $default = null)
    {
        $v = self::run($sql, $params)->fetchColumn();
        return $v === false ? $default : $v;
    }

    public static function insert(string $sql, array $params = []): int
    {
        self::run($sql, $params);
        return (int) db()->lastInsertId();
    }

    public static function execute(string $sql, array $params = []): int
    {
        return self::run($sql, $params)->rowCount();
    }
}