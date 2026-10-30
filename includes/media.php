<?php
declare(strict_types=1);

/* ==========================================================================
   Text, HTML and file helpers shared by the public site and the admin panel.
   ========================================================================== */

/* ---------- Slugs ---------- */
function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = preg_replace('/[^\p{L}\p{M}\p{N}]+/u', '-', $s) ?? '';
    return trim($s, '-');
}

/** Words that are already site addresses; an article can never take them. */
function reserved_slugs(): array
{
    return ['en','bn','news','all-news','events','notices','projects','documents','gallery','heart-health','publications','team','about',
            'mission','donate','join','contact','admin','assets','public','index','config','includes','views',
            'lang','sql','storage','sw','manifest','offline'];
}

/** Unique across slug_en/slug_bn of $table (ignoring row $ignoreId) and, optionally, of $others. */
function unique_slug(string $table, string $base, int $ignoreId = 0, array $others = []): string
{
    $base = $base !== '' ? $base : 'item-' . date('Ymd-His');
    $slug = $base;
    $n = 2;
    while (true) {
        $taken = in_array($slug, reserved_slugs(), true);
        if (!$taken) {
            $st = db()->prepare("SELECT COUNT(*) FROM `$table` WHERE (slug_en = ? OR slug_bn = ?) AND id <> ?");
            $st->execute([$slug, $slug, $ignoreId]);
            $taken = (int)$st->fetchColumn() > 0;
        }
        foreach ($others as $o) {
            if ($taken) break;
            $st = db()->prepare("SELECT COUNT(*) FROM `$o` WHERE slug_en = ? OR slug_bn = ?");
            $st->execute([$slug, $slug]);
            $taken = (int)$st->fetchColumn() > 0;
        }
        if (!$taken && in_array($table, ['news', 'pages'], true)) {
            $st = db()->prepare('SELECT COUNT(*) FROM news_slug_redirects WHERE old_slug = ?');
            $st->execute([$slug]);
            $taken = (int)$st->fetchColumn() > 0;
        }
        if (!$taken) return $slug;
        $slug = $base . '-' . $n++;
    }
}

/* ---------- HTML sanitiser (allow-list) ---------- */
function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') return '';

    if (!class_exists('DOMDocument')) {
        // Without ext-dom: keep text only, never raw markup.
        return '<p>' . nl2br(e(strip_tags($html))) . '</p>';
    }

    $allowed = ['p','br','strong','b','em','i','u','h2','h3','h4','ul','ol','li','a',
                'blockquote','hr','table','thead','tbody','tr','th','td'];

    $prev = libxml_use_internal_errors(true);
    $doc = new DOMDocument('1.0', 'UTF-8');
    $doc->loadHTML('<?xml encoding="UTF-8"><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementsByTagName('div')->item(0);
    if (!$root) return '';
    _san_children($root, $allowed);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return trim($out);
}

function _san_children(DOMNode $node, array $allowed): void
{
    $drop = ['script','style','iframe','object','embed','svg','math','form','input','button',
             'textarea','select','link','meta','base','noscript','template','frame','frameset'];

    for ($i = $node->childNodes->length - 1; $i >= 0; $i--) {
        $c = $node->childNodes->item($i);
        if ($c === null) continue;

        if ($c->nodeType === XML_TEXT_NODE) continue;
        if ($c->nodeType !== XML_ELEMENT_NODE) { $node->removeChild($c); continue; }

        $tag = strtolower($c->nodeName);
        if (in_array($tag, $drop, true)) { $node->removeChild($c); continue; }

        _san_children($c, $allowed);

        if (!in_array($tag, $allowed, true)) {           // unwrap unknown tags, keep their text
            while ($c->firstChild) { $node->insertBefore($c->firstChild, $c); }
            $node->removeChild($c);
            continue;
        }

        /** @var DOMElement $c */
        for ($j = $c->attributes->length - 1; $j >= 0; $j--) {
            $attr = $c->attributes->item($j);
            if ($attr === null) continue;
            $keep = false;
            if ($tag === 'a' && strtolower($attr->nodeName) === 'href') {
                $href = trim(preg_replace('/[\x00-\x20]+/', '', $attr->nodeValue ?? '') ?? '');
                $keep = (bool)preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href);
            }
            if (!$keep) $c->removeAttribute($attr->nodeName);
        }
        if ($tag === 'a' && $c->hasAttribute('href')) {
            if (preg_match('~^https?://~i', $c->getAttribute('href'))) {
                $c->setAttribute('target', '_blank');
            }
            $c->setAttribute('rel', 'noopener noreferrer');
        }
    }
}

