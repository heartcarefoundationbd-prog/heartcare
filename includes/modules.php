<?php
declare(strict_types=1);

/* ==========================================================================
   Public queries for notices, projects, documents, gallery and archives.
   Every function degrades to an empty result if its table has not been
   created yet (i.e. migration 001 has not been run), so the old pages keep working.
   ========================================================================== */

function safe_query(callable $fn, mixed $fallback = []): mixed
{
    try {
        return $fn();
    } catch (PDOException $e) {
        error_log('HeartCare query failed: ' . $e->getMessage());
        return $fallback;
    }
}

function _limit_stmt(string $sql, array $params, int $limit, int $offset = 0): PDOStatement
{
    $st = db()->prepare($sql . ' LIMIT ? OFFSET ?');
    $i = 1;
    foreach ($params as $p) { $st->bindValue($i++, $p); }
    $st->bindValue($i++, $limit, PDO::PARAM_INT);
    $st->bindValue($i, $offset, PDO::PARAM_INT);
    $st->execute();
    return $st;
}

/* ---------- Notices ---------- */
const NOTICE_LIVE = 'status = 1 AND notice_date <= CURDATE() AND (expires_at IS NULL OR expires_at >= CURDATE())';

function notices_active(int $limit = 20, int $offset = 0): array
{
    return safe_query(fn() => _limit_stmt(
        'SELECT * FROM notices WHERE ' . NOTICE_LIVE . ' ORDER BY is_pinned DESC, notice_date DESC, id DESC',
        [], $limit, $offset)->fetchAll());
}

function notices_count(): int
{
    return (int)safe_query(fn() => db()->query('SELECT COUNT(*) FROM notices WHERE ' . NOTICE_LIVE)->fetchColumn(), 0);
}

function notices_expired(int $limit = 20): array
{
    return safe_query(fn() => _limit_stmt(
        'SELECT * FROM notices WHERE status = 1 AND notice_date <= CURDATE() AND expires_at IS NOT NULL AND expires_at < CURDATE() ORDER BY notice_date DESC',
        [], $limit)->fetchAll());
}

function get_notice(string $slug): ?array
{
    return safe_query(function () use ($slug) {
        $st = db()->prepare('SELECT * FROM notices WHERE (slug_en = ? OR slug_bn = ?) AND status = 1 AND notice_date <= CURDATE() AND (expires_at IS NULL OR expires_at >= CURDATE()) LIMIT 1');
        $st->execute([$slug, $slug]);
        return $st->fetch() ?: null;
    }, null);
}

/* ---------- Events archive ---------- */
function past_events(int $limit = 20): array
{
    $st = db()->prepare('SELECT * FROM events WHERE status = 1 AND event_date < CURDATE() ORDER BY event_date DESC LIMIT ?');
    $st->bindValue(1, $limit, PDO::PARAM_INT);
    $st->execute();
    return $st->fetchAll();
}

/* ---------- Projects ---------- */
function project_years(): array
{
    return safe_query(fn() => array_map('intval', db()->query(
        'SELECT DISTINCT project_year FROM projects WHERE status = 1 ORDER BY project_year DESC'
    )->fetchAll(PDO::FETCH_COLUMN)));
}

function projects_for_year(int $year): array
{
    return safe_query(function () use ($year) {
        $st = db()->prepare('SELECT * FROM projects WHERE status = 1 AND project_year = ? ORDER BY sort ASC, id DESC');
        $st->execute([$year]);
        return $st->fetchAll();
    });
}

function get_project(string $slug): ?array
{
    return safe_query(fn() => find_by_slug('projects', $slug), null);
}

/* ---------- Documents ---------- */
function document_categories(): array
{
    return [
        'annual-report'  => ['en' => 'Annual report',   'bn' => 'বার্ষিক প্রতিবেদন'],
        'project-report' => ['en' => 'Project report',  'bn' => 'প্রকল্প প্রতিবেদন'],
        'financial'      => ['en' => 'Financial',       'bn' => 'আর্থিক'],
        'policy'         => ['en' => 'Policy',          'bn' => 'নীতিমালা'],
        'form'           => ['en' => 'Form',            'bn' => 'ফর্ম'],
        'other'          => ['en' => 'Other',           'bn' => 'অন্যান্য'],
    ];
}

