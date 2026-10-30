<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("This importer can only be run from the command line.\n");
}

require dirname(__DIR__) . '/config/config.php';
require APP_ROOT . '/includes/functions.php';

const WP_IMPORT_IMAGE_MAX_BYTES = 12582912;
const WP_IMPORT_IMAGE_HOST = 'heartcarefoundationcb.org';

function wp_import_usage(): never
{
    fwrite(STDERR, "Usage: php scripts/import-wordpress-content.php [csv-path] [--dry-run] [--skip-images]\n");
    exit(2);
}

function wp_import_text(string $value): string
{
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/<!--.*?-->/s', ' ', $value) ?? $value;
    $value = preg_replace('/\[(?:\/?[\w-]+)(?:\s[^\]]*)?\]/u', ' ', $value) ?? $value;
    return trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');
}

function wp_import_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '') return null;

    try {
        return (new DateTimeImmutable($value))->format('Y-m-d');
    } catch (Throwable) {
        return null;
    }
}

function wp_import_language(string $value): string
{
    return preg_match('/\p{Bengali}/u', $value) === 1 ? 'bn' : 'en';
}

function wp_import_slug(array $row): string
{
    $sourceSlug = rawurldecode(trim((string)($row['post_name'] ?? '')));
    $slug = slugify($sourceSlug !== '' ? $sourceSlug : (string)($row['title'] ?? ''));
    if ($slug === '') $slug = 'wordpress-post-' . (int)($row['post_id'] ?? 0);
    return mb_substr($slug, 0, 170);
}

function wp_import_category(string $value): string
{
    $parts = array_values(array_filter(array_map('trim', explode('|', $value))));
    $category = $parts ? (string)end($parts) : 'News';
    return mb_substr($category, 0, 60);
}

function wp_import_images(string $html): array
{
    preg_match_all('/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/is', $html, $matches);
    $urls = [];
    foreach ($matches[2] ?? [] as $src) {
        $url = html_entity_decode(trim((string)$src), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($url === '' || str_starts_with(strtolower($url), 'data:')) continue;
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https'
            || strtolower((string)($parts['host'] ?? '')) !== WP_IMPORT_IMAGE_HOST
            || !str_starts_with((string)($parts['path'] ?? ''), '/wp-content/uploads/')) {
            fwrite(STDERR, "Skipping embedded image outside the approved WordPress uploads host.\n");
            continue;
        }
        $urls[$url] = true;
    }
    return array_keys($urls);
}

function wp_import_thumbnail_id(array $row): ?int
{
    $metadata = json_decode((string)($row['metadata_json'] ?? ''), true);
    if (!is_array($metadata)) return null;

    foreach ($metadata as $entry) {
        if (($entry['key'] ?? '') === '_thumbnail_id' && ctype_digit((string)($entry['value'] ?? ''))) {
            $id = (int)$entry['value'];
            return $id > 0 ? $id : null;
        }
    }
    return null;
}

function wp_import_thumbnail_url(int $attachmentId): string
{
    $endpoint = 'https://' . WP_IMPORT_IMAGE_HOST . '/wp-json/wp/v2/media/' . $attachmentId . '?context=embed';
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 15,
            'follow_location' => 0,
            'max_redirects' => 0,
            'header' => "User-Agent: HeartCareWordPressImporter/1.0\r\n",
        ],
    ]);
    $json = @file_get_contents($endpoint, false, $context, 0, 2097152);
    if (!is_string($json)) throw new RuntimeException('Could not resolve the WordPress featured image.');

    $media = json_decode($json, true);
    $url = is_array($media) ? (string)($media['source_url'] ?? '') : '';
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https'
        || strtolower((string)($parts['host'] ?? '')) !== WP_IMPORT_IMAGE_HOST
        || !str_starts_with((string)($parts['path'] ?? ''), '/wp-content/uploads/')) {
        throw new RuntimeException('WordPress returned no approved featured image URL.');
    }
    return $url;
}

