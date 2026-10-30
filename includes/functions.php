<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$GLOBALS['LANG'] = DEFAULT_LANG;

/* ---------- Language ---------- */
function lang(): string { return $GLOBALS['LANG']; }

function t(string $key, ?string $fallback = null): string
{
    static $cache = [];
    $l = lang();
    if (!isset($cache[$l])) {
        $file = APP_ROOT . '/lang/' . $l . '.php';
        $cache[$l] = is_file($file) ? require $file : [];
    }
    return $cache[$l][$key] ?? $fallback ?? $key;
}

/** Localized DB field with fallback. */
function lf(array $row, string $field): string
{
    $val = $row[$field . '_' . lang()] ?? '';
    if ($val !== null && $val !== '') return (string)$val;
    $other = lang() === 'en' ? 'bn' : 'en';
    return (string)($row[$field . '_' . $other] ?? '');
}

function field_missing_in_lang(array $row, string $field): bool
{
    return empty($row[$field . '_' . lang()]) && !empty($row[$field . '_' . (lang()==='en'?'bn':'en')]);
}

/* ---------- URLs ---------- */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . lang() . ($path !== '' ? '/' . $path : '');
}

function asset(string $path): string
{
    return BASE_PATH . '/' . ltrim($path, '/');
}

function current_path(): string
{
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (BASE_PATH !== '' && str_starts_with($uri, BASE_PATH)) {
        $uri = substr($uri, strlen(BASE_PATH));
    }
    return '/' . trim($uri, '/');
}

function switch_lang_url(string $newLang): string
{
    $segs = array_values(array_filter(explode('/', current_path()), fn($s) => $s !== ''));
    if (!empty($segs) && in_array($segs[0], ['en','bn'], true)) {
        array_shift($segs);
    }
    return BASE_PATH . '/' . $newLang . (empty($segs) ? '' : '/' . implode('/', $segs));
}

/* ---------- Output / security ---------- */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    return isset($_POST['_csrf'], $_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], (string)$_POST['_csrf']);
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) { $_SESSION['flash'] = $msg; return null; }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

/* ---------- Settings ---------- */
function all_settings(): array
{
    static $s = null;
    if ($s === null) {
        $s = [];
        foreach (db()->query('SELECT skey, svalue FROM settings') as $r) {
            $s[$r['skey']] = $r['svalue'];
        }
    }
    return $s;
}

function setting(string $key, string $default = ''): string
{
    $v = all_settings()[$key] ?? '';
    return $v !== '' ? $v : $default;
}

/** Configured social profiles; omit empty links and generic platform homepages. */
function social_links(): array
{
    $links = [];
    foreach ([
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'x' => 'X',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
        'threads' => 'Threads',
    ] as $key => $label) {
        $url = trim(setting($key));
        $path = parse_url($url, PHP_URL_PATH);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)
            || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            || !is_string($path) || trim($path, '/') === '') {
            continue;
        }
        $links[] = ['key' => $key, 'label' => $label, 'url' => $url];
    }
    return $links;
}

function valid_http_url(string $url): bool
{
    $parts = parse_url($url);
    return filter_var($url, FILTER_VALIDATE_URL) !== false
        && is_array($parts)
        && in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)
        && !empty($parts['host'])
        && !isset($parts['user'])
        && !isset($parts['pass']);
}

function youtube_video_id(string $value): ?string
{
    $value = trim($value);
    if (preg_match('/^[A-Za-z0-9_-]{11}$/D', $value)) return $value;
    if (!valid_http_url($value)) return null;

    $parts = parse_url($value);
    $host = strtolower((string)($parts['host'] ?? ''));
    $path = (string)($parts['path'] ?? '');
    $id = null;

    if (in_array($host, ['youtu.be', 'www.youtu.be'], true)) {
        $id = trim($path, '/');
    } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtube-nocookie.com', 'www.youtube-nocookie.com'], true)) {
        if ($path === '/watch') {
            parse_str((string)($parts['query'] ?? ''), $query);
            $id = is_string($query['v'] ?? null) ? $query['v'] : null;
        } elseif (preg_match('~^/(?:embed|shorts|live)/([A-Za-z0-9_-]{11})/?$~D', $path, $match)) {
            $id = $match[1];
        }
    }

    return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/D', $id) ? $id : null;
}