/* ---------- Pagination ---------- */
function paginate(int $total, int $perPage, int $page): array
{
    $pages = max(1, (int)ceil($total / max(1, $perPage)));
    $page  = min(max(1, $page), $pages);
    return ['total' => $total, 'per' => $perPage, 'page' => $page, 'pages' => $pages,
            'offset' => ($page - 1) * $perPage];
}

function human_size(int $bytes): string
{
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
    if ($bytes >= 1024)    return number_format($bytes / 1024, 0) . ' KB';
    return $bytes . ' B';
}

/* ---------- Upload plumbing ---------- */
function upload_error_text(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is larger than the server allows (check upload_max_filesize / post_max_size in php.ini).',
        UPLOAD_ERR_PARTIAL   => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not store the file.',
        default => 'The file could not be uploaded.',
    };
}

/** Last upload problem, readable by callers that want to show it. */
function upload_last_error(?string $set = null): string
{
    static $msg = '';
    if ($set !== null) $msg = $set;
    return $msg;
}

/**
 * Store an uploaded image. Resizes (max 1600px) and creates 480/960px variants
 * when GD is available. Returns a path relative to the site root, or null.
 */
function upload_image(string $field): ?string
{
    upload_last_error('');
    if (empty($_FILES[$field]['name'])) return null;
    return store_uploaded_image($_FILES[$field]);
}

/**
 * Store all files from a multiple-image input. Every image is validated
 * independently; if one fails, files already stored by this call are removed.
 *
 * @return array{paths: list<string>, errors: list<string>}
 */
function upload_multiple_images(string $field): array
{
    $paths = [];
    $errors = [];
    $files = $_FILES[$field] ?? null;
    if (!is_array($files) || !is_array($files['name'] ?? null)) {
        return ['paths' => [], 'errors' => []];
    }

    foreach ($files['name'] as $i => $name) {
        if ((int)($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        $file = [
            'name' => $name,
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
        $path = store_uploaded_image($file);
        if ($path === null) {
            $errors[] = 'Image ' . ((int)$i + 1) . ': ' . (upload_last_error() ?: 'upload failed.');
            continue;
        }
        $paths[] = $path;
    }

    if ($errors) {
        foreach ($paths as $path) delete_upload($path);
        $paths = [];
    }
    return ['paths' => $paths, 'errors' => $errors];
}

/** Store one normalized upload record after validating its actual image bytes. */
function store_uploaded_image(array $f): ?string
{
    upload_last_error('');
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        upload_last_error(upload_error_text((int)($f['error'] ?? UPLOAD_ERR_NO_FILE)));
        return null;
    }
    if (!isset($f['tmp_name'], $f['size']) || !is_string($f['tmp_name']) || (int)$f['size'] > MAX_IMAGE_BYTES) {
        upload_last_error('Image is larger than ' . human_size(MAX_IMAGE_BYTES) . ' or could not be read.');
        return null;
    }
    if (!is_uploaded_file($f['tmp_name'])) {
        upload_last_error('The uploaded image could not be verified.');
        return null;
    }
    $info = @getimagesize($f['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp'];
    if (!$info || !isset($types[$info[2]])) {
        upload_last_error('Only JPG, PNG or WebP images are accepted.');
        return null;
    }
    if (!is_dir(UPLOAD_PATH) && !@mkdir(UPLOAD_PATH, 0775, true) && !is_dir(UPLOAD_PATH)) {
        upload_last_error('The uploads folder could not be created. Create assets/uploads and give the web server write permission.');
        return null;
    }
    if (!is_writable(UPLOAD_PATH)) {
        upload_last_error('The web server cannot write to assets/uploads. Give the PHP/Apache user write permission to that folder.');
        return null;
    }

    $base = bin2hex(random_bytes(8));
    $gd = function_exists('imagecreatetruecolor');

    if ($gd) {
        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($f['tmp_name']),
            IMAGETYPE_PNG  => @imagecreatefrompng($f['tmp_name']),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false,
            default => false,
        };
        if ($src) {
            if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
                $exif = @exif_read_data($f['tmp_name']);
                $rot = match ((int)($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
                if ($rot !== 0) { $r = imagerotate($src, $rot, 0); if ($r) $src = $r; }
            }
            $useWebp = function_exists('imagewebp');
            $ext = $useWebp ? 'webp' : ($info[2] === IMAGETYPE_PNG ? 'png' : 'jpg');
            $ok = true;
            foreach ([1600 => '', 960 => '-960', 480 => '-480'] as $w => $suffix) {
                $ok = $ok && _save_scaled($src, $w, UPLOAD_PATH . '/' . $base . $suffix . '.' . $ext, $ext);
            }
            if ($ok) return 'assets/uploads/' . $base . '.' . $ext;
            foreach (['', '-960', '-480'] as $s) @unlink(UPLOAD_PATH . '/' . $base . $s . '.' . $ext);
        }
    }

    // Fallback: keep the original file as-is (GD missing or failed)
    $ext = $types[$info[2]];
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_PATH . '/' . $base . '.' . $ext)) {
        upload_last_error('The image could not be saved. Check that the web server can write to assets/uploads and that the server has free disk space.');
        return null;
    }
    return 'assets/uploads/' . $base . '.' . $ext;
}

function _save_scaled($src, int $maxW, string $dest, string $ext): bool
{
    $w = imagesx($src); $h = imagesy($src);
    $nw = min($w, $maxW);
    $nh = (int)round($h * ($nw / max(1, $w)));
    $dst = imagecreatetruecolor($nw, $nh);
    if ($ext !== 'jpg') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    } else {
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $res = match ($ext) {
        'webp' => imagewebp($dst, $dest, 82),
        'png'  => imagepng($dst, $dest, 7),
        default => imagejpeg($dst, $dest, 84),
    };
    imagedestroy($dst);
    return (bool)$res;
}

/** Delete an uploaded image and its -960 / -480 variants (only inside assets/uploads). */
function delete_upload(?string $path): void
{
    if (!$path || !str_starts_with($path, 'assets/uploads/') || str_contains($path, '..')) return;
    $full = APP_ROOT . '/' . $path;
    @unlink($full);
    $dot = strrpos($full, '.');
    if ($dot !== false) {
        foreach (['-960', '-480'] as $s) @unlink(substr($full, 0, $dot) . $s . substr($full, $dot));
    }
}

/** Allowed document types: extension => mime types accepted. */
function document_types(): array
{
    return [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls'  => ['application/vnd.ms-excel', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
    ];
}

/** Store an uploaded document. Returns [path, original name, size, ext] or null. */
function upload_document(string $field, array $allowedExtensions = []): ?array
{
    upload_last_error('');
    if (empty($_FILES[$field]['name'])) return null;
    $f = $_FILES[$field];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        upload_last_error(upload_error_text((int)$f['error']));
        return null;
    }
    if ($f['size'] > MAX_DOC_BYTES) {
        upload_last_error('Document is larger than ' . human_size(MAX_DOC_BYTES) . '.');
        return null;
    }
    $ext = strtolower(pathinfo((string)$f['name'], PATHINFO_EXTENSION));
    $types = document_types();
    if (!isset($types[$ext]) || ($allowedExtensions && !in_array($ext, $allowedExtensions, true))) {
        upload_last_error($allowedExtensions
            ? 'Allowed document type: ' . strtoupper(implode(', ', $allowedExtensions)) . '.'
            : 'Allowed document types: PDF, DOC, DOCX, XLS, XLSX.');
        return null;
    }
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $fi ? (string)finfo_file($fi, $f['tmp_name']) : '';
        if ($fi) finfo_close($fi);
        if ($mime !== '' && !in_array($mime, $types[$ext], true)) {
            upload_last_error('The file content does not match its extension.');
            return null;
        }
    }
    $dir = UPLOAD_PATH . '/docs';
    if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
        upload_last_error('The documents folder is not writable.');
        return null;
    }
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
        upload_last_error('The document could not be saved.');
        return null;
    }
    $orig = preg_replace('/[^\p{L}\p{M}\p{N}\s._()-]+/u', '', basename((string)$f['name'])) ?: ('document.' . $ext);
    return ['assets/uploads/docs/' . $name, mb_substr($orig, 0, 190), (int)$f['size'], $ext];
}