function wp_import_store_image(string $url, int $postId): ?string
{
    $parts = parse_url($url);
    if (($parts['scheme'] ?? '') !== 'https'
        || strtolower((string)($parts['host'] ?? '')) !== WP_IMPORT_IMAGE_HOST
        || !str_starts_with((string)($parts['path'] ?? ''), '/wp-content/uploads/')) {
        throw new RuntimeException('Image URL is outside the approved WordPress uploads host.');
    }

    $directory = APP_ROOT . '/assets/uploads/wordpress-import';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create assets/uploads/wordpress-import.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('assets/uploads/wordpress-import is not writable by PHP.');
    }

    $urlHash = substr(hash('sha256', $url), 0, 20);
    $base = 'wp-' . $postId . '-' . $urlHash;
    foreach (['jpg', 'png', 'webp'] as $extension) {
        $existing = $directory . '/' . $base . '.' . $extension;
        if (is_file($existing) && !is_link($existing) && @getimagesize($existing)) {
            return 'assets/uploads/wordpress-import/' . $base . '.' . $extension;
        }
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 20,
            'follow_location' => 0,
            'max_redirects' => 0,
            'header' => "User-Agent: HeartCareWordPressImporter/1.0\r\n",
        ],
    ]);
    $stream = @fopen($url, 'rb', false, $context);
    if ($stream === false) throw new RuntimeException('Could not fetch approved image URL.');

    try {
        $bytes = stream_get_contents($stream, WP_IMPORT_IMAGE_MAX_BYTES + 1);
    } finally {
        fclose($stream);
    }
    if (!is_string($bytes) || $bytes === '' || strlen($bytes) > WP_IMPORT_IMAGE_MAX_BYTES) {
        throw new RuntimeException('Image is empty or larger than 12 MB.');
    }

    $info = @getimagesizefromstring($bytes);
    $extension = match ($info[2] ?? 0) {
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        default => null,
    };
    if ($extension === null) throw new RuntimeException('Downloaded file is not a supported JPG, PNG, or WebP image.');

    $file = $directory . '/' . $base . '.' . $extension;
    if (!is_file($file) && file_put_contents($file, $bytes, LOCK_EX) === false) {
        throw new RuntimeException('Could not save image under assets/uploads/wordpress-import.');
    }

    return 'assets/uploads/wordpress-import/' . $base . '.' . $extension;
}

function wp_import_tags(PDO $pdo, int $newsId, string $value): void
{
    $names = array_values(array_unique(array_filter(array_map('trim', preg_split('/[,|]/', $value) ?: []))));
    $find = $pdo->prepare('SELECT id FROM tags WHERE name = ? LIMIT 1');
    $findSlug = $pdo->prepare('SELECT id FROM tags WHERE slug = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)');
    $link = $pdo->prepare('INSERT IGNORE INTO news_tags (news_id, tag_id) VALUES (?, ?)');

    foreach ($names as $name) {
        $name = mb_substr($name, 0, 100);
        $find->execute([$name]);
        $tagId = $find->fetchColumn();
        if ($tagId === false) {
            $base = mb_substr(slugify($name) ?: 'tag', 0, 115);
            $slug = $base;
            $suffix = 2;
            do {
                $findSlug->execute([$slug]);
                $taken = $findSlug->fetchColumn() !== false;
                if ($taken) $slug = mb_substr($base, 0, 115 - strlen((string)$suffix)) . '-' . $suffix++;
            } while ($taken);
            $insert->execute([$name, $slug]);
            $tagId = (int)$pdo->lastInsertId();
        }
        $link->execute([$newsId, (int)$tagId]);
    }
}

$csvPath = APP_ROOT . '/news-events-content.csv';
$dryRun = false;
$skipImages = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--dry-run') {
        $dryRun = true;
    } elseif ($argument === '--skip-images') {
        $skipImages = true;
    } elseif (str_starts_with($argument, '--')) {
        wp_import_usage();
    } else {
        $csvPath = $argument;
    }
}

if (!is_file($csvPath) || !is_readable($csvPath)) {
    fwrite(STDERR, "CSV file does not exist or is not readable: {$csvPath}\n");
    exit(1);
}

$handle = fopen($csvPath, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Could not open CSV file: {$csvPath}\n");
    exit(1);
}

$header = fgetcsv($handle, null, ',', '"', '');
if ($header === false) {
    fclose($handle);
    fwrite(STDERR, "CSV file is empty.\n");
    exit(1);
}
$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]) ?? (string)$header[0];
$requiredColumns = ['post_id', 'title', 'post_name', 'status', 'post_type', 'post_date', 'categories', 'tags', 'content_html', 'excerpt_html'];
if (array_diff($requiredColumns, $header)) {
    fclose($handle);
    fwrite(STDERR, "CSV is missing required WordPress export columns.\n");
    exit(1);
}