/** setting_en / setting_bn pick by current language, falling back to the other. */
function setting_l(string $base): string
{
    $a = setting($base . '_' . lang());
    return $a !== '' ? $a : setting($base . '_' . (lang() === 'en' ? 'bn' : 'en'));
}

/* ---------- Queries ---------- */
function find_by_slug(string $table, string $slug): ?array
{
    $st = db()->prepare("SELECT * FROM `$table` WHERE (slug_en = ? OR slug_bn = ?) AND status = 1 LIMIT 1");
    $st->execute([$slug, $slug]);
    $row = $st->fetch();
    return $row ?: null;
}

function get_page(string $slug): ?array   { return find_by_slug('pages', $slug); }
function get_news_item(string $slug): ?array
{
    $st = db()->prepare('SELECT * FROM news WHERE (slug_en = ? OR slug_bn = ?) AND status = 1 AND published_at <= CURDATE() LIMIT 1');
    $st->execute([$slug, $slug]);
    return $st->fetch() ?: null;
}
function get_news_redirect(string $slug): ?array
{
    $st = db()->prepare('SELECT n.* FROM news_slug_redirects r INNER JOIN news n ON n.id = r.news_id WHERE r.old_slug = ? AND n.status = 1 AND n.published_at <= CURDATE() LIMIT 1');
    $st->execute([$slug]);
    return $st->fetch() ?: null;
}
function get_publication(string $slug): ?array { return find_by_slug('publications', $slug); }
function get_topic(string $slug): ?array { return get_publication($slug); }
function get_event(string $slug): ?array  { return find_by_slug('events', $slug); }

