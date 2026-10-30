<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin('settings');
$self = admin_url('settings.php');

/* key => [label, kind, max, help] */
$groups = [
    'Organisation & contact' => [
        'site_name_en' => ['Organisation name (English)', 'text', 120],
        'site_name_bn' => ['Organisation name (বাংলা)', 'text', 120],
        'phone'        => ['Phone / WhatsApp number', 'text', 40, 'Used for the WhatsApp button. Include the country code, e.g. +8801XXXXXXXXX.'],
        'email'        => ['Email', 'email', 190],
        'address_en'   => ['Address (English)', 'text', 190],
        'address_bn'   => ['Address (বাংলা)', 'text', 190],
        'facebook'     => ['Facebook page link', 'url', 255, 'Enter the organisation profile URL. Platform homepages are not shown on the site.'],
        'instagram'    => ['Instagram profile link', 'url', 255],
        'x'            => ['X (Twitter) profile link', 'url', 255],
        'youtube'      => ['YouTube channel link', 'url', 255],
        'linkedin'     => ['LinkedIn page link', 'url', 255],
        'threads'      => ['Threads profile link', 'url', 255],
    ],
    'Homepage' => [
        'video_youtube_id' => ['Homepage video (YouTube link or ID)', 'video', 200],
        'local_screened'   => ['People screened (number shown on homepage)', 'text', 40, 'Leave empty to use the default text. Enter only verified figures.'],
        'source_note_en'   => ['Statistics source note (English)', 'text', 190],
        'source_note_bn'   => ['Statistics source note (বাংলা)', 'text', 190],
    ],
    'Leadership message (homepage)' => [
        'leader_name_en'  => ['Name (English)', 'text', 120, 'Leave all leadership fields empty to keep the built-in message.'],
        'leader_name_bn'  => ['Name (বাংলা)', 'text', 120],
        'leader_role_en'  => ['Role (English)', 'text', 120],
        'leader_role_bn'  => ['Role (বাংলা)', 'text', 120],
        'leader_quote_en' => ['Message (English)', 'textarea', 1200],
        'leader_quote_bn' => ['Message (বাংলা)', 'textarea', 1200],
        'leader_photo'    => ['Photo', 'image', 255],
    ],
    'App' => [
        'pwa_enabled' => ['Let visitors install the website as an app (PWA)', 'check', 1,
                          'Needs HTTPS on a live site. Only public pages are cached; admin pages and documents never are.'],
    ],
];