$pdo = db();
$newsSourceColumn = $pdo->query("SHOW COLUMNS FROM news LIKE 'source_wp_id'")->fetch();
$eventSourceColumn = $pdo->query("SHOW COLUMNS FROM events LIKE 'source_wp_id'")->fetch();
if (!$newsSourceColumn || !$eventSourceColumn) {
    fclose($handle);
    fwrite(STDERR, "Apply sql/migrations/004_wordpress_import.sql before importing.\n");
    exit(1);
}

$newsBySourceId = $pdo->prepare('SELECT id, slug_en FROM news WHERE source_wp_id = ? LIMIT 1');
$eventBySourceId = $pdo->prepare('SELECT id, slug_en FROM events WHERE source_wp_id = ? LIMIT 1');
$newsInsert = $pdo->prepare(
    'INSERT INTO news (slug_en, title_en, title_bn, excerpt_en, excerpt_bn, body_en, body_bn, image, category, published_at, content_language, source_wp_id, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
);
$newsUpdate = $pdo->prepare(
    'UPDATE news SET slug_en = ?, title_en = ?, title_bn = ?, excerpt_en = ?, excerpt_bn = ?, body_en = ?, body_bn = ?, image = ?, category = ?, published_at = ?, content_language = ?, status = 1
     WHERE id = ?'
);
$eventInsert = $pdo->prepare(
    'INSERT INTO events (slug_en, title_en, title_bn, body_en, body_bn, location_en, image, event_date, source_wp_id, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
);
$eventUpdate = $pdo->prepare(
    'UPDATE events SET slug_en = ?, title_en = ?, title_bn = ?, body_en = ?, body_bn = ?, location_en = ?, image = ?, event_date = ?, status = 1 WHERE id = ?'
);
$deleteImages = $pdo->prepare('DELETE FROM news_images WHERE news_id = ?');
$insertImage = $pdo->prepare('INSERT INTO news_images (news_id, image_path, sort_order) VALUES (?, ?, ?)');
$deleteTags = $pdo->prepare('DELETE FROM news_tags WHERE news_id = ?');