function latest_news(int $limit = 3): array
{
    $st = db()->prepare('SELECT * FROM news WHERE status = 1 AND published_at <= CURDATE() ORDER BY published_at DESC, id DESC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function news_count(): int
{
    return (int)db()->query('SELECT COUNT(*) FROM news WHERE status = 1 AND published_at <= CURDATE()')->fetchColumn();
}

function news_list(int $limit = 20, int $offset = 0): array
{
    $st = db()->prepare('SELECT * FROM news WHERE status = 1 AND published_at <= CURDATE() ORDER BY published_at DESC, id DESC LIMIT ? OFFSET ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->bindValue(2, $offset, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/** Return the authored-language value for new single-language news, preserving legacy bilingual display. */
function news_field(array $row, string $field): string
{
    $authoredLanguage = $row['content_language'] ?? null;
    if (in_array($authoredLanguage, ['en', 'bn'], true)) {
        return (string)($row[$field . '_' . $authoredLanguage] ?? '');
    }
    return lf($row, $field);
}

function news_images(int $newsId): array
{
    $st = db()->prepare('SELECT id, image_path, sort_order FROM news_images WHERE news_id = ? ORDER BY sort_order ASC, id ASC');
    $st->execute([$newsId]);
    return $st->fetchAll();
}

function news_tags(int $newsId): array
{
    $st = db()->prepare('SELECT t.id, t.name, t.slug FROM tags t INNER JOIN news_tags nt ON nt.tag_id = t.id WHERE nt.news_id = ? ORDER BY t.name ASC');
    $st->execute([$newsId]);
    return $st->fetchAll();
}

/**
 * Build escaped-at-render-time SEO data for a published content record.
 * $path is the canonical route relative to the language root.
 */
function content_meta(array $row, string $path, string $summaryField = 'excerpt', string $imageField = 'image', string $type = 'article'): array
{
    $title = !empty($row['content_language']) && isset($row['title_' . $row['content_language']])
        ? news_field($row, 'title')
        : lf($row, 'title');
    $metaTitle = trim((string)($row['meta_title'] ?? ''));
    $description = trim((string)($row['meta_description'] ?? ''));
    if ($description === '' && $summaryField !== '') {
        $description = !empty($row['content_language']) && isset($row[$summaryField . '_' . $row['content_language']])
            ? news_field($row, $summaryField)
            : lf($row, $summaryField);
    }
    if ($description === '') {
        $body = !empty($row['content_language']) && isset($row['body_' . $row['content_language']])
            ? news_field($row, 'body')
            : lf($row, 'body');
        $description = trim(preg_replace('/\s+/u', ' ', strip_tags($body)) ?? '');
    }
    $description = mb_substr($description, 0, 320);
    $canonical = rtrim(APP_URL, '/') . url($path);
    $image = trim((string)($row[$imageField] ?? ''));
    $absoluteImage = $image !== '' ? rtrim(APP_URL, '/') . asset($image) : '';

    return [
        'pageTitle' => ($metaTitle !== '' ? $metaTitle : $title) . ' — ' . t('site_name'),
        'metaDescription' => $description !== '' ? $description : t('tagline'),
        'metaKeywords' => trim((string)($row['meta_keywords'] ?? '')),
        'canonicalUrl' => $canonical,
        'ogType' => $type,
        'ogImage' => $absoluteImage,
        'contentLang' => in_array($row['content_language'] ?? null, ['en', 'bn'], true)
            ? $row['content_language'] : lang(),
        'hasLanguageAlternates' => !in_array($row['content_language'] ?? null, ['en', 'bn'], true),
    ];
}

function upcoming_events(int $limit = 4): array
{
    $st = db()->prepare('SELECT * FROM events WHERE status = 1 AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function publications(int $limit = 6): array
{
    $st = db()->prepare('SELECT * FROM publications WHERE status = 1 ORDER BY sort ASC, id ASC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

function health_topics(int $limit = 6): array
{
    return publications($limit);
}

function publication_pdf_url(array $publication): ?string
{
    $path = (string)($publication['publication_pdf_path'] ?? '');
    return preg_match('~^assets/uploads/docs/[a-f0-9]{20}\.pdf$~D', $path) ? asset($path) : null;
}

function publication_external_url(array $publication): ?string
{
    $url = trim((string)($publication['international_url'] ?? ''));
    return $url !== '' && valid_http_url($url) ? $url : null;
}

function team_members(): array
{
    return db()->query('SELECT * FROM team WHERE status = 1 ORDER BY sort ASC')->fetchAll();
}

function home_card_read_all(string $query, array $params = []): ?array
{
    static $loggedFailure = false;
    try {
        $st = db()->prepare($query);
        $st->execute($params);
        return $st->fetchAll();
    } catch (PDOException $e) {
        if (!$loggedFailure) {
            error_log('Homepage card data could not be read: ' . $e->getMessage());
            $loggedFailure = true;
        }
        return null;
    }
}

function home_cards(): array
{
    $cards = home_card_read_all(
        'SELECT hc.*, hcc.name_en AS category_en, hcc.name_bn AS category_bn
         FROM home_cards hc
         LEFT JOIN home_card_categories hcc ON hcc.id = hc.category_id AND hcc.status = 1
         WHERE hc.status = 1 AND hc.is_archived = 0
           AND (hc.starts_at IS NULL OR hc.starts_at <= NOW())
           AND (hc.ends_at IS NULL OR hc.ends_at >= NOW())
         ORDER BY hc.is_featured DESC, COALESCE(hc.sort_order, hc.slot) ASC, hc.id ASC'
    );
    if ($cards !== null) return $cards;

    return home_card_read_all(
        'SELECT id, slot, title_en, title_bn, body_en, body_bn, image, link_path, status
         FROM home_cards
         WHERE status = 1
         ORDER BY slot ASC, id ASC'
    ) ?? [];
}

function home_card_stats(int $cardId): array
{
    return home_card_read_all(
        'SELECT * FROM home_card_stats WHERE card_id = ? ORDER BY sort_order ASC, id ASC',
        [$cardId]
    ) ?? [];
}

function home_card_buttons(int $cardId): array
{
    return home_card_read_all(
        'SELECT * FROM home_card_buttons WHERE card_id = ? ORDER BY sort_order ASC, id ASC',
        [$cardId]
    ) ?? [];
}

function home_card_media(int $cardId): ?array
{
    return home_card_read_all(
        'SELECT * FROM home_card_media WHERE card_id = ?',
        [$cardId]
    )[0] ?? null;
}

function home_card_style(int $cardId): ?array
{
    return home_card_read_all(
        'SELECT * FROM home_card_styles WHERE card_id = ?',
        [$cardId]
    )[0] ?? null;
}

function home_card_animation(int $cardId): ?array
{
    return home_card_read_all(
        'SELECT * FROM home_card_animations WHERE card_id = ?',
        [$cardId]
    )[0] ?? null;
}

function home_card_url(string $path): ?string
{
    $path = trim($path);
    if ($path === '') return null;
    if (valid_http_url($path)) return $path;
    if (str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x20\x7f]/', $path)) return null;
    if (!preg_match('~^/?[A-Za-z0-9/_-]+(?:\?[A-Za-z0-9=&_-]*)?$~D', $path)) return null;
    foreach (explode('/', strtok(ltrim($path, '/'), '?') ?: '') as $segment) {
        if ($segment === '..' || $segment === '.') return null;
    }
    return url(ltrim($path, '/'));
}

function home_card_icon(string $key): string
{
    $paths = [
        'people' => '<circle cx="12" cy="7" r="3"/><path d="M5 20v-1a7 7 0 0 1 14 0v1M4 10a2.5 2.5 0 1 0 0 5m16-5a2.5 2.5 0 1 1 0 5"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8l1.1 1.1L12 21l7.8-7.5 1.1-1.1a5.5 5.5 0 0 0-.1-7.8Z"/><path d="M4.5 12h4l2-4 3 8 2-4h4"/>',
        'hands' => '<path d="M12 4v7m-3.5-3.5h7M3 15h5l2 2h5l6-4a2 2 0 0 1 2 3l-6 4H9l-6-2z"/>',
        'pulse' => '<path d="M3 12h4l3-7 4 14 3-7h4"/>',
        'campaign' => '<path d="M4 13V9a2 2 0 0 1 2-2h3l9-4v16l-9-4H6a2 2 0 0 1-2-2Zm5 2 2 6h4l-2-5"/>',
        'volunteer' => '<path d="M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.5-8 11-8 11Z"/>',
        'research' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5M8 10h5m-2.5-2.5v5"/>',
        'donate' => '<path d="M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.5-8 11-8 11Z"/><path d="M12 8v6m-2-2h4"/>',
    ];
    $path = $paths[$key] ?? $paths['heart'];
    return '<svg class="home-card-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $path . '</svg>';
}

function featured_cards(): array
{
    return db()->query('SELECT * FROM featured_cards WHERE status = 1 ORDER BY slot ASC LIMIT 3')->fetchAll();
}

function leadership_message(): ?array
{
    $st = db()->query('SELECT * FROM team WHERE status = 1 AND is_leadership_message = 1 ORDER BY sort ASC LIMIT 1');
    $row = $st->fetch();
    return $row ?: null;
}

function health_days(int $limit = 3): array
{
    $st = db()->prepare('SELECT * FROM events WHERE status = 1 AND is_health_day = 1 AND event_date >= CURDATE() ORDER BY event_date ASC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/* ---------- Bangla numeral helper ---------- */
function bn_num(string|int|float $n): string
{
    $s = (string)$n;
    return lang() === 'bn' ? str_replace(range('0','9'), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], $s) : $s;
}

/* ---------- Dates ---------- */
function format_date(string $date): string
{
    $ts = strtotime($date);
    if ($ts === false) return $date;
    if (lang() === 'bn') {
        $months = ['','জানুয়ারি','ফেব্রুয়ারি','মার্চ','এপ্রিল','মে','জুন',
                   'জুলাই','আগস্ট','সেপ্টেম্বর','অক্টোবর','নভেম্বর','ডিসেম্বর'];
        $d = (int)date('j', $ts);
        $bnDigits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        $d = str_replace(range('0','9'), $bnDigits, (string)$d);
        return $d . ' ' . $months[(int)date('n', $ts)] . ' ' . str_replace(range('0','9'), $bnDigits, date('Y', $ts));
    }
    return date('j M Y', $ts);
}

function day_badge(string $date): array
{
    $bn = lang() === 'bn';
    $d = date('d', strtotime($date));
    $m = date('M', strtotime($date));
    if ($bn) {
        $d = str_replace(range('0','9'), ['০','১','২','৩','৪','৫','৬','৭','৮','৯'], $d);
        $m = ['জানু','ফেব্রু','মার্চ','এপ্রি','মে','জুন','জুলা','আগ','সেপ্টে','অক্টো','নভে','ডিসে'][(int)date('n', strtotime($date))-1];
    }
    return [$d, $m];
}

/* ---------- Uploads ---------- */
/** Kept for backwards compatibility: stores an image, returns its path or null. */
function handle_upload(string $field): ?string
{
    return upload_image($field);
}

/* ---------- Rendering ---------- */
function render(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/includes/header.php';
    require APP_ROOT . '/views/' . $view . '.php';
    require APP_ROOT . '/includes/footer.php';
}

function render_view(string $view, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/views/' . $view . '.php';
}

/* ---------- Extra modules ---------- */
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/modules.php';
require_once __DIR__ . '/forms.php';