function load_settings(): array
{
    $o = [];
    foreach (db()->query('SELECT skey, svalue FROM settings') as $r) $o[$r['skey']] = (string)$r['svalue'];
    return $o;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf($self);
    $cur = load_settings(); $save = []; $errors = []; $oldFiles = [];
    foreach ($groups as $fields) {
        foreach ($fields as $key => $f) {
            [$label, $kind, $max] = $f;
            if ($kind === 'image') {
                if (!empty($_FILES[$key]['name'])) {
                    $p = upload_image($key);
                    if ($p === null) { $errors[] = $label . ': ' . (upload_last_error() ?: 'upload failed.'); }
                    else {
                        $save[$key] = $p;
                        media_library_register_path($p, (string)($_FILES[$key]['name'] ?? ''), media_library_current_admin_id());
                        if (!empty($cur[$key]) && $cur[$key] !== $p) $oldFiles[$key] = $cur[$key];
                    }
                } elseif (!empty($_POST['media_selection'][$key])) {
                    $selected = media_library_resolve_selection((int)$_POST['media_selection'][$key], [], ['image'], MAX_IMAGE_BYTES);
                    if (!$selected) { $errors[] = $label . ': selected library image is unavailable or invalid.'; }
                    else {
                        $save[$key] = $selected['path'];
                        if (!empty($cur[$key]) && $cur[$key] !== $selected['path']) $oldFiles[$key] = $cur[$key];
                    }
                } elseif (isset($_POST['_remove_' . $key]) && !empty($cur[$key])) {
                    $oldFiles[$key] = $cur[$key]; $save[$key] = '';
                }
                continue;
            }
            if ($kind === 'check') { $save[$key] = isset($_POST[$key]) ? '1' : '0'; continue; }
            $v = trim((string)($_POST[$key] ?? ''));
            $v = $kind === 'textarea' ? mb_substr($v, 0, $max) : mb_substr(preg_replace('/\s+/u', ' ', $v) ?? $v, 0, $max);
            if ($kind === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[] = $label . ' is not a valid email.';
            if ($kind === 'url' && $v !== '' && !preg_match('~^https?://~i', $v)) $errors[] = $label . ' must start with http:// or https://';
            if ($kind === 'video' && $v !== '') {
                if (preg_match('~^[A-Za-z0-9_-]{11}$~', $v)) { /* plain id */ }
                elseif (preg_match('~(?:v=|youtu\.be/|embed/|shorts/)([A-Za-z0-9_-]{11})~', $v, $m)) { $v = $m[1]; }
                else { $errors[] = $label . ' is not a valid YouTube link or ID.'; }
            }
            $save[$key] = $v;
        }
    }
    if ($errors) {
        flash('!' . implode(' ', $errors));
    } else {
        $st = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
        foreach ($save as $k => $v) $st->execute([$k, $v]);
        foreach ($oldFiles as $key => $path) {
            if (($save[$key] ?? $cur[$key] ?? null) !== $path && !media_library_is_path($path)) delete_upload($path);
        }
        flash('Settings saved.');
    }
    redirect($self);
}

$s = load_settings();
admin_header('Site settings', $user, 'settings');
?>
<div class="a-pagehead"><h1>Site settings</h1></div>
<form method="post" enctype="multipart/form-data" class="a-card a-form" data-dirty-guard>
  <?= csrf_field() ?>
  <?php foreach ($groups as $gname => $fields): ?>
    <fieldset class="a-group-box"><legend><?= e($gname) ?></legend>
    <?php foreach ($fields as $key => $f): [$label, $kind, $max] = $f; $help = $f[3] ?? ''; $val = $s[$key] ?? ''; ?>
      <?php if ($kind === 'check'): ?>
        <div class="a-field a-check"><label><input type="checkbox" name="<?= e($key) ?>" value="1"<?= $val === '1' ? ' checked' : '' ?>> <span><?= e($label) ?></span></label>
        <?php if ($help): ?><p class="a-help"><?= e($help) ?></p><?php endif; ?></div>
      <?php else: ?>
        <div class="a-field"><label for="s-<?= e($key) ?>"><?= e($label) ?></label>
        <?php if ($kind === 'textarea'): ?>
          <textarea id="s-<?= e($key) ?>" name="<?= e($key) ?>" rows="4" maxlength="<?= (int)$max ?>"><?= e($val) ?></textarea>
        <?php elseif ($kind === 'image'): ?>
          <?php if ($val): ?><div class="a-preview"><img src="<?= e(asset(img_variant($val, '-480'))) ?>" alt="Current photo" loading="lazy"></div><?php endif; ?>
          <input type="file" id="s-<?= e($key) ?>" name="<?= e($key) ?>" accept="image/jpeg,image/png,image/webp" data-image-input>
          <?php render_media_picker($key, 'image', ['jpg', 'jpeg', 'png', 'webp']); ?>
          <div class="a-preview" data-new-preview hidden><img alt="New photo preview"></div>
          <?php if ($val): ?><label class="a-inline-check"><input type="checkbox" name="_remove_<?= e($key) ?>" value="1"> Remove photo (use the built-in one)</label><?php endif; ?>
        <?php else: ?>
          <input type="<?= $kind === 'email' ? 'email' : ($kind === 'url' ? 'url' : 'text') ?>" id="s-<?= e($key) ?>" name="<?= e($key) ?>"
                 value="<?= e($val) ?>" maxlength="<?= (int)$max ?>"<?= $kind === 'email' ? ' inputmode="email"' : '' ?>>
        <?php endif; ?>
        <?php if ($help): ?><p class="a-help"><?= e($help) ?></p><?php endif; ?></div>
      <?php endif; ?>
    <?php endforeach; ?>
    </fieldset>
  <?php endforeach; ?>
  <div class="a-savebar"><button class="a-btn a-btn-primary" type="submit">Save settings</button></div>
</form>
<?php admin_footer(); ?>