/* ---------- Responsive <img> ---------- */
/** Path of a size variant if it exists on disk, else the original. */
function img_variant(string $path, string $suffix): string
{
    $dot = strrpos($path, '.');
    if ($dot === false) return $path;
    $v = substr($path, 0, $dot) . $suffix . substr($path, $dot);
    return is_file(APP_ROOT . '/' . $v) ? $v : $path;
}

function img_srcset(string $path): string
{
    $parts = [];
    foreach ([['-480', '480w'], ['-960', '960w'], ['', '1600w']] as [$suffix, $w]) {
        $v = img_variant($path, $suffix);
        if ($suffix === '' || $v !== $path) $parts[] = asset($v) . ' ' . $w;
    }
    return count($parts) > 1 ? implode(', ', $parts) : '';
}

/** Full <img> tag with srcset when variants exist. */
function img_tag(string $path, string $alt, string $sizes = '100vw', string $class = '', bool $eager = false): string
{
    $srcset = img_srcset($path);
    return '<img src="' . e(asset($path)) . '"'
        . ($srcset !== '' ? ' srcset="' . e($srcset) . '" sizes="' . e($sizes) . '"' : '')
        . ' alt="' . e($alt) . '"'
        . ($class !== '' ? ' class="' . e($class) . '"' : '')
        . ($eager ? '' : ' loading="lazy" decoding="async"') . '>';
}
