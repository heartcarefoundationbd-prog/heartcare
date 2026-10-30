<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin('content');

$TYPES = require __DIR__ . '/types.php';
$type = (string)($_GET['type'] ?? $_POST['type'] ?? 'news');
if ($type === 'health_topics') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirect(admin_url('content.php?type=publications'));
    }
    $type = 'publications';
}
if (!isset($TYPES[$type])) { http_response_code(400); exit('Unknown content type.'); }
$cfg   = $TYPES[$type];
$table = $cfg['table'];
$self  = admin_url('content.php');
$listUrl = $self . '?type=' . urlencode($type);
if ($type === 'home_cards') {
    $legacyAction = (string)($_GET['action'] ?? '');
    $legacyId = (int)($_GET['id'] ?? 0);
    redirect(admin_url($legacyAction === 'new'
        ? 'home-cards.php?action=new'
        : ($legacyAction === 'edit' && $legacyId > 0 ? 'home-cards.php?action=edit&id=' . $legacyId : 'home-cards.php')));
}

/* ---------- helpers ---------- */
function field_cols(array $f): array
{
    return !empty($f['bi']) ? [$f['name'] . '_en', $f['name'] . '_bn'] : [$f['name']];
}

function field_options(array $f): array
{
    $o = $f['options'] ?? [];
    return is_callable($o) ? $o() : $o;
}