function document_category_label(string $key): string
{
    $c = document_categories();
    return $c[$key][lang()] ?? $key;
}

function documents_list(?string $category = null, ?int $year = null, ?int $projectId = null, int $limit = 100): array
{
    return safe_query(function () use ($category, $year, $projectId, $limit) {
        $where = ['status = 1']; $params = [];
        if ($category && isset(document_categories()[$category])) { $where[] = 'category = ?'; $params[] = $category; }
        if ($year)      { $where[] = 'doc_year = ?';   $params[] = $year; }
        if ($projectId) { $where[] = 'project_id = ?'; $params[] = $projectId; }
        return _limit_stmt('SELECT * FROM documents WHERE ' . implode(' AND ', $where)
            . ' ORDER BY doc_year DESC, id DESC', $params, $limit)->fetchAll();
    });
}

function document_years(): array
{
    return safe_query(fn() => array_map('intval', db()->query(
        'SELECT DISTINCT doc_year FROM documents WHERE status = 1 AND doc_year IS NOT NULL ORDER BY doc_year DESC'
    )->fetchAll(PDO::FETCH_COLUMN)));
}

function get_document(int $id): ?array
{
    return safe_query(function () use ($id) {
        $st = db()->prepare('SELECT * FROM documents WHERE id = ? AND status = 1');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }, null);
}

/** Stream a document (inline for PDF, download otherwise) and count the hit. */
function send_document(array $doc): never
{
    $full = APP_ROOT . '/' . $doc['file_path'];
    if (!str_starts_with((string)$doc['file_path'], 'assets/uploads/docs/') || !is_file($full)) {
        http_response_code(404);
        exit('File not found.');
    }
    safe_query(fn() => db()->prepare('UPDATE documents SET downloads = downloads + 1 WHERE id = ?')->execute([$doc['id']]));

    $ext = strtolower((string)$doc['file_ext']);
    $mimes = ['pdf' => 'application/pdf', 'doc' => 'application/msword',
              'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
              'xls' => 'application/vnd.ms-excel',
              'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
    $name = preg_replace('/[\r\n"]+/', '', (string)($doc['file_name'] ?: ('document.' . $ext)));
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . (string)filesize($full));
    header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment')
        . '; filename="' . rawurlencode($name) . '"; filename*=UTF-8\'\'' . rawurlencode($name));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0');
    readfile($full);
    exit;
}

/* ---------- Gallery ---------- */
function gallery_categories(): array
{
    return [
        'featured' => t('gallery_category_featured'),
        'activities' => t('gallery_category_activities'),
        'health' => t('gallery_category_health'),
        'heart-care' => t('gallery_category_heart_care'),
        'world-heart-day' => t('gallery_category_world_heart_day'),
        'cultural' => t('gallery_category_cultural'),
    ];
}

function gallery_items(int $limit = 60, int $offset = 0, string $category = ''): array
{
    $categories = gallery_categories();
    $category = isset($categories[$category]) ? $category : '';
    $where = 'status = 1';
    $params = [];
    if ($category !== '') {
        $where .= ' AND category = ?';
        $params[] = $category;
    }
    return safe_query(fn() => _limit_stmt(
        "SELECT * FROM gallery_items WHERE $where ORDER BY sort ASC, id DESC", $params, $limit, $offset)->fetchAll());
}

function gallery_count(string $category = ''): int
{
    $categories = gallery_categories();
    $category = isset($categories[$category]) ? $category : '';
    if ($category === '') {
        return (int)safe_query(fn() => db()->query('SELECT COUNT(*) FROM gallery_items WHERE status = 1')->fetchColumn(), 0);
    }
    return (int)safe_query(function () use ($category): int {
        $st = db()->prepare('SELECT COUNT(*) FROM gallery_items WHERE status = 1 AND category = ?');
        $st->execute([$category]);
        return (int)$st->fetchColumn();
    }, 0);
}

/** Automatic address of an article: /en/free-heart-camp.php (no file is created; the page is built from the database). */
function news_url(array $n): string
{
    return url(rawurlencode(lf($n, 'slug')) . '.php');
}
