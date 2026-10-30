<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin('content');

const HOME_CARD_CONTENT_TYPES = [
    'mission', 'goal', 'program', 'campaign', 'achievement', 'impact_story',
    'department', 'health_service', 'donation', 'volunteer', 'research',
];
const HOME_CARD_ICONS = ['people', 'heart', 'hands', 'pulse', 'campaign', 'volunteer', 'research', 'donate'];
const HOME_CARD_VIDEO_LIMIT = 24 * 1024 * 1024;

function home_card_form_value(array $values, string $key, mixed $fallback = ''): mixed
{
    return $values[$key] ?? $fallback;
}

function home_card_datetime_input(?string $value): string
{
    if (!$value) return '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/D', $value)) return $value;
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return $date ? $date->format('Y-m-d\TH:i') : '';
}

function home_card_parse_datetime(string $value): ?string
{
    if ($value === '') return null;
    $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value);
    $errors = DateTime::getLastErrors();
    if (!$date || ($errors && ($errors['warning_count'] || $errors['error_count']))
        || $date->format('Y-m-d\TH:i') !== $value) {
        return null;
    }
    return $date->format('Y-m-d H:i:s');
}

function home_card_store_video(string $field): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ((int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The video upload did not complete. Please try again.');
    }
    $tmp = $file['tmp_name'] ?? '';
    if (!is_string($tmp) || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Choose a video smaller than 24 MB.');
    }
    $size = filesize($tmp);
    if ($size === false || $size < 1 || $size > HOME_CARD_VIDEO_LIMIT) {
        throw new RuntimeException('Choose a video smaller than 24 MB.');
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['mp4', 'webm'], true)) {
        throw new RuntimeException('Only MP4 and WebM videos are accepted.');
    }
    $validation = media_library_validate_file($tmp, $extension);
    if (!$validation['ok']) throw new RuntimeException((string)$validation['error']);
    $directory = UPLOAD_PATH . '/videos';
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('The video folder could not be created.');
    }
    if (!is_writable($directory)) {
        throw new RuntimeException('The web server cannot write to the video uploads folder.');
    }
    $filename = bin2hex(random_bytes(12)) . '.' . $extension;
    if (!move_uploaded_file($tmp, $directory . '/' . $filename)) {
        throw new RuntimeException('The video could not be saved. Check server permissions and free disk space.');
    }
    return 'assets/uploads/videos/' . $filename;
}

function home_card_remove_video(?string $path): void
{
    if (!$path || !preg_match('~^assets/uploads/videos/[a-f0-9]{24}\.(mp4|webm)$~D', $path)) return;
    $fullPath = APP_ROOT . '/' . $path;
    if (is_file($fullPath)) @unlink($fullPath);
}