$counts = ['news_inserted' => 0, 'news_updated' => 0, 'events_inserted' => 0, 'events_updated' => 0, 'skipped' => 0, 'failed' => 0, 'images_saved' => 0];
$rowNumber = 1;
while (($values = fgetcsv($handle, null, ',', '"', '')) !== false) {
    $rowNumber++;
    if ($values === [null] || count($values) === 1 && trim((string)$values[0]) === '') continue;
    if (count($values) !== count($header)) {
        $counts['failed']++;
        fwrite(STDERR, "Row {$rowNumber}: CSV column count does not match the header; skipped.\n");
        continue;
    }
    $row = array_combine($header, $values);
    if (!is_array($row)) {
        $counts['failed']++;
        fwrite(STDERR, "Row {$rowNumber}: could not map CSV columns; skipped.\n");
        continue;
    }

    $postId = filter_var($row['post_id'], FILTER_VALIDATE_INT);
    $postType = strtolower(trim((string)$row['post_type']));
    if (!$postId || ($row['status'] ?? '') !== 'publish'
        || !in_array($postType, ['post', 'article', 'news', 'event', 'tribe_events', 'events'], true)) {
        $counts['skipped']++;
        continue;
    }

    $title = trim((string)$row['title']);
    $date = wp_import_date((string)($row['post_date'] ?: ($row['pub_date'] ?? '')));
    if ($title === '' || $date === null) {
        $counts['failed']++;
        fwrite(STDERR, "Row {$rowNumber} (WordPress ID {$postId}): title/date is missing or invalid; skipped.\n");
        continue;
    }
    $language = wp_import_language($title . ' ' . (string)$row['content_html']);
    $slugBase = wp_import_slug($row);
    $bodySource = trim((string)$row['content_html']);
    if ($bodySource === '') $bodySource = (string)($row['description'] ?? '');
    $imageUrls = wp_import_images($bodySource);
    $bodySource = preg_replace('/\[(?:\/?[\w-]+)(?:\s[^\]]*)?\]/u', ' ', $bodySource) ?? $bodySource;
    $body = sanitize_html($bodySource);
    $excerpt = wp_import_text((string)$row['excerpt_html']);
    if ($excerpt === '') $excerpt = wp_import_text((string)($row['description'] ?? ''));
    $thumbnailId = wp_import_thumbnail_id($row);
    $isEvent = in_array($postType, ['event', 'tribe_events', 'events'], true);

    if ($dryRun) {
        if ($isEvent) $counts['events_inserted']++;
        else $counts['news_inserted']++;
        $counts['images_saved'] += $skipImages ? 0
            : count($imageUrls) + (empty($imageUrls) && $thumbnailId !== null ? 1 : 0);
        continue;
    }

    $savedImages = [];
    if (!$skipImages) {
        if (!$imageUrls && $thumbnailId !== null) {
            try {
                $imageUrls[] = wp_import_thumbnail_url($thumbnailId);
            } catch (Throwable $e) {
                $counts['failed']++;
                fwrite(STDERR, "Row {$rowNumber} (WordPress ID {$postId}): featured image not resolved: {$e->getMessage()}\n");
            }
        }
        foreach ($imageUrls as $imageUrl) {
            try {
                $savedPath = wp_import_store_image($imageUrl, (int)$postId);
                if ($savedPath !== null) $savedImages[] = $savedPath;
            } catch (Throwable $e) {
                $counts['failed']++;
                fwrite(STDERR, "Row {$rowNumber} (WordPress ID {$postId}): image not imported: {$e->getMessage()}\n");
            }
        }
    }

    try {
        $pdo->beginTransaction();
        if ($isEvent) {
            $eventBySourceId->execute([$postId]);
            $existing = $eventBySourceId->fetch();
            $slug = $existing ? (string)$existing['slug_en']
                : unique_slug('events', $slugBase, 0, ['news']);
            if ($existing) {
                $eventUpdate->execute([
                    $slug, $language === 'en' ? $title : '', $language === 'bn' ? $title : '',
                    $language === 'en' ? $body : '', $language === 'bn' ? $body : '',
                    '', $savedImages[0] ?? null, $date, (int)$existing['id'],
                ]);
                $counts['events_updated']++;
            } else {
                $eventInsert->execute([
                    $slug, $language === 'en' ? $title : '', $language === 'bn' ? $title : '',
                    $language === 'en' ? $body : '', $language === 'bn' ? $body : '',
                    '', $savedImages[0] ?? null, $date, $postId,
                ]);
                $counts['events_inserted']++;
            }
        } else {
            $newsBySourceId->execute([$postId]);
            $existing = $newsBySourceId->fetch();
            $slug = $existing ? (string)$existing['slug_en']
                : unique_slug('news', $slugBase, 0, ['pages', 'events']);
            $titleEn = $language === 'en' ? mb_substr($title, 0, 190) : '';
            $titleBn = $language === 'bn' ? mb_substr($title, 0, 190) : '';
            $excerptEn = $language === 'en' ? mb_substr($excerpt, 0, 400) : '';
            $excerptBn = $language === 'bn' ? mb_substr($excerpt, 0, 400) : '';
            $bodyEn = $language === 'en' ? $body : '';
            $bodyBn = $language === 'bn' ? $body : '';
            $image = $savedImages[0] ?? null;
            $category = wp_import_category((string)$row['categories']);

            if ($existing) {
                $newsUpdate->execute([
                    $slug, $titleEn, $titleBn, $excerptEn, $excerptBn, $bodyEn, $bodyBn,
                    $image, $category, $date, $language, (int)$existing['id'],
                ]);
                $newsId = (int)$existing['id'];
                $counts['news_updated']++;
            } else {
                $newsInsert->execute([
                    $slug, $titleEn, $titleBn, $excerptEn, $excerptBn, $bodyEn, $bodyBn,
                    $image, $category, $date, $language, $postId,
                ]);
                $newsId = (int)$pdo->lastInsertId();
                $counts['news_inserted']++;
            }

            $deleteImages->execute([$newsId]);
            foreach (array_slice($savedImages, 1) as $index => $imagePath) {
                $insertImage->execute([$newsId, $imagePath, $index + 1]);
            }
            $deleteTags->execute([$newsId]);
            wp_import_tags($pdo, $newsId, (string)$row['tags']);
        }
        $pdo->commit();
        $counts['images_saved'] += count($savedImages);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $counts['failed']++;
        fwrite(STDERR, "Row {$rowNumber} (WordPress ID {$postId}): database import failed: {$e->getMessage()}\n");
    }
}
fclose($handle);

echo ($dryRun ? "Dry run complete.\n" : "Import complete.\n");
foreach ($counts as $label => $count) echo $label . ': ' . $count . PHP_EOL;
if ($counts['failed'] > 0) exit(1);
