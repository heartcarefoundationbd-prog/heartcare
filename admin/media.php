<?php
declare(strict_types=1);

require_once __DIR__ . '/includes.php';
require_once APP_ROOT . '/includes/media-library.php';

$user = require_admin('content');
$self = admin_url('media.php');

$formatItem = static function (array $item): array {
    $path = (string)$item['path'];
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    return [
        'id' => (int)$item['id'],
        'name' => (string)$item['original_name'],
        'path' => $path,
        'url' => asset($path),
        'mime' => (string)$item['mime_type'],
        'kind' => (string)$item['media_kind'],
        'size' => (int)$item['file_size'],
        'extension' => $extension,
        'created_at' => (string)($item['created_at'] ?? ''),
    ];
};

if (($_GET['ajax'] ?? '') === 'list') {
    media_library_sync_existing_uploads();
    $query = trim((string)($_GET['q'] ?? ''));
    $kind = (string)($_GET['kind'] ?? '');
    $extensions = (string)($_GET['extensions'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 24;
    $items = array_map($formatItem, media_library_list($query, $kind, $limit, ($page - 1) * $limit, $extensions));
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'items' => $items,
        'total' => media_library_count($query, $kind, $extensions),
        'page' => $page,
        'per_page' => $limit,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['ajax'] ?? '') === 'upload') {
    header('Content-Type: application/json; charset=utf-8');
    if (!csrf_check()) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Your session expired. Reload the page and try again.']);
        exit;
    }

    $adminId = isset($user['id']) ? (int)$user['id'] : null;
    $uploaded = [];
    $errors = [];
    $incoming = $_FILES['files'] ?? null;
    if (!is_array($incoming) || !isset($incoming['name']) || !is_array($incoming['name'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Choose at least one file to upload.']);
        exit;
    }
    foreach ($incoming['name'] as $index => $name) {
        $file = [
            'name' => $name,
            'type' => $incoming['type'][$index] ?? '',
            'tmp_name' => $incoming['tmp_name'][$index] ?? '',
            'error' => $incoming['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $incoming['size'][$index] ?? 0,
        ];
        $result = media_library_upload($file, $adminId);
        if ($result['ok']) $uploaded[] = $result['item'];
        else $errors[] = basename((string)$name) . ': ' . $result['error'];
    }
    if (!$uploaded) http_response_code(422);
    echo json_encode(['ok' => (bool)$uploaded, 'uploaded' => $uploaded, 'errors' => $errors], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf($self);
    $adminId = isset($user['id']) ? (int)$user['id'] : null;
    $uploadedCount = 0;
    $errors = [];
    $incoming = $_FILES['files'] ?? null;
    if (is_array($incoming) && isset($incoming['name']) && is_array($incoming['name'])) {
        foreach ($incoming['name'] as $index => $name) {
            $result = media_library_upload([
                'name' => $name,
                'type' => $incoming['type'][$index] ?? '',
                'tmp_name' => $incoming['tmp_name'][$index] ?? '',
                'error' => $incoming['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $incoming['size'][$index] ?? 0,
            ], $adminId);
            if ($result['ok']) $uploadedCount++;
            else $errors[] = basename((string)$name) . ': ' . $result['error'];
        }
    }
    flash(($uploadedCount ? $uploadedCount . ' file(s) uploaded. ' : '') . implode(' ', $errors));
    redirect($self);
}

media_library_sync_existing_uploads();
$query = trim((string)($_GET['q'] ?? ''));
$kind = (string)($_GET['kind'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 24;
$total = media_library_count($query, $kind);
$pages = max(1, (int)ceil($total / $limit));
$page = min($page, $pages);
$items = array_map($formatItem, media_library_list($query, $kind, $limit, ($page - 1) * $limit));
$message = flash();

admin_header('Media Library', $user, 'media');
?>
<section class="a-pagehead">
  <div>
    <h1>Media Library</h1>
    <p>Upload files once, then reuse them across supported content and people forms.</p>
  </div>
</section>

<?php if ($message): ?><div class="a-notice"><?= e($message) ?></div><?php endif; ?>

<section class="ml-upload-card">
  <div>
    <h2>Upload files</h2>
    <p>Images, documents, archives, audio and video are supported. Executable files and active web content are blocked. Maximum size: 25 MB per file, subject to the server upload limit.</p>
  </div>
  <form method="post" enctype="multipart/form-data" class="ml-upload-form">
    <?= csrf_field() ?>
    <input type="hidden" name="ajax" value="">
    <label class="ml-file-picker">
      <span>Select files from this device</span>
      <input type="file" name="files[]" multiple required>
    </label>
    <button class="a-btn a-btn-primary" type="submit">Upload to library</button>
  </form>
</section>

<form class="ml-toolbar" method="get" action="<?= e($self) ?>">
  <label>
    <span class="hp">Search media</span>
    <input type="search" name="q" value="<?= e($query) ?>" placeholder="Search by file name">
  </label>
  <label>
    <span class="hp">Filter media type</span>
    <select name="kind">
      <option value="">All file types</option>
      <?php foreach (['image' => 'Images', 'document' => 'Documents', 'archive' => 'Archives', 'audio' => 'Audio', 'video' => 'Video'] as $value => $label): ?>
        <option value="<?= e($value) ?>" <?= $kind === $value ? 'selected' : '' ?>><?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>
  <button class="a-btn" type="submit">Filter</button>
  <span class="ml-result-count"><?= number_format($total) ?> file(s)</span>
</form>

<?php if ($items): ?>
  <div class="ml-grid">
    <?php foreach ($items as $item): ?>
      <article class="ml-card">
        <div class="ml-preview">
          <?php if ($item['kind'] === 'image'): ?>
            <img src="<?= e($item['url']) ?>" alt="" loading="lazy">
          <?php else: ?>
            <span class="ml-file-badge"><?= e(strtoupper($item['extension'])) ?></span>
          <?php endif; ?>
        </div>
        <div class="ml-card-details">
          <strong title="<?= e($item['name']) ?>"><?= e($item['name']) ?></strong>
          <span><?= e(strtoupper($item['kind'])) ?> · <?= e(number_format($item['size'] / 1048576, 2)) ?> MB</span>
          <a href="<?= e($item['url']) ?>" target="_blank" rel="noopener noreferrer">Open file</a>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <p class="a-empty">No media found. Upload a file or change the search filters.</p>
<?php endif; ?>

<?php if ($pages > 1): ?>
  <nav class="ml-pagination" aria-label="Media library pages">
    <?php if ($page > 1): ?><a href="<?= e($self . '?' . http_build_query(['q' => $query, 'kind' => $kind, 'page' => $page - 1])) ?>">Previous</a><?php endif; ?>
    <span>Page <?= e((string)$page) ?> of <?= e((string)$pages) ?></span>
    <?php if ($page < $pages): ?><a href="<?= e($self . '?' . http_build_query(['q' => $query, 'kind' => $kind, 'page' => $page + 1])) ?>">Next</a><?php endif; ?>
  </nav>
<?php endif; ?>

<style>
.ml-upload-card{display:flex;align-items:center;justify-content:space-between;gap:24px;margin:0 0 24px;padding:24px;border:1px solid var(--a-border,#e5e7eb);border-radius:16px;background:#fff}
.ml-upload-card h2{margin:0 0 5px;font-size:20px}.ml-upload-card p{max-width:680px;margin:0;color:#606875;font-size:14px}
.ml-upload-form{display:flex;align-items:center;flex-wrap:wrap;gap:10px}.ml-file-picker{display:inline-flex;min-height:42px;align-items:center;padding:0 14px;border:1px solid #d5d9df;border-radius:8px;cursor:pointer;font-size:13px;font-weight:700}.ml-file-picker input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0)}
.ml-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:18px}.ml-toolbar label:first-child{flex:1}.ml-toolbar input,.ml-toolbar select{width:100%;min-height:42px;padding:8px 12px;border:1px solid #d5d9df;border-radius:8px;background:#fff;font:inherit}.ml-result-count{margin-left:auto;color:#68707b;font-size:13px}
.ml-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}.ml-card{min-width:0;overflow:hidden;border:1px solid #e3e6e9;border-radius:12px;background:#fff}.ml-preview{display:grid;height:170px;place-items:center;overflow:hidden;background:#f4f6f7}.ml-preview img{width:100%;height:100%;object-fit:contain}.ml-file-badge{display:grid;width:68px;height:78px;place-items:center;border:1px solid #d9dee4;border-radius:8px;background:#fff;color:#374151;font-size:14px;font-weight:800}.ml-card-details{display:grid;gap:4px;padding:12px}.ml-card-details strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}.ml-card-details span,.ml-card-details a{font-size:12px;color:#68707b}.ml-card-details a{color:#b01e31;font-weight:700}.ml-pagination{display:flex;justify-content:center;align-items:center;gap:20px;margin:24px 0}.ml-pagination a{color:#b01e31;font-weight:700}
@media(max-width:900px){.ml-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:640px){.ml-upload-card{align-items:flex-start;flex-direction:column;padding:17px}.ml-upload-form{width:100%}.ml-toolbar{align-items:stretch;flex-wrap:wrap}.ml-toolbar label:first-child{flex-basis:100%}.ml-result-count{margin-left:0}.ml-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:9px}.ml-preview{height:130px}.ml-card-details{padding:9px}}
</style>

<?php admin_footer(); ?>