function home_card_cleanup_unused_media(array $paths): void
{
    $paths = array_values(array_unique(array_filter($paths, static fn($path) => is_string($path) && $path !== '')));
    foreach ($paths as $path) {
        if (media_library_is_path($path)) continue;
        if (str_starts_with($path, 'assets/uploads/videos/')) {
            $rows = home_card_read_all('SELECT COUNT(*) AS uses FROM home_card_media WHERE video_path = ?', [$path]);
            if ($rows !== null && (int)($rows[0]['uses'] ?? 0) === 0) home_card_remove_video($path);
        } elseif (str_starts_with($path, 'assets/uploads/')) {
            $rows = home_card_read_all('SELECT
                (SELECT COUNT(*) FROM home_cards WHERE image = ?) +
                (SELECT COUNT(*) FROM home_card_media WHERE image_path = ? OR poster_path = ?) AS uses', [$path, $path, $path]);
            if ($rows !== null && (int)($rows[0]['uses'] ?? 0) === 0) delete_upload($path);
        }
    }
}

function home_card_request_rows(string $key): array
{
    $rows = $_POST[$key] ?? [];
    return is_array($rows) ? $rows : [];
}

function home_card_clean_rows(array $rows, array $fields, array &$errors, string $label): array
{
    $indexes = [];
    foreach (array_keys($fields) as $field) {
        if (isset($rows[$field]) && is_array($rows[$field])) {
            foreach (array_keys($rows[$field]) as $index) $indexes[(string)$index] = $index;
        }
    }
    asort($indexes);
    $clean = [];
    foreach ($indexes as $index) {
        $item = [];
        foreach ($fields as $field => $max) {
            $raw = $rows[$field][$index] ?? '';
            if (!is_scalar($raw)) {
                $errors[] = 'One of the ' . $label . ' entries is invalid.';
                $raw = '';
            }
            $item[$field] = trim(mb_substr((string)$raw, 0, $max));
        }
        $hasContent = count(array_filter($item, static fn($v) => $v !== '')) > 0;
        if (!$hasContent) continue;
        $clean[] = $item;
    }
    return $clean;
}

function home_card_is_valid_button_url(string $url): bool
{
    if (valid_http_url($url)) return true;
    return home_card_url($url) !== null;
}

function home_card_save_children(PDO $pdo, int $cardId, array $stats, array $buttons, array $media, array $style, array $animation): void
{
    foreach (['home_card_stats', 'home_card_buttons'] as $table) {
        $pdo->prepare("DELETE FROM `$table` WHERE card_id = ?")->execute([$cardId]);
    }
    foreach ($stats as $index => $stat) {
        $pdo->prepare('INSERT INTO home_card_stats
            (card_id,label_en,label_bn,value,icon_key,color_key,animate_counter,sort_order)
            VALUES (?,?,?,?,?,?,?,?)')->execute([
                $cardId, $stat['label_en'], $stat['label_bn'] ?: null, $stat['value'],
                $stat['icon_key'], $stat['color_key'], $stat['animate_counter'], $index,
            ]);
    }
    foreach ($buttons as $index => $button) {
        $pdo->prepare('INSERT INTO home_card_buttons
            (card_id,label_en,label_bn,url,button_type,sort_order) VALUES (?,?,?,?,?,?)')->execute([
                $cardId, $button['label_en'], $button['label_bn'] ?: null, $button['url'],
                $button['button_type'], $index,
            ]);
    }
    $pdo->prepare('INSERT INTO home_card_media (card_id,visual_type,icon_key,image_path,video_path,poster_path)
        VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE visual_type=VALUES(visual_type),icon_key=VALUES(icon_key),
        image_path=VALUES(image_path),video_path=VALUES(video_path),poster_path=VALUES(poster_path)')
        ->execute([$cardId, $media['visual_type'], $media['icon_key'], $media['image_path'], $media['video_path'], $media['poster_path']]);
    $pdo->prepare('INSERT INTO home_card_styles
        (card_id,background_type,background_color,gradient_start,gradient_end,pattern_key,theme,border_radius,shadow,spacing,overlay_color,overlay_opacity)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE background_type=VALUES(background_type),
        background_color=VALUES(background_color),gradient_start=VALUES(gradient_start),gradient_end=VALUES(gradient_end),
        pattern_key=VALUES(pattern_key),theme=VALUES(theme),border_radius=VALUES(border_radius),shadow=VALUES(shadow),
        spacing=VALUES(spacing),overlay_color=VALUES(overlay_color),overlay_opacity=VALUES(overlay_opacity)')
        ->execute([$cardId, $style['background_type'], $style['background_color'], $style['gradient_start'], $style['gradient_end'],
            $style['pattern_key'], $style['theme'], $style['border_radius'], $style['shadow'], $style['spacing'],
            $style['overlay_color'], $style['overlay_opacity']]);
    $pdo->prepare('INSERT INTO home_card_animations (card_id,animation_type,trigger_type,duration_ms,delay_ms,hover_effect,heartbeat)
        VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE animation_type=VALUES(animation_type),trigger_type=VALUES(trigger_type),
        duration_ms=VALUES(duration_ms),delay_ms=VALUES(delay_ms),hover_effect=VALUES(hover_effect),heartbeat=VALUES(heartbeat)')
        ->execute([$cardId, $animation['animation_type'], $animation['trigger_type'], $animation['duration_ms'],
            $animation['delay_ms'], $animation['hover_effect'], $animation['heartbeat']]);
}

function home_card_duplicate(PDO $pdo, int $id): int
{
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT * FROM home_cards WHERE id = ? FOR UPDATE');
        $st->execute([$id]);
        $card = $st->fetch();
        if (!$card) throw new RuntimeException('That card no longer exists.');
        $copyTitle = mb_substr((string)$card['title_en'] . ' (copy)', 0, 190);
        $bnTitle = $card['title_bn'] ? mb_substr((string)$card['title_bn'] . ' (কপি)', 0, 190) : null;
        $pdo->prepare('INSERT INTO home_cards
            (slot,title_en,title_bn,subtitle_en,subtitle_bn,body_en,body_bn,content_type,category_id,tags,image,link_path,status,sort_order,is_featured,starts_at,ends_at,is_archived)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,0,?,0,NULL,NULL,0)')
            ->execute([
                $card['slot'], $copyTitle, $bnTitle, $card['subtitle_en'], $card['subtitle_bn'], $card['body_en'], $card['body_bn'],
                $card['content_type'], $card['category_id'], $card['tags'], $card['image'], $card['link_path'],
                (int)($card['sort_order'] ?? $card['slot']) + 1,
            ]);
        $newId = (int)$pdo->lastInsertId();
        foreach ([
            'home_card_stats' => ['label_en','label_bn','value','icon_key','color_key','animate_counter','sort_order'],
            'home_card_buttons' => ['label_en','label_bn','url','button_type','sort_order'],
        ] as $table => $columns) {
            $columnSql = implode(',', array_map(static fn($column) => '`' . $column . '`', $columns));
            $pdo->prepare("INSERT INTO `$table` (card_id,$columnSql) SELECT ?, $columnSql FROM `$table` WHERE card_id = ?")
                ->execute([$newId, $id]);
        }
        $oneToOneColumns = [
            'home_card_media' => ['visual_type','icon_key','image_path','video_path','poster_path'],
            'home_card_styles' => ['background_type','background_color','gradient_start','gradient_end','pattern_key','theme','border_radius','shadow','spacing','overlay_color','overlay_opacity'],
            'home_card_animations' => ['animation_type','trigger_type','duration_ms','delay_ms','hover_effect','heartbeat'],
        ];
        foreach ($oneToOneColumns as $table => $columns) {
            $columnSql = implode(',', array_map(static fn($column) => '`' . $column . '`', $columns));
            $pdo->prepare("INSERT INTO `$table` (card_id,$columnSql) SELECT ?, $columnSql FROM `$table` WHERE card_id = ?")
                ->execute([$newId, $id]);
        }
        $pdo->commit();
        return $newId;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

$self = admin_url('home-cards.php');
try {
$action = (string)($_GET['action'] ?? 'list');
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$existing = null;
if ($action === 'edit' && $id > 0) {
    $st = db()->prepare('SELECT hc.*, hcc.name_en AS category_en, hcc.name_bn AS category_bn
        FROM home_cards hc LEFT JOIN home_card_categories hcc ON hcc.id = hc.category_id WHERE hc.id = ?');
    $st->execute([$id]);
    $existing = $st->fetch() ?: null;
    if (!$existing) { flash('!That card no longer exists.'); redirect($self); }
}

$formErrors = [];
$uploadedPaths = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = (string)($_POST['do'] ?? 'save');
    if (!csrf_check()) { flash('!Your session expired. Please try again.'); redirect($self); }

    if ($do === 'delete') {
        if (!can($user, 'delete')) { flash('!Your role cannot delete cards.'); redirect($self); }
        $st = db()->prepare('SELECT hc.image, hm.image_path, hm.poster_path, hm.video_path
            FROM home_cards hc LEFT JOIN home_card_media hm ON hm.card_id = hc.id WHERE hc.id = ?');
        $st->execute([$id]);
        $mediaRow = $st->fetch();
        if ($mediaRow) {
            db()->prepare('DELETE FROM home_cards WHERE id = ?')->execute([$id]);
            home_card_cleanup_unused_media(array_filter([$mediaRow['image'], $mediaRow['image_path'], $mediaRow['poster_path'], $mediaRow['video_path']]));
            flash('Card deleted.');
        }
        redirect($self);
    }
    if ($do === 'duplicate') {
        if (!can($user, 'create')) { flash('!Your role cannot duplicate cards.'); redirect($self); }
        try {
            $newId = home_card_duplicate(db(), $id);
            flash('Card duplicated as a draft.');
            redirect($self . '?action=edit&id=' . $newId);
        } catch (Throwable $e) {
            error_log('Home card duplicate failed: ' . $e->getMessage());
            flash('!Could not duplicate the card. Please check the server log.');
            redirect($self);
        }
    }
    if ($do === 'toggle') {
        db()->prepare('UPDATE home_cards SET status = 1 - status WHERE id = ?')->execute([$id]);
        flash('Card status updated.');
        redirect($self);
    }

    if ($id === 0 && !can($user, 'create')) { flash('!Your role cannot create cards.'); redirect($self); }
    $st = $id ? db()->prepare('SELECT * FROM home_cards WHERE id = ?') : null;
    if ($st) { $st->execute([$id]); $existing = $st->fetch() ?: null; }
    if ($id && !$existing) { flash('!That card no longer exists.'); redirect($self); }

    $titleEn = trim((string)($_POST['title_en'] ?? ''));
    $titleBn = trim((string)($_POST['title_bn'] ?? ''));
    $subtitleEn = trim((string)($_POST['subtitle_en'] ?? ''));
    $subtitleBn = trim((string)($_POST['subtitle_bn'] ?? ''));
    $bodyEn = trim((string)($_POST['body_en'] ?? ''));
    $bodyBn = trim((string)($_POST['body_bn'] ?? ''));
    $contentType = (string)($_POST['content_type'] ?? 'mission');
    $tags = trim((string)($_POST['tags'] ?? ''));
    $sortOrder = filter_var($_POST['sort_order'] ?? 0, FILTER_VALIDATE_INT);
    $startInput = trim((string)($_POST['starts_at'] ?? ''));
    $endInput = trim((string)($_POST['ends_at'] ?? ''));
    $startsAt = home_card_parse_datetime($startInput);
    $endsAt = home_card_parse_datetime($endInput);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $newCategoryEn = trim((string)($_POST['new_category_en'] ?? ''));
    $newCategoryBn = trim((string)($_POST['new_category_bn'] ?? ''));

    if ($titleEn === '' || mb_strlen($titleEn) > 190) $formErrors[] = 'Enter an English title no longer than 190 characters.';
    if (mb_strlen($titleBn) > 190 || mb_strlen($subtitleEn) > 190 || mb_strlen($subtitleBn) > 190) $formErrors[] = 'Titles and subtitles must be no longer than 190 characters.';
    if (mb_strlen($bodyEn) > 10000 || mb_strlen($bodyBn) > 10000) $formErrors[] = 'Descriptions must be no longer than 10,000 characters.';
    if (!in_array($contentType, HOME_CARD_CONTENT_TYPES, true)) $formErrors[] = 'Choose a supported card content type.';
    if ($sortOrder === false || $sortOrder < 0 || $sortOrder > 1000000) $formErrors[] = 'Enter a card order between 0 and 1,000,000.';
    if ($startInput !== '' && $startsAt === null) $formErrors[] = 'Enter a valid schedule start date and time.';
    if ($endInput !== '' && $endsAt === null) $formErrors[] = 'Enter a valid schedule end date and time.';
    if ($startsAt && $endsAt && $startsAt > $endsAt) $formErrors[] = 'The schedule end must be after its start.';
    if (mb_strlen($tags) > 500) $formErrors[] = 'Tags must be no longer than 500 characters.';
    if ($categoryId > 0) {
        $categoryCheck = db()->prepare('SELECT COUNT(*) FROM home_card_categories WHERE id = ? AND status = 1');
        $categoryCheck->execute([$categoryId]);
        if (!(int)$categoryCheck->fetchColumn()) $formErrors[] = 'Choose a valid card category.';
    }
    if ($newCategoryEn !== '' && (mb_strlen($newCategoryEn) > 100 || mb_strlen($newCategoryBn) > 100 || slugify($newCategoryEn) === '')) {
        $formErrors[] = 'Enter a category name in English with no more than 100 characters.';
    }

    $stats = home_card_clean_rows(home_card_request_rows('stats'), [
        'label_en' => 120, 'label_bn' => 120, 'value' => 64, 'icon_key' => 32, 'color_key' => 24, 'animate_counter' => 5,
    ], $formErrors, 'statistics');
    foreach ($stats as &$stat) {
        if ($stat['label_en'] === '' || $stat['value'] === '') $formErrors[] = 'Each statistic needs an English label and a value.';
        if (!in_array($stat['icon_key'], HOME_CARD_ICONS, true)) $stat['icon_key'] = 'heart';
        if (!in_array($stat['color_key'], ['brand', 'teal', 'dark'], true)) $stat['color_key'] = 'brand';
        $stat['animate_counter'] = in_array($stat['animate_counter'], ['1','on','true'], true) ? 1 : 0;
    }
    unset($stat);
    $buttons = home_card_clean_rows(home_card_request_rows('buttons'), [
        'label_en' => 80, 'label_bn' => 80, 'url' => 2048, 'button_type' => 24,
    ], $formErrors, 'buttons');
    foreach ($buttons as $button) {
        if ($button['label_en'] === '' || $button['url'] === '' || !home_card_is_valid_button_url($button['url'])) {
            $formErrors[] = 'Each CTA needs an English button label and a valid internal or HTTP(S) URL.';
            break;
        }
    }
    foreach ($buttons as &$button) {
        if (!in_array($button['button_type'], ['primary', 'secondary', 'donation', 'volunteer'], true)) $button['button_type'] = 'primary';
    }
    unset($button);

    $visualType = (string)($_POST['visual_type'] ?? 'icon');
    $iconKey = (string)($_POST['icon_key'] ?? 'heart');
    $backgroundType = (string)($_POST['background_type'] ?? 'solid');
    $patternKey = (string)($_POST['pattern_key'] ?? 'none');
    $theme = (string)($_POST['theme'] ?? 'light');
    $radius = (string)($_POST['border_radius'] ?? 'large');
    $shadow = (string)($_POST['shadow'] ?? 'soft');
    $spacing = (string)($_POST['spacing'] ?? 'comfortable');
    $animationType = (string)($_POST['animation_type'] ?? 'none');
    $triggerType = (string)($_POST['trigger_type'] ?? 'scroll');
    $hoverEffect = (string)($_POST['hover_effect'] ?? 'elevation');
    $duration = filter_var($_POST['duration_ms'] ?? 500, FILTER_VALIDATE_INT);
    $delay = filter_var($_POST['delay_ms'] ?? 0, FILTER_VALIDATE_INT);
    $opacity = filter_var($_POST['overlay_opacity'] ?? 30, FILTER_VALIDATE_INT);
    $color = strtoupper((string)($_POST['background_color'] ?? '#FFFFFF'));
    $gradientStart = strtoupper((string)($_POST['gradient_start'] ?? '#FFFFFF'));
    $gradientEnd = strtoupper((string)($_POST['gradient_end'] ?? '#F7F7F8'));
    $overlayColor = strtoupper((string)($_POST['overlay_color'] ?? '#1B1B1F'));

    if (!in_array($visualType, ['icon', 'image', 'video'], true)) $formErrors[] = 'Choose a supported card visual.';
    if (!in_array($iconKey, HOME_CARD_ICONS, true)) $formErrors[] = 'Choose a supported icon.';
    if (!in_array($backgroundType, ['solid', 'gradient', 'pattern', 'image', 'video', 'animated'], true)) $formErrors[] = 'Choose a supported background.';
    if (!in_array($patternKey, ['none', 'dots'], true) || !in_array($theme, ['light', 'dark', 'brand'], true)
        || !in_array($radius, ['small', 'medium', 'large'], true) || !in_array($shadow, ['none', 'soft', 'strong'], true)
        || !in_array($spacing, ['compact', 'comfortable', 'spacious'], true)) $formErrors[] = 'Choose supported appearance options.';
    if (!in_array($animationType, ['none', 'fade-up', 'float', 'heartbeat', 'reveal'], true)
        || !in_array($triggerType, ['scroll', 'load'], true)
        || !in_array($hoverEffect, ['none', 'elevation', 'glow', 'scale', 'border'], true)) $formErrors[] = 'Choose supported animation options.';
    foreach ([$color, $gradientStart, $gradientEnd, $overlayColor] as $hex) {
        if (!preg_match('/^#[0-9A-F]{6}$/D', $hex)) { $formErrors[] = 'Use valid six-digit colors for the card design.'; break; }
    }
    if ($duration === false || $duration < 150 || $duration > 2000 || $delay === false || $delay < 0 || $delay > 1500
        || $opacity === false || $opacity < 0 || $opacity > 85) $formErrors[] = 'Animation and overlay values are outside the allowed range.';
    if ($visualType === 'video' && empty($_FILES['video']['name']) && empty(home_card_media($id)['video_path'])) {
        $formErrors[] = 'Upload an MP4 or WebM file for a video visual.';
    }

    if (!$formErrors) {
        $imagePath = (string)($existing['image'] ?? '');
        $mediaExisting = $id ? (home_card_media($id) ?? []) : [];
        if (!empty($_FILES['image']['name'])) {
            $newImage = upload_image('image');
            if ($newImage === null) $formErrors[] = 'Card image: ' . (upload_last_error() ?: 'upload failed.');
            else {
                $imagePath = $newImage;
                $uploadedPaths[] = $newImage;
                media_library_register_path($newImage, (string)($_FILES['image']['name'] ?? ''), media_library_current_admin_id());
            }
        } elseif (!empty($_POST['media_selection']['image'])) {
            $selected = media_library_resolve_selection((int)$_POST['media_selection']['image'], [], ['image'], MAX_IMAGE_BYTES);
            if (!$selected) $formErrors[] = 'Card image: selected library image is unavailable or invalid.';
            else $imagePath = $selected['path'];
        } elseif (isset($_POST['remove_image'])) {
            $imagePath = '';
        }
        $videoPath = (string)($mediaExisting['video_path'] ?? '');
        if (!empty($_FILES['video']['name'])) {
            try {
                $newVideo = home_card_store_video('video');
                if ($newVideo) {
                    $videoPath = $newVideo;
                    $uploadedPaths[] = $newVideo;
                    media_library_register_path($newVideo, (string)($_FILES['video']['name'] ?? ''), media_library_current_admin_id());
                }
            } catch (Throwable $e) {
                $formErrors[] = $e->getMessage();
            }
        } elseif (!empty($_POST['media_selection']['video'])) {
            $selected = media_library_resolve_selection((int)$_POST['media_selection']['video'], ['mp4', 'webm'], ['video'], HOME_CARD_VIDEO_LIMIT);
            if (!$selected) $formErrors[] = 'Background video: selected library video is unavailable or invalid.';
            else $videoPath = $selected['path'];
        } elseif (isset($_POST['remove_video'])) {
            $videoPath = '';
        }
        if ($visualType === 'image' && $imagePath === '') $formErrors[] = 'Upload an image for this card visual.';
        if ($visualType === 'video' && $videoPath === '') $formErrors[] = 'Upload an MP4 or WebM file for a video visual.';
        $posterPath = $imagePath;

        if (!$formErrors) {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                if ($newCategoryEn !== '') {
                    $slug = slugify($newCategoryEn);
                    $pdo->prepare('INSERT INTO home_card_categories (slug,name_en,name_bn,status)
                        VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE name_en=VALUES(name_en),name_bn=VALUES(name_bn),status=1')
                        ->execute([$slug, $newCategoryEn, $newCategoryBn ?: null]);
                    $categoryStmt = $pdo->prepare('SELECT id FROM home_card_categories WHERE slug = ?');
                    $categoryStmt->execute([$slug]);
                    $categoryId = (int)$categoryStmt->fetchColumn();
                }
                $firstLink = $buttons[0]['url'] ?? '';
                $slot = max(0, min(2147483647, (int)$sortOrder));
                $values = [
                    $slot, $titleEn, $titleBn ?: null, $subtitleEn ?: null, $subtitleBn ?: null,
                    $bodyEn ?: null, $bodyBn ?: null, $contentType, $categoryId ?: null, $tags ?: null,
                    $imagePath ?: null, $firstLink, isset($_POST['status']) ? 1 : 0, $slot,
                    isset($_POST['is_featured']) ? 1 : 0, $startsAt, $endsAt, isset($_POST['is_archived']) ? 1 : 0,
                ];
                if ($id) {
                    $pdo->prepare('UPDATE home_cards SET slot=?,title_en=?,title_bn=?,subtitle_en=?,subtitle_bn=?,body_en=?,body_bn=?,
                        content_type=?,category_id=?,tags=?,image=?,link_path=?,status=?,sort_order=?,is_featured=?,starts_at=?,ends_at=?,is_archived=?
                        WHERE id=?')->execute([...$values, $id]);
                } else {
                    $pdo->prepare('INSERT INTO home_cards
                        (slot,title_en,title_bn,subtitle_en,subtitle_bn,body_en,body_bn,content_type,category_id,tags,image,link_path,status,sort_order,is_featured,starts_at,ends_at,is_archived)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute($values);
                    $id = (int)$pdo->lastInsertId();
                }
                home_card_save_children($pdo, $id, $stats, $buttons, [
                    'visual_type' => $visualType, 'icon_key' => $iconKey,
                    'image_path' => $imagePath ?: null, 'video_path' => $videoPath ?: null, 'poster_path' => $posterPath ?: null,
                ], [
                    'background_type' => $backgroundType, 'background_color' => $color, 'gradient_start' => $gradientStart,
                    'gradient_end' => $gradientEnd, 'pattern_key' => $patternKey, 'theme' => $theme, 'border_radius' => $radius,
                    'shadow' => $shadow, 'spacing' => $spacing, 'overlay_color' => $overlayColor, 'overlay_opacity' => $opacity,
                ], [
                    'animation_type' => $animationType, 'trigger_type' => $triggerType, 'duration_ms' => $duration,
                    'delay_ms' => $delay, 'hover_effect' => $hoverEffect, 'heartbeat' => isset($_POST['heartbeat']) ? 1 : 0,
                ]);
                $pdo->commit();
                $oldPaths = array_filter([
                    $existing['image'] ?? null, $mediaExisting['image_path'] ?? null,
                    $mediaExisting['poster_path'] ?? null, $mediaExisting['video_path'] ?? null,
                ]);
                home_card_cleanup_unused_media($oldPaths);
                flash('Card saved.');
                redirect($self);
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Home card save failed: ' . $e->getMessage());
                $formErrors[] = 'Could not save this card. Please check the fields and server log.';
            }
        }
    }
    if ($uploadedPaths) home_card_cleanup_unused_media($uploadedPaths);
}

if ($action === 'new' || $action === 'edit') {
    if ($action === 'new' && !can($user, 'create')) { flash('!Your role cannot create cards.'); redirect($self); }
    $formValues = $existing ?? [];
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'save') {
        foreach (['title_en','title_bn','subtitle_en','subtitle_bn','body_en','body_bn','content_type','tags','sort_order','starts_at','ends_at','category_id','new_category_en','new_category_bn','visual_type','icon_key','background_type','background_color','gradient_start','gradient_end','pattern_key','theme','border_radius','shadow','spacing','overlay_color','overlay_opacity','animation_type','trigger_type','duration_ms','delay_ms','hover_effect'] as $key) {
            if (array_key_exists($key, $_POST)) $formValues[$key] = $_POST[$key];
        }
        foreach (['status','is_featured','is_archived','heartbeat'] as $key) $formValues[$key] = isset($_POST[$key]) ? 1 : 0;
        $formValues['sort_order'] = $_POST['sort_order'] ?? ($existing['sort_order'] ?? $existing['slot'] ?? 0);
        $formValues['starts_at'] = $_POST['starts_at'] ?? '';
        $formValues['ends_at'] = $_POST['ends_at'] ?? '';
        $formValues['image'] = $existing['image'] ?? null;
            $formValues['video_path'] = (home_card_media($id) ?? [])['video_path'] ?? '';
    }
    $media = $id ? (home_card_media($id) ?? []) : [];
    $style = $id ? (home_card_style($id) ?? []) : [];
    $motion = $id ? (home_card_animation($id) ?? []) : [];
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['do'] ?? '') !== 'save') {
        $stats = $id ? home_card_stats($id) : [];
        $buttons = $id ? home_card_buttons($id) : [];
        if (!$buttons && $existing && trim((string)($existing['link_path'] ?? '')) !== '') {
            $buttons = [[
                'label_en' => 'Learn more',
                'label_bn' => null,
                'url' => (string)$existing['link_path'],
                'button_type' => 'primary',
            ]];
        }
    }
    if (isset($formValues['category_en'])) $formValues['category_id'] = $existing['category_id'] ?? 0;
    $defaults = [
        'content_type' => 'mission', 'visual_type' => 'icon', 'icon_key' => 'heart',
        'background_type' => 'solid', 'background_color' => '#FFFFFF', 'gradient_start' => '#FFFFFF',
        'gradient_end' => '#F7F7F8', 'pattern_key' => 'none', 'theme' => 'light',
        'border_radius' => 'large', 'shadow' => 'soft', 'spacing' => 'comfortable',
        'overlay_color' => '#1B1B1F', 'overlay_opacity' => 30, 'animation_type' => 'none',
        'trigger_type' => 'scroll', 'duration_ms' => 500, 'delay_ms' => 0,
        'hover_effect' => 'elevation', 'sort_order' => $existing['sort_order'] ?? $existing['slot'] ?? 0,
        'status' => 1, 'is_featured' => 0, 'is_archived' => 0, 'heartbeat' => 0,
    ];
    $formValues = array_merge($defaults, $style, $motion, $media, $existing ?? [], $formValues);
    if ($existing && empty($formValues['category_id'])) $formValues['category_id'] = $existing['category_id'] ?? 0;
    if ($existing && !isset($media['visual_type'])) $formValues['visual_type'] = !empty($existing['image']) ? 'image' : 'icon';
    $categories = db()->query('SELECT id,name_en,name_bn FROM home_card_categories WHERE status=1 ORDER BY sort_order,name_en')->fetchAll();
    admin_header(($id ? 'Edit' : 'New') . ' homepage card', $user, 'home_cards');
    ?>
    <div class="a-pagehead">
      <h1><?= $id ? 'Edit' : 'New' ?> homepage card</h1>
      <a class="a-btn a-btn-sec" href="<?= e($self) ?>">← Back to cards</a>
    </div>
    <?php if ($formErrors): ?><div class="a-msg a-err" role="alert"><ul><?php foreach (array_unique($formErrors) as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="a-card a-form home-card-form" data-dirty-guard>
      <?= csrf_field() ?>
      <input type="hidden" name="do" value="save">
      <input type="hidden" name="id" value="<?= $id ?>">
      <fieldset class="a-group-box"><legend>Content</legend>
        <div class="a-field"><label for="title_en">Title (English) <span class="a-req">*</span></label><input id="title_en" name="title_en" maxlength="190" required value="<?= e((string)home_card_form_value($formValues,'title_en')) ?>"></div>
        <div class="a-field"><label for="title_bn">Title (বাংলা)</label><input id="title_bn" name="title_bn" maxlength="190" value="<?= e((string)home_card_form_value($formValues,'title_bn')) ?>"></div>
        <div class="a-field"><label for="subtitle_en">Subtitle (English)</label><input id="subtitle_en" name="subtitle_en" maxlength="190" value="<?= e((string)home_card_form_value($formValues,'subtitle_en')) ?>"></div>
        <div class="a-field"><label for="subtitle_bn">Subtitle (বাংলা)</label><input id="subtitle_bn" name="subtitle_bn" maxlength="190" value="<?= e((string)home_card_form_value($formValues,'subtitle_bn')) ?>"></div>
        <div class="a-field"><label for="body_en">Description (English)</label><textarea id="body_en" name="body_en" maxlength="10000"><?= e((string)home_card_form_value($formValues,'body_en')) ?></textarea></div>
        <div class="a-field"><label for="body_bn">Description (বাংলা)</label><textarea id="body_bn" name="body_bn" maxlength="10000"><?= e((string)home_card_form_value($formValues,'body_bn')) ?></textarea></div>
        <div class="a-field"><label for="content_type">Content type</label><select id="content_type" name="content_type">
          <?php foreach (HOME_CARD_CONTENT_TYPES as $option): ?><option value="<?= e($option) ?>"<?= home_card_form_value($formValues,'content_type') === $option ? ' selected' : '' ?>><?= e(ucwords(str_replace('_',' ',$option))) ?></option><?php endforeach; ?>
        </select></div>
        <div class="a-field"><label for="category_id">Category</label><select id="category_id" name="category_id"><option value="0">— None —</option>
          <?php foreach ($categories as $category): ?><option value="<?= (int)$category['id'] ?>"<?= (int)home_card_form_value($formValues,'category_id',0) === (int)$category['id'] ? ' selected' : '' ?>><?= e($category['name_en']) ?></option><?php endforeach; ?>
        </select></div>
        <details class="a-adv"><summary>Add or update a category</summary>
          <div class="a-field"><label for="new_category_en">Category name (English)</label><input id="new_category_en" name="new_category_en" maxlength="100" value="<?= e((string)home_card_form_value($formValues,'new_category_en')) ?>"></div>
          <div class="a-field"><label for="new_category_bn">Category name (বাংলা)</label><input id="new_category_bn" name="new_category_bn" maxlength="100" value="<?= e((string)home_card_form_value($formValues,'new_category_bn')) ?>"></div>
        </details>
        <div class="a-field"><label for="tags">Tags (comma-separated)</label><input id="tags" name="tags" maxlength="500" value="<?= e((string)home_card_form_value($formValues,'tags')) ?>"></div>
      </fieldset>

      <fieldset class="a-group-box"><legend>Media and visuals</legend>
        <div class="a-field"><label for="visual_type">Visual type</label><select id="visual_type" name="visual_type">
          <?php foreach (['icon' => 'SVG icon', 'image' => 'Image / illustration', 'video' => 'Video background'] as $value => $label): ?><option value="<?= e($value) ?>"<?= home_card_form_value($formValues,'visual_type') === $value ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
        </select></div>
        <div class="a-field"><label for="icon_key">Icon</label><select id="icon_key" name="icon_key">
          <?php foreach (HOME_CARD_ICONS as $icon): ?><option value="<?= e($icon) ?>"<?= home_card_form_value($formValues,'icon_key') === $icon ? ' selected' : '' ?>><?= e(ucfirst($icon)) ?></option><?php endforeach; ?>
        </select></div>
        <?php if (!empty($formValues['image'])): ?><div class="a-field"><label>Current image</label><img class="a-thumb" src="<?= e(asset(img_variant((string)$formValues['image'],'-480'))) ?>" alt=""><label class="a-inline-check"><input type="checkbox" name="remove_image" value="1"> Remove image</label></div><?php endif; ?>
        <div class="a-field"><label for="image">Card image / video poster</label><input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"><?php render_media_picker('image', 'image', ['jpg', 'jpeg', 'png', 'webp']); ?><p class="a-help">JPG, PNG or WebP. This also supplies a poster when using video.</p></div>
        <?php if (!empty($formValues['video_path'])): ?><div class="a-field"><label>Current background video</label><video controls preload="metadata" style="max-width:320px"><source src="<?= e(asset((string)$formValues['video_path'])) ?>"></video><label class="a-inline-check"><input type="checkbox" name="remove_video" value="1"> Remove video</label></div><?php endif; ?>
        <div class="a-field"><label for="video">Video background (MP4 or WebM, max 24 MB)</label><input type="file" id="video" name="video" accept="video/mp4,video/webm"><?php render_media_picker('video', 'video', ['mp4', 'webm']); ?></div>
      </fieldset>

      <fieldset class="a-group-box"><legend>Appearance</legend>
        <?php
        $selectField = static function (string $name, string $label, array $options, array $values): void {
            echo '<div class="a-field"><label for="' . e($name) . '">' . e($label) . '</label><select id="' . e($name) . '" name="' . e($name) . '">';
            foreach ($options as $value => $caption) echo '<option value="' . e((string)$value) . '"' . ((string)home_card_form_value($values,$name) === (string)$value ? ' selected' : '') . '>' . e($caption) . '</option>';
            echo '</select></div>';
        };
        $selectField('background_type','Background type',['solid'=>'Solid color','gradient'=>'Gradient','pattern'=>'Pattern','image'=>'Image','video'=>'Video','animated'=>'Animated CSS background'],$formValues);
        ?>
        <div class="home-card-fields-grid">
          <?php foreach (['background_color'=>'Background color','gradient_start'=>'Gradient start','gradient_end'=>'Gradient end','overlay_color'=>'Overlay color'] as $field=>$label): ?>
            <div class="a-field"><label for="<?= e($field) ?>"><?= e($label) ?></label><input type="color" id="<?= e($field) ?>" name="<?= e($field) ?>" value="<?= e((string)home_card_form_value($formValues,$field)) ?>"></div>
          <?php endforeach; ?>
          <div class="a-field"><label for="overlay_opacity">Overlay opacity (0–85%)</label><input type="number" id="overlay_opacity" name="overlay_opacity" min="0" max="85" value="<?= (int)home_card_form_value($formValues,'overlay_opacity') ?>"></div>
        </div>
        <?php
        $selectField('pattern_key','Pattern',['none'=>'None','dots'=>'Subtle dots'],$formValues);
        $selectField('theme','Text theme',['light'=>'Light','dark'=>'Dark','brand'=>'Brand'],$formValues);
        $selectField('border_radius','Corner radius',['small'=>'Small','medium'=>'Medium','large'=>'Large'],$formValues);
        $selectField('shadow','Shadow',['none'=>'None','soft'=>'Soft','strong'=>'Strong'],$formValues);
        $selectField('spacing','Card spacing',['compact'=>'Compact','comfortable'=>'Comfortable','spacious'=>'Spacious'],$formValues);
        ?>
      </fieldset>

      <fieldset class="a-group-box"><legend>Motion</legend>
        <?php
        $selectField('animation_type','Reveal / background animation',['none'=>'None','fade-up'=>'Fade up','reveal'=>'Reveal','float'=>'Floating visual','heartbeat'=>'Heartbeat visual'],$formValues);
        $selectField('trigger_type','Animation trigger',['scroll'=>'On scroll','load'=>'On page load'],$formValues);
        $selectField('hover_effect','Hover effect',['none'=>'None','elevation'=>'Elevation','glow'=>'Glow','scale'=>'Smooth scale','border'=>'Border accent'],$formValues);
        ?>
        <div class="home-card-fields-grid">
          <div class="a-field"><label for="duration_ms">Duration (150–2000 ms)</label><input type="number" id="duration_ms" name="duration_ms" min="150" max="2000" value="<?= (int)home_card_form_value($formValues,'duration_ms') ?>"></div>
          <div class="a-field"><label for="delay_ms">Delay (0–1500 ms)</label><input type="number" id="delay_ms" name="delay_ms" min="0" max="1500" value="<?= (int)home_card_form_value($formValues,'delay_ms') ?>"></div>
        </div>
        <label class="a-inline-check"><input type="checkbox" name="heartbeat" value="1"<?= !empty($formValues['heartbeat']) ? ' checked' : '' ?>> Heartbeat icon micro-animation</label>
        <p class="a-help">All motion is CSS-based and automatically reduced when visitors prefer less motion. No remote animation scripts are loaded.</p>
      </fieldset>

      <fieldset class="a-group-box"><legend>Statistics</legend>
        <div data-card-rows="stats">
          <?php foreach ($stats ?: [[]] as $idx => $stat): ?>
            <div class="a-card-row" data-card-row>
              <div class="a-field"><label>Label (English)</label><input name="stats[label_en][<?= (int)$idx ?>]" maxlength="120" value="<?= e((string)($stat['label_en'] ?? '')) ?>"></div>
              <div class="a-field"><label>Label (বাংলা)</label><input name="stats[label_bn][<?= (int)$idx ?>]" maxlength="120" value="<?= e((string)($stat['label_bn'] ?? '')) ?>"></div>
              <div class="a-field"><label>Value</label><input name="stats[value][<?= (int)$idx ?>]" maxlength="64" value="<?= e((string)($stat['value'] ?? '')) ?>"></div>
              <div class="a-field"><label>Icon</label><select name="stats[icon_key][<?= (int)$idx ?>]"><?php foreach (HOME_CARD_ICONS as $icon): ?><option value="<?= e($icon) ?>"<?= ($stat['icon_key'] ?? 'heart') === $icon ? ' selected' : '' ?>><?= e(ucfirst($icon)) ?></option><?php endforeach; ?></select></div>
              <div class="a-field"><label>Color</label><select name="stats[color_key][<?= (int)$idx ?>]"><?php foreach (['brand','teal','dark'] as $colorKey): ?><option value="<?= e($colorKey) ?>"<?= ($stat['color_key'] ?? 'brand') === $colorKey ? ' selected' : '' ?>><?= e(ucfirst($colorKey)) ?></option><?php endforeach; ?></select></div>
              <input type="hidden" name="stats[animate_counter][<?= (int)$idx ?>]" value="0">
              <label class="a-inline-check"><input type="checkbox" name="stats[animate_counter][<?= (int)$idx ?>]" value="1"<?= !empty($stat['animate_counter']) ? ' checked' : '' ?>> Animate numeric value</label>
              <button type="button" class="a-btn a-btn-sm" data-card-remove>Remove statistic</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="a-btn a-btn-sec" data-card-add="stats">+ Add statistic</button>
        <template data-card-template="stats"><div class="a-card-row" data-card-row>
          <div class="a-field"><label>Label (English)</label><input name="stats[label_en][__INDEX__]" maxlength="120"></div>
          <div class="a-field"><label>Label (বাংলা)</label><input name="stats[label_bn][__INDEX__]" maxlength="120"></div>
          <div class="a-field"><label>Value</label><input name="stats[value][__INDEX__]" maxlength="64"></div>
          <div class="a-field"><label>Icon</label><select name="stats[icon_key][__INDEX__]"><?php foreach (HOME_CARD_ICONS as $icon): ?><option value="<?= e($icon) ?>"><?= e(ucfirst($icon)) ?></option><?php endforeach; ?></select></div>
          <div class="a-field"><label>Color</label><select name="stats[color_key][__INDEX__]"><?php foreach (['brand','teal','dark'] as $colorKey): ?><option value="<?= e($colorKey) ?>"><?= e(ucfirst($colorKey)) ?></option><?php endforeach; ?></select></div>
          <input type="hidden" name="stats[animate_counter][__INDEX__]" value="0">
          <label class="a-inline-check"><input type="checkbox" name="stats[animate_counter][__INDEX__]" value="1"> Animate numeric value</label>
          <button type="button" class="a-btn a-btn-sm" data-card-remove>Remove statistic</button>
        </div></template>
      </fieldset>

      <fieldset class="a-group-box"><legend>Calls to action</legend>
        <div data-card-rows="buttons">
          <?php foreach ($buttons ?: [[]] as $idx => $button): ?>
            <div class="a-card-row" data-card-row>
              <div class="a-field"><label>Button label (English)</label><input name="buttons[label_en][<?= (int)$idx ?>]" maxlength="80" value="<?= e((string)($button['label_en'] ?? '')) ?>"></div>
              <div class="a-field"><label>Button label (বাংলা)</label><input name="buttons[label_bn][<?= (int)$idx ?>]" maxlength="80" value="<?= e((string)($button['label_bn'] ?? '')) ?>"></div>
              <div class="a-field"><label>URL</label><input name="buttons[url][<?= (int)$idx ?>]" maxlength="2048" placeholder="heart-health or https://example.org" value="<?= e((string)($button['url'] ?? '')) ?>"></div>
              <div class="a-field"><label>Button type</label><select name="buttons[button_type][<?= (int)$idx ?>]"><?php foreach (['primary','secondary','donation','volunteer'] as $type): ?><option value="<?= e($type) ?>"<?= ($button['button_type'] ?? 'primary') === $type ? ' selected' : '' ?>><?= e(ucfirst($type)) ?></option><?php endforeach; ?></select></div>
              <button type="button" class="a-btn a-btn-sm" data-card-remove>Remove button</button>
            </div>
          <?php endforeach; ?>
        </div>
        <button type="button" class="a-btn a-btn-sec" data-card-add="buttons">+ Add button</button>
        <template data-card-template="buttons"><div class="a-card-row" data-card-row>
          <div class="a-field"><label>Button label (English)</label><input name="buttons[label_en][__INDEX__]" maxlength="80"></div>
          <div class="a-field"><label>Button label (বাংলা)</label><input name="buttons[label_bn][__INDEX__]" maxlength="80"></div>
          <div class="a-field"><label>URL</label><input name="buttons[url][__INDEX__]" maxlength="2048" placeholder="heart-health or https://example.org"></div>
          <div class="a-field"><label>Button type</label><select name="buttons[button_type][__INDEX__]"><?php foreach (['primary','secondary','donation','volunteer'] as $type): ?><option value="<?= e($type) ?>"><?= e(ucfirst($type)) ?></option><?php endforeach; ?></select></div>
          <button type="button" class="a-btn a-btn-sm" data-card-remove>Remove button</button>
        </div></template>
      </fieldset>

      <fieldset class="a-group-box"><legend>Publishing</legend>
        <div class="home-card-fields-grid">
          <div class="a-field"><label for="sort_order">Display order (lower appears first)</label><input type="number" id="sort_order" name="sort_order" min="0" max="1000000" value="<?= (int)home_card_form_value($formValues,'sort_order') ?>"></div>
          <div class="a-field"><label for="starts_at">Schedule start (optional)</label><input type="datetime-local" id="starts_at" name="starts_at" value="<?= e(home_card_datetime_input($formValues['starts_at'] ?? null)) ?>"></div>
          <div class="a-field"><label for="ends_at">Schedule end (optional)</label><input type="datetime-local" id="ends_at" name="ends_at" value="<?= e(home_card_datetime_input($formValues['ends_at'] ?? null)) ?>"></div>
        </div>
        <label class="a-inline-check"><input type="checkbox" name="status" value="1"<?= !empty($formValues['status']) ? ' checked' : '' ?>> Published / enabled</label>
        <label class="a-inline-check"><input type="checkbox" name="is_featured" value="1"<?= !empty($formValues['is_featured']) ? ' checked' : '' ?>> Pin as featured (shown first)</label>
        <label class="a-inline-check"><input type="checkbox" name="is_archived" value="1"<?= !empty($formValues['is_archived']) ? ' checked' : '' ?>> Archive this card (keep data, hide from homepage)</label>
      </fieldset>
      <div class="a-savebar"><button type="submit" class="a-btn a-btn-primary">Save card</button><a class="a-btn a-btn-sec" href="<?= e($self) ?>">Cancel</a></div>
    </form>
    <?php
    admin_footer();
    exit;
}

$rows = db()->query('SELECT hc.*, hcc.name_en AS category_en
    FROM home_cards hc LEFT JOIN home_card_categories hcc ON hcc.id=hc.category_id
    ORDER BY hc.is_featured DESC, COALESCE(hc.sort_order,hc.slot), hc.id')->fetchAll();
admin_header('Homepage card manager', $user, 'home_cards');
?>
<div class="a-pagehead">
  <h1>Homepage card manager</h1>
  <?php if (can($user, 'create')): ?><a class="a-btn a-btn-primary" href="<?= e($self) ?>?action=new">+ New card</a><?php endif; ?>
</div>
<p class="a-help">Manage the mission, programs, and impact cards shown near the top of the homepage. Scheduled, archived, and unpublished cards remain in this list.</p>
<?php if (!$rows): ?>
  <p class="a-empty">No homepage cards yet.<?= can($user, 'create') ? ' Add a card to get started.' : '' ?></p>
<?php else: ?>
  <div class="a-card-list">
    <?php foreach ($rows as $row): ?>
      <article class="a-card a-card-list-item">
        <?php if (!empty($row['image'])): ?><img class="a-thumb" src="<?= e(asset(img_variant((string)$row['image'],'-480'))) ?>" alt="" loading="lazy"><?php endif; ?>
        <div class="a-card-list-main">
          <h2><?= e((string)$row['title_en']) ?><?= !empty($row['is_featured']) ? ' · Featured' : '' ?></h2>
          <p><?= e($row['category_en'] ?: ucwords(str_replace('_',' ',(string)($row['content_type'] ?? 'card')))) ?> · Order <?= (int)($row['sort_order'] ?? $row['slot']) ?></p>
          <div class="a-card-list-badges">
            <span class="a-pill <?= !empty($row['status']) ? 'a-pill-ok' : 'a-pill-draft' ?>"><?= !empty($row['status']) ? 'Published' : 'Draft' ?></span>
            <?php if (!empty($row['is_archived'])): ?><span class="a-pill a-pill-draft">Archived</span><?php endif; ?>
            <?php if (!empty($row['starts_at']) && strtotime((string)$row['starts_at']) > time()): ?><span class="a-pill a-pill-draft">Scheduled</span><?php endif; ?>
          </div>
        </div>
        <div class="a-row-actions">
          <a class="a-btn a-btn-sm" href="<?= e($self) ?>?action=edit&amp;id=<?= (int)$row['id'] ?>">Edit</a>
          <?= admin_post_button($self, ['do'=>'toggle','id'=>$row['id']], !empty($row['status']) ? 'Unpublish' : 'Publish') ?>
          <?php if (can($user, 'create')): ?><?= admin_post_button($self, ['do'=>'duplicate','id'=>$row['id']], 'Duplicate') ?><?php endif; ?>
          <?php if (can($user, 'delete')): ?><?= admin_post_button($self, ['do'=>'delete','id'=>$row['id']], 'Delete', 'Delete this card? Any media still used by another card will be kept.', 'a-btn a-btn-sm a-btn-danger-o') ?><?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
<?php
} catch (PDOException $e) {
    error_log('Home card manager request failed: ' . $e->getMessage());
    http_response_code(503);
    admin_header('Homepage card manager unavailable', $user, 'home_cards');
    echo '<div class="a-msg a-err" role="alert">Card management is temporarily unavailable. Verify that migration 006 is applied and check the server log.</div>';
    admin_footer();
}
?>