function fetch_row(string $table, int $id): ?array
{
    $st = db()->prepare("SELECT * FROM `$table` WHERE id = ?");
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

function valid_date(string $s): ?string
{
    $d = DateTime::createFromFormat('!Y-m-d', $s);
    return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

/** Returns [data, errors, filesToDeleteAfterSuccess]. */
function collect_post(array $cfg, ?array $existing): array
{
    $data = []; $errors = []; $trash = [];

    foreach ($cfg['fields'] as $f) {
        $name = $f['name']; $type = $f['type'];
        $req  = $f['req'] ?? false;

        if ($type === 'text' || $type === 'textarea' || $type === 'html') {
            foreach (field_cols($f) as $col) {
                $raw = (string)($_POST[$col] ?? '');
                if ($type === 'html') {
                    $v = sanitize_html($raw);
                } else {
                    $v = trim($raw);
                    if ($type === 'text') $v = preg_replace('/\s+/u', ' ', $v) ?? $v;
                    if (!empty($f['max'])) $v = mb_substr($v, 0, (int)$f['max']);
                }
                $isEn = !empty($f['bi']) ? str_ends_with($col, '_en') : true;
                if ($v === '' && (($req === true && $isEn) || ($req === 'en' && str_ends_with($col, '_en')))) {
                    $errors[] = $f['label'] . ' is required' . (!empty($f['bi']) ? ' (English)' : '') . '.';
                }
                $data[$col] = (!empty($f['bi']) && $v === '' && $type !== 'html' && str_ends_with($col, '_bn')) ? null : $v;
                if ($type === 'html' && $v === '') $data[$col] = null;
                if (in_array($col, ['title_bn', 'caption_bn', 'name_bn', 'summary_bn', 'excerpt_bn', 'location_bn', 'body_bn'], true) && $v === '') $data[$col] = null;
            }
        } elseif ($type === 'date') {
            $raw = trim((string)($_POST[$name] ?? ''));
            $v = $raw === '' ? null : valid_date($raw);
            if ($raw !== '' && $v === null) $errors[] = $f['label'] . ' is not a valid date.';
            if ($v === null && ($f['default'] ?? '') === 'today' && empty($f['null'])) $v = date('Y-m-d');
            if ($v === null && $req === true && empty($f['null'])) $errors[] = $f['label'] . ' is required.';
            $data[$name] = $v;
        } elseif ($type === 'int') {
            $raw = trim((string)($_POST[$name] ?? ''));
            $data[$name] = $raw === '' ? (!empty($f['null']) ? null : (int)($f['default'] ?? 0)) : (int)$raw;
        } elseif ($type === 'url') {
            $raw = trim((string)($_POST[$name] ?? ''));
            if ($raw !== '' && (mb_strlen($raw) > (int)($f['max'] ?? 2048) || !valid_http_url($raw))) {
                $errors[] = $f['label'] . ' must be a valid http:// or https:// URL.';
                $data[$name] = $existing[$name] ?? null;
            } else {
                $data[$name] = $raw !== '' ? $raw : null;
            }
        } elseif ($type === 'check') {
            $data[$name] = isset($_POST[$name]) ? 1 : 0;
        } elseif ($type === 'select') {
            $opts = field_options($f);
            $raw = (string)($_POST[$name] ?? '');
            if ($raw === '' && !empty($f['null'])) { $data[$name] = null; }
            elseif (isset($opts[$raw])) { $data[$name] = ctype_digit($raw) ? (int)$raw : $raw; }
            else {
                $data[$name] = $existing[$name] ?? ($f['default'] ?? null);
                if ($req === true && $data[$name] === null) $errors[] = $f['label'] . ' is required.';
            }
        } elseif ($type === 'tags' || $type === 'multi_images') {
            continue;
        } elseif ($type === 'image') {
            $old = $existing[$name] ?? null;
            if (!empty($_FILES[$name]['name'])) {
                $new = upload_image($name);
                if ($new === null) { $errors[] = $f['label'] . ': ' . (upload_last_error() ?: 'upload failed.'); }
                else { $data[$name] = $new; if ($old) $trash[] = $old; }
            } elseif (isset($_POST['_remove_' . $name]) && $req !== true) {
                $data[$name] = null; if ($old) $trash[] = $old;
            } elseif ($req === true && empty($old)) {
                $errors[] = $f['label'] . ' is required.';
            }
        } elseif ($type === 'file') {
            $cols = $f['cols'];
            $old = $existing[$cols['path']] ?? null;
            if (!empty($_FILES[$name]['name'])) {
                $up = upload_document($name, $f['extensions'] ?? []);
                if ($up === null) { $errors[] = $f['label'] . ': ' . (upload_last_error() ?: 'upload failed.'); }
                else {
                    $data[$cols['path']] = $up[0];
                    $data[$cols['name']] = $up[1];
                    if (isset($cols['size'])) $data[$cols['size']] = $up[2];
                    if (isset($cols['ext']))  $data[$cols['ext']]  = $up[3];
                    if ($old) $trash[] = $old;
                }
            } elseif (isset($_POST['_remove_' . $name]) && $req !== true) {
                $data[$cols['path']] = null; $data[$cols['name']] = null;
                if ($old) $trash[] = $old;
            } elseif ($req === true && empty($old)) {
                $errors[] = $f['label'] . ' is required.';
            }
        }
    }
    return [$data, $errors, $trash];
}

function remove_stored_file(?string $path): void
{
    if (!$path) return;
    if (str_starts_with($path, 'assets/uploads/docs/')) {
        if (!str_contains($path, '..')) @unlink(APP_ROOT . '/' . $path);
    } else {
        delete_upload($path);
    }
}

function cleanup_new_uploads(array $cfg, array $data, ?array $existing): void
{
    foreach ($cfg['fields'] as $f) {
        if ($f['type'] === 'image') {
            $path = $data[$f['name']] ?? null;
            if ($path && $path !== ($existing[$f['name']] ?? null)) remove_stored_file($path);
        } elseif ($f['type'] === 'file') {
            $path = $data[$f['cols']['path']] ?? null;
            if ($path && $path !== ($existing[$f['cols']['path']] ?? null)) remove_stored_file($path);
        }
    }
}

function render_field(array $f, ?array $row, int $uid): void
{
    $type = $f['type']; $name = $f['name'];
    $help = !empty($f['help']) ? '<p class="a-help" id="h-' . $uid . '">' . e($f['help']) . '</p>' : '';

    if ($type === 'multi_images') {
        echo '<fieldset class="a-group-box"><legend>' . e($f['label']) . '</legend>';
        if (!empty($row['id'])) {
            $saved = news_images((int)$row['id']);
            foreach ($saved as $image) {
                $imageId = (int)$image['id'];
                echo '<div class="a-existing-image"><img src="' . e(asset(img_variant((string)$image['image_path'], '-480'))) . '" alt="" loading="lazy">'
                   . '<label>Order <input type="number" name="news_image_order[' . $imageId . ']" value="' . (int)$image['sort_order'] . '" min="0" step="1"></label>'
                   . '<label class="a-inline-check"><input type="checkbox" name="news_image_remove[]" value="' . $imageId . '"> Remove image</label></div>';
            }
        }
        echo '<div class="a-field"><label for="f-news-images">Add article images</label>'
           . '<input type="file" id="f-news-images" name="news_images[]" accept="image/jpeg,image/png,image/webp" multiple>'
           . '<div class="a-multi-preview" data-multi-preview></div>'
           . '<p class="a-help">JPG, PNG or WebP. Each image is checked and limited to ' . e(human_size(MAX_IMAGE_BYTES)) . '.</p></div>';
        echo '</fieldset>';
        return;
    }

    if ($type === 'tags') {
        $options = field_options($f);
        $selected = array_map('strval', (array)($row['news_tag_ids'] ?? []));
        if (!$selected && !empty($row['id'])) {
            $selected = array_map(static fn(array $tag): string => (string)$tag['id'], news_tags((int)$row['id']));
        }
        echo '<div class="a-field"><label for="f-news-tags">' . e($f['label']) . '</label>'
           . '<select id="f-news-tags" name="news_tag_ids[]" multiple size="' . max(4, min(8, count($options))) . '">';
        foreach ($options as $value => $label) {
            echo '<option value="' . e((string)$value) . '"' . (in_array((string)$value, $selected, true) ? ' selected' : '') . '>' . e((string)$label) . '</option>';
        }
        echo '</select><label for="f-new-tags">Add new tags (comma-separated)</label>'
           . '<input type="text" id="f-new-tags" name="new_tags" maxlength="1000" value="' . e((string)($row['new_tags'] ?? '')) . '">'
           . '<p class="a-help">Use Ctrl/Cmd to select multiple existing tags. New tags are available to all articles.</p></div>';
        return;
    }

    if (!empty($f['bi'])) {
        echo '<fieldset class="a-bi"><legend>' . e($f['label']) . '</legend>';
        foreach (['en' => 'English', 'bn' => 'বাংলা'] as $code => $langLabel) {
            $col = $name . '_' . $code; $id = 'f-' . $col;
            $val = (string)($row[$col] ?? '');
            echo '<div class="a-field"><label for="' . e($id) . '" id="l-' . e($id) . '">' . e($langLabel) . '</label>';
            if ($type === 'html') {
                echo '<div class="rte" data-rte><div class="rte-bar" role="toolbar" aria-label="Formatting"></div>'
                   . '<div class="rte-area" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="l-' . e($id) . '" hidden></div>'
                   . '<textarea class="rte-src" id="' . e($id) . '" name="' . e($col) . '" rows="10"' . ($code === 'bn' ? ' lang="bn"' : '') . '>' . e($val) . '</textarea></div>';
            } elseif ($type === 'textarea') {
                echo '<textarea id="' . e($id) . '" name="' . e($col) . '" rows="' . (int)($f['rows'] ?? 4) . '"'
                   . (!empty($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '') . ($code === 'bn' ? ' lang="bn"' : '') . '>' . e($val) . '</textarea>';
            } else {
                echo '<input type="text" id="' . e($id) . '" name="' . e($col) . '" value="' . e($val) . '"'
                   . (!empty($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '')
                   . (($f['req'] ?? false) && $code === 'en' ? ' required' : '') . ($code === 'bn' ? ' lang="bn"' : '') . '>';
            }
            echo '</div>';
        }
        echo $help . '</fieldset>';
        return;
    }

    $id = 'f-' . $name;
    $val = $row[$name] ?? ($f['default'] ?? '');
    if ($val === 'today') $val = date('Y-m-d');

    if ($type === 'check') {
        echo '<div class="a-field a-check"><label><input type="checkbox" name="' . e($name) . '" value="1"'
           . (!empty($val) ? ' checked' : '') . '> <span>' . e($f['label']) . '</span></label></div>';
        return;
    }

    echo '<div class="a-field"><label for="' . e($id) . '">' . e($f['label']) . (($f['req'] ?? false) === true ? ' <span class="a-req" aria-hidden="true">*</span>' : '') . '</label>';
    switch ($type) {
        case 'text':
            echo '<input type="text" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$val) . '"'
               . (!empty($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '') . (($f['req'] ?? false) === true ? ' required' : '') . '>';
            break;
        case 'url':
            echo '<input type="url" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$val) . '"'
               . (!empty($f['max']) ? ' maxlength="' . (int)$f['max'] . '"' : '') . ' inputmode="url">';
            break;
        case 'textarea':
            echo '<textarea id="' . e($id) . '" name="' . e($name) . '" rows="' . (int)($f['rows'] ?? 4) . '">' . e((string)$val) . '</textarea>';
            break;
        case 'date':
            echo '<input type="date" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)($val ?? '')) . '"' . (($f['req'] ?? false) === true && empty($f['null']) ? ' required' : '') . '>';
            break;
        case 'int':
            echo '<input type="number" id="' . e($id) . '" name="' . e($name) . '" value="' . e((string)$val) . '" inputmode="numeric">';
            break;
        case 'select':
            echo '<select id="' . e($id) . '" name="' . e($name) . '">';
            foreach (field_options($f) as $k => $label) {
                echo '<option value="' . e((string)$k) . '"' . ((string)$val === (string)$k ? ' selected' : '') . '>' . e((string)$label) . '</option>';
            }
            echo '</select>';
            break;
        case 'image':
            $cur = $row[$name] ?? '';
            if ($cur) {
                echo '<div class="a-preview"><img src="' . e(asset(img_variant($cur, '-480'))) . '" alt="Current image" loading="lazy"></div>';
            }
            echo '<input type="file" id="' . e($id) . '" name="' . e($name) . '" accept="image/jpeg,image/png,image/webp" data-image-input>';
            echo '<div class="a-preview" data-new-preview hidden><img alt="New image preview"></div>';
            echo '<p class="a-help">JPG, PNG or WebP. Large phone photos are resized automatically.</p>';
            if ($cur && ($f['req'] ?? false) !== true) {
                echo '<label class="a-inline-check"><input type="checkbox" name="_remove_' . e($name) . '" value="1"> Remove current image</label>';
            }
            break;
        case 'file':
            $cur = $row[$f['cols']['path']] ?? '';
            if ($cur) {
                echo '<p class="a-current-file">Current: <a href="' . e(asset($cur)) . '" target="_blank" rel="noopener">'
                   . e((string)($row[$f['cols']['name']] ?? basename($cur))) . '</a></p>';
            }
            $extensions = $f['extensions'] ?? ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
            echo '<input type="file" id="' . e($id) . '" name="' . e($name) . '" accept="' . e(implode(',', array_map(static fn(string $ext): string => '.' . $ext, $extensions))) . '"' . (($f['req'] ?? false) === true && !$cur ? ' required' : '') . '>';
            if ($cur && ($f['req'] ?? false) !== true) {
                echo '<label class="a-inline-check"><input type="checkbox" name="_remove_' . e($name) . '" value="1"> Remove current file</label>';
            }
            break;
    }
    if (!empty($f['help'])) echo $help;
    echo '</div>';
}

/* ---------- POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = (string)($_POST['do'] ?? 'save');
    $id = (int)($_POST['id'] ?? 0);

    if (!csrf_check()) { flash('!Your session expired. Please try again.'); redirect($listUrl); }

    if ($do === 'delete') {
        if (!can($user, 'delete')) { flash('!Your role cannot delete items.'); redirect($listUrl); }
        $row = $id ? fetch_row($table, $id) : null;
        if ($row) {
            $relatedImagePaths = $type === 'news'
                ? array_column(news_images($id), 'image_path')
                : [];
            db()->prepare("DELETE FROM `$table` WHERE id = ?")->execute([$id]);
            foreach ($cfg['fields'] as $f) {
                if ($f['type'] === 'image') remove_stored_file($row[$f['name']] ?? null);
                if ($f['type'] === 'file')  remove_stored_file($row[$f['cols']['path']] ?? null);
            }
            foreach ($relatedImagePaths as $path) remove_stored_file((string)$path);
            flash('Deleted.');
        }
        redirect($listUrl);
    }

    if ($do === 'toggle') {
        if ($id) db()->prepare("UPDATE `$table` SET status = 1 - status WHERE id = ?")->execute([$id]);
        flash('Status updated.');
        $back = (string)($_POST['back'] ?? '');
        redirect(($back !== '' && $back[0] === '/' && !str_starts_with($back, '//')) ? $back : $listUrl);
    }

    /* save */
    $existing = $id > 0 ? fetch_row($table, $id) : null;
    if ($id > 0 && !$existing) { flash('!That item no longer exists.'); redirect($listUrl); }
    if ($id === 0 && !can($user, 'create')) { flash('!Your role cannot create new items.'); redirect($listUrl); }

    [$data, $errors, $trash] = collect_post($cfg, $existing);
    $newsImagePaths = [];
    $newsImagesToRemove = [];
    $newsTagIds = [];
    $newTagNames = [];
    $oldNewsSlugs = [];

    if ($type === 'gallery') {
        $mediaType = (string)($data['media_type'] ?? 'photo');
        if (!in_array($mediaType, ['photo', 'video'], true)) {
            $errors[] = 'Choose a supported gallery media type.';
            $mediaType = 'photo';
        }
        $data['media_type'] = $mediaType;
        if ($mediaType === 'video') {
            $youtubeId = youtube_video_id((string)($data['youtube_id'] ?? ''));
            if ($youtubeId === null) {
                $errors[] = 'Enter a valid YouTube URL or 11-character video ID.';
            } else {
                $data['youtube_id'] = $youtubeId;
            }
            if (trim((string)($data['title_en'] ?? '')) === '' && trim((string)($data['title_bn'] ?? '')) === '') {
                $errors[] = 'Enter a title for the video.';
            }
        } else {
            $data['youtube_id'] = null;
            $photoPath = array_key_exists('image', $data) ? $data['image'] : ($existing['image'] ?? null);
            if (empty($photoPath)) {
                $errors[] = 'Upload a photo for photo album items.';
            }
        }
    }

    if ($type === 'news') {
        $language = (string)($data['content_language'] ?? '');
        $titleEn = trim((string)($data['title_en'] ?? ''));
        $titleBn = trim((string)($data['title_bn'] ?? ''));
        if ($id === 0 && !in_array($language, ['en', 'bn'], true)) {
            $errors[] = 'Choose the language the article is written in.';
        }
        if (in_array($language, ['en', 'bn'], true)) {
            if (trim((string)($data['title_' . $language] ?? '')) === '') {
                $errors[] = 'Enter the article title in the selected language.';
            }
        } elseif ($titleEn === '' && $titleBn === '') {
            $errors[] = 'Enter an article title in English or Bangla.';
        }

        foreach ((array)($_POST['news_tag_ids'] ?? []) as $rawTagId) {
            if (!is_scalar($rawTagId) || !ctype_digit((string)$rawTagId) || (int)$rawTagId < 1) {
                $errors[] = 'One or more selected tags are invalid.';
                continue;
            }
            $newsTagIds[] = (int)$rawTagId;
        }
        $newsTagIds = array_values(array_unique($newsTagIds));
        if ($newsTagIds) {
            $placeholders = implode(',', array_fill(0, count($newsTagIds), '?'));
            $checkTags = db()->prepare("SELECT COUNT(*) FROM tags WHERE id IN ($placeholders)");
            $checkTags->execute($newsTagIds);
            if ((int)$checkTags->fetchColumn() !== count($newsTagIds)) {
                $errors[] = 'One or more selected tags no longer exist.';
            }
        }

        $rawNewTags = trim((string)($_POST['new_tags'] ?? ''));
        if ($rawNewTags !== '' && !can($user, 'create')) {
            $errors[] = 'Your role cannot create tags.';
        } elseif ($rawNewTags !== '') {
            foreach (explode(',', $rawNewTags) as $tagName) {
                $tagName = trim(preg_replace('/\s+/u', ' ', $tagName) ?? $tagName);
                if ($tagName === '') continue;
                if (mb_strlen($tagName) > 100 || slugify($tagName) === '') {
                    $errors[] = 'Each new tag must contain letters or numbers and be no longer than 100 characters.';
                    continue;
                }
                $newTagNames[] = $tagName;
            }
            if (count($newTagNames) > 20) $errors[] = 'Add no more than 20 new tags at a time.';
            $newTagNames = array_values(array_unique($newTagNames));
        }

        $currentImages = $id > 0 ? news_images($id) : [];
        $knownImageIds = array_fill_keys(array_map(static fn(array $image): int => (int)$image['id'], $currentImages), true);
        foreach ((array)($_POST['news_image_remove'] ?? []) as $rawImageId) {
            if (!is_scalar($rawImageId) || !ctype_digit((string)$rawImageId) || !isset($knownImageIds[(int)$rawImageId])) {
                $errors[] = 'One or more selected article images are invalid.';
                continue;
            }
            $newsImagesToRemove[] = (int)$rawImageId;
        }
        $newsImagesToRemove = array_values(array_unique($newsImagesToRemove));

        if (!empty($_FILES['news_images']['name'])) {
            $uploadResult = upload_multiple_images('news_images');
            $newsImagePaths = $uploadResult['paths'];
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }

    if (!empty($cfg['slug'])) {
        $titleEn = (string)($data['title_en'] ?? $data['name_en'] ?? '');
        $titleBn = (string)($data['title_bn'] ?? '');
        $postedEn = slugify((string)($_POST['slug_en'] ?? ''));
        $postedBn = slugify((string)($_POST['slug_bn'] ?? ''));
        $slugEn = $postedEn !== '' ? $postedEn : ($existing['slug_en'] ?? '');
        if ($slugEn === '') $slugEn = slugify($titleEn) !== '' ? slugify($titleEn) : slugify($titleBn);
        $others = $table === 'news' ? ['pages'] : ($table === 'pages' ? ['news'] : []);
        $slugEn = unique_slug($table, $slugEn, $id, $others);
        $slugBn = $postedBn !== '' ? unique_slug($table, $postedBn, $id, $others) : ($existing['slug_bn'] ?? '');
        if ($slugBn === '' || $slugBn === null) $slugBn = $slugEn;
        $data['slug_en'] = $slugEn;
        $data['slug_bn'] = $slugBn;
        if ($table === 'news' && $existing) {
            foreach (['slug_en' => $slugEn, 'slug_bn' => $slugBn] as $column => $newSlug) {
                $oldSlug = (string)($existing[$column] ?? '');
                if ($oldSlug !== '' && $oldSlug !== $newSlug && $oldSlug !== $slugEn && $oldSlug !== $slugBn) {
                    $oldNewsSlugs[] = $oldSlug;
                }
            }
        }
    }

    if ($errors) {
        cleanup_new_uploads($cfg, $data, $existing);
        foreach ($newsImagePaths as $path) remove_stored_file($path);
        // keep what was typed so nothing is lost on a phone
        $_SESSION['form_old'][$type . ':' . $id] = $_POST;
        $_SESSION['form_errors'] = $errors;
        flash('!Please fix the problems below.');
        redirect($self . '?type=' . urlencode($type) . '&action=' . ($id ? 'edit&id=' . $id : 'new'));
    }

    try {
        $pdo = db();
        if ($type === 'news') $pdo->beginTransaction();
        if ($id > 0) {
            $set = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
            $pdo->prepare("UPDATE `$table` SET $set WHERE id = ?")->execute([...array_values($data), $id]);
            flash('Saved.');
        } else {
            $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
            $ph = implode(', ', array_fill(0, count($data), '?'));
            $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($ph)")->execute(array_values($data));
            $id = (int)$pdo->lastInsertId();
            flash('Created.');
        }
        if ($type === 'news') {
            $saveRedirect = $pdo->prepare('INSERT INTO news_slug_redirects (old_slug, news_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE news_id = VALUES(news_id)');
            foreach (array_unique($oldNewsSlugs) as $oldSlug) $saveRedirect->execute([$oldSlug, $id]);

            $tagIds = $newsTagIds;
            foreach ($newTagNames as $tagName) {
                $base = mb_substr(slugify($tagName), 0, 120);
                $candidate = $base;
                $suffix = 2;
                while (true) {
                    $find = $pdo->prepare('SELECT id FROM tags WHERE name = ? OR slug = ? LIMIT 1');
                    $find->execute([$tagName, $candidate]);
                    $found = $find->fetch();
                    if ($found && strcasecmp((string)$found['name'], $tagName) === 0) {
                        $tagIds[] = (int)$found['id'];
                        break;
                    }
                    if (!$found) {
                        $pdo->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)')->execute([$tagName, $candidate]);
                        $tagIds[] = (int)$pdo->lastInsertId();
                        break;
                    }
                    $tail = '-' . $suffix++;
                    $candidate = mb_substr($base, 0, 120 - strlen($tail)) . $tail;
                }
            }
            $tagIds = array_values(array_unique($tagIds));
            $pdo->prepare('DELETE FROM news_tags WHERE news_id = ?')->execute([$id]);
            $linkTag = $pdo->prepare('INSERT INTO news_tags (news_id, tag_id) VALUES (?, ?)');
            foreach ($tagIds as $tagId) $linkTag->execute([$id, $tagId]);

            $removeLookup = array_fill_keys($newsImagesToRemove, true);
            $deleteImage = $pdo->prepare('DELETE FROM news_images WHERE id = ? AND news_id = ?');
            $updateImage = $pdo->prepare('UPDATE news_images SET sort_order = ? WHERE id = ? AND news_id = ?');
            $nextImageOrder = 0;
            foreach ($currentImages as $image) {
                $imageId = (int)$image['id'];
                if (isset($removeLookup[$imageId])) {
                    $deleteImage->execute([$imageId, $id]);
                    $trash[] = (string)$image['image_path'];
                    continue;
                }
                $rawOrder = $_POST['news_image_order'][$imageId] ?? $image['sort_order'];
                $sortOrder = is_scalar($rawOrder) && ctype_digit((string)$rawOrder)
                    ? min((int)$rawOrder, 1000000)
                    : (int)$image['sort_order'];
                $updateImage->execute([$sortOrder, $imageId, $id]);
                $nextImageOrder = max($nextImageOrder, $sortOrder + 1);
            }
            $addImage = $pdo->prepare('INSERT INTO news_images (news_id, image_path, sort_order) VALUES (?, ?, ?)');
            foreach ($newsImagePaths as $path) $addImage->execute([$id, $path, $nextImageOrder++]);
            $pdo->commit();
        }
        foreach ($trash as $p) remove_stored_file($p);
        unset($_SESSION['form_old'][$type . ':' . ($_POST['id'] ?? 0)]);
    } catch (Throwable $ex) {
        if ($type === 'news' && isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        cleanup_new_uploads($cfg, $data, $existing);
        foreach ($newsImagePaths as $path) remove_stored_file($path);
        error_log('Admin save failed: ' . $ex->getMessage());
        flash('!Could not save. ' . (DEBUG ? $ex->getMessage() : 'Check the entries and try again; if the problem continues, ask the site developer to check the error log.'));
        redirect($self . '?type=' . urlencode($type) . '&action=' . ((int)($_POST['id'] ?? 0) ? 'edit&id=' . (int)$_POST['id'] : 'new'));
    }
    redirect($self . '?type=' . urlencode($type) . '&action=edit&id=' . $id);
}

/* ---------- Edit / new form ---------- */
$action = (string)($_GET['action'] ?? 'list');
if ($action === 'new' || $action === 'edit') {
    $id = $action === 'edit' ? (int)($_GET['id'] ?? 0) : 0;
    $row = $id ? fetch_row($table, $id) : null;
    if ($action === 'edit' && !$row) { flash('!That item no longer exists.'); redirect($listUrl); }
    if ($action === 'new' && !can($user, 'create')) { flash('!Your role cannot create new items.'); redirect($listUrl); }

    $old = $_SESSION['form_old'][$type . ':' . $id] ?? null;
    unset($_SESSION['form_old'][$type . ':' . $id]);
    $errors = $_SESSION['form_errors'] ?? []; unset($_SESSION['form_errors']);
    if ($old) { $row = array_merge($row ?? [], array_filter($old, fn($k) => $k !== '_csrf', ARRAY_FILTER_USE_KEY)); }

    admin_header(($id ? 'Edit ' : 'New ') . $cfg['singular'], $user, $type);
    ?>
    <div class="a-pagehead">
      <h1><?= $id ? 'Edit' : 'New' ?> <?= e($cfg['singular']) ?></h1>
      <a class="a-btn a-btn-sec" href="<?= e($listUrl) ?>">← Back to list</a>
    </div>
    <?php if ($errors): ?>
      <div class="a-msg a-err" role="alert"><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="a-card a-form" data-dirty-guard>
      <?= csrf_field() ?>
      <input type="hidden" name="type" value="<?= e($type) ?>">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="do" value="save">
      <?php foreach ($cfg['fields'] as $i => $f) { render_field($f, $row, $i); } ?>

      <?php if (!empty($cfg['slug'])): ?>
        <details class="a-adv">
          <summary>Web address (optional)</summary>
          <p class="a-help">Created automatically from the title. Change it only if you need a specific address.</p>
          <div class="a-field"><label for="f-slug_en">Address (English)</label>
            <input type="text" id="f-slug_en" name="slug_en" value="<?= e((string)($row['slug_en'] ?? '')) ?>" maxlength="190"></div>
          <div class="a-field"><label for="f-slug_bn">Address (বাংলা)</label>
            <input type="text" id="f-slug_bn" name="slug_bn" value="<?= e((string)($row['slug_bn'] ?? '')) ?>" maxlength="190"></div>
        </details>
      <?php endif; ?>

      <div class="a-savebar">
        <button type="submit" class="a-btn a-btn-primary">Save</button>
        <?php
          $pub = (string)($cfg['public'] ?? '');
          $slugNow = (string)($row['slug_en'] ?? '');
          if ($id && $pub !== '' && (!str_contains($pub, '{slug}') || $slugNow !== '')): ?>
          <a class="a-btn a-btn-sec" target="_blank" rel="noopener"
             href="<?= e(BASE_PATH . '/en/' . str_replace('{slug}', rawurlencode($slugNow), $pub)) ?>">View on site</a>
        <?php endif; ?>
      </div>
    </form>
    <?php
    admin_footer();
    exit;
}

/* ---------- List ---------- */
$q = trim((string)($_GET['q'] ?? ''));
$st = (string)($_GET['status'] ?? '');
$where = []; $params = [];
if ($q !== '' && !empty($cfg['search'])) {
    $likes = [];
    foreach ($cfg['search'] as $col) { $likes[] = "`$col` LIKE ?"; $params[] = '%' . $q . '%'; }
    $where[] = '(' . implode(' OR ', $likes) . ')';
}
if ($st === '1' || $st === '0') { $where[] = 'status = ?'; $params[] = (int)$st; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$cnt = db()->prepare("SELECT COUNT(*) FROM `$table`$whereSql");
$cnt->execute($params);
$pg = paginate((int)$cnt->fetchColumn(), 20, (int)($_GET['page'] ?? 1));

$sel = db()->prepare("SELECT * FROM `$table`$whereSql ORDER BY {$cfg['order']} LIMIT " . (int)$pg['per'] . ' OFFSET ' . (int)$pg['offset']);
$sel->execute($params);
$rows = $sel->fetchAll();

admin_header($cfg['title'], $user, $type);
?>
<div class="a-pagehead">
  <h1><?= e($cfg['title']) ?></h1>
  <?php if (can($user, 'create')): ?>
    <a class="a-btn a-btn-primary" href="<?= e($self) ?>?type=<?= e($type) ?>&amp;action=new">+ New <?= e($cfg['singular']) ?></a>
  <?php endif; ?>
</div>
<?php if (!empty($cfg['note'])): ?><p class="a-help"><?= e($cfg['note']) ?></p><?php endif; ?>

<form method="get" class="a-filter" role="search">
  <input type="hidden" name="type" value="<?= e($type) ?>">
  <label class="a-sr" for="q">Search</label>
  <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Search…">
  <label class="a-sr" for="status">Status</label>
  <select id="status" name="status">
    <option value="">All</option>
    <option value="1"<?= $st === '1' ? ' selected' : '' ?>>Published</option>
    <option value="0"<?= $st === '0' ? ' selected' : '' ?>>Drafts</option>
  </select>
  <button class="a-btn a-btn-sec" type="submit">Search</button>
</form>

<?php if (!$rows): ?>
  <p class="a-empty">Nothing here yet.<?= can($user, 'create') ? ' Use “New ' . e($cfg['singular']) . '” to add the first one.' : '' ?></p>
<?php else: ?>
<table class="a-table">
  <thead><tr>
    <?php foreach ($cfg['list'] as $c): ?><th scope="col"><?= e($c[1]) ?></th><?php endforeach; ?>
    <th scope="col">Actions</th>
  </tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <?php foreach ($cfg['list'] as $c):
        $key = $c[0]; $kind = $c[2] ?? 'text'; $v = $r[$key] ?? ($r[$key . '_en'] ?? '');
        if ($v === '' || $v === null) $v = $r[str_replace('_en', '_bn', $key)] ?? '';
      ?>
        <td data-label="<?= e($c[1]) ?>">
          <?php if ($kind === 'status'): ?>
            <span class="a-pill <?= !empty($v) ? 'a-pill-ok' : 'a-pill-draft' ?>"><?= !empty($v) ? 'Published' : 'Draft' ?></span>
          <?php elseif ($kind === 'bool'): ?><?= !empty($v) ? 'Yes' : '—' ?>
          <?php elseif ($kind === 'image'): ?>
            <?php if ($v): ?><img class="a-thumb" src="<?= e(asset(img_variant((string)$v, '-480'))) ?>" alt="" loading="lazy"><?php endif; ?>
          <?php elseif ($kind === 'category'): ?><?= e(document_category_label((string)$v)) ?>
          <?php else: ?><?= e(mb_strimwidth((string)$v, 0, 70, '…')) ?>
          <?php endif; ?>
        </td>
      <?php endforeach; ?>
      <td data-label="Actions" class="a-row-actions">
        <a class="a-btn a-btn-sm" href="<?= e($self) ?>?type=<?= e($type) ?>&amp;action=edit&amp;id=<?= (int)$r['id'] ?>">Edit</a>
        <?php if (array_key_exists('status', $r)):
          echo admin_post_button($self, ['type' => $type, 'do' => 'toggle', 'id' => $r['id'], 'back' => $_SERVER['REQUEST_URI'] ?? $listUrl],
                                 !empty($r['status']) ? 'Unpublish' : 'Publish'); endif; ?>
        <?php if (can($user, 'delete')):
          echo admin_post_button($self, ['type' => $type, 'do' => 'delete', 'id' => $r['id']], 'Delete',
                                 'Delete this ' . $cfg['singular'] . '? This cannot be undone.', 'a-btn a-btn-sm a-btn-danger-o'); endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php admin_pager($pg, ['type' => $type, 'q' => $q, 'status' => $st]); endif; ?>
<?php admin_footer(); ?>
