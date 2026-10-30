<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/includes/functions.php';

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');            // admin pages are never cached (browser, proxy or service worker)

function admin_url(string $page = 'index.php'): string
{
    return BASE_PATH . '/admin/' . ltrim($page, '/');
}

function admin_user(): ?array
{
    static $u = false;
    if ($u !== false) return $u;
    if (empty($_SESSION['admin_id'])) return $u = null;
    $st = db()->prepare('SELECT id, name, email, role FROM users WHERE id = ?');
    $st->execute([(int)$_SESSION['admin_id']]);
    return $u = ($st->fetch() ?: null);
}

/** Role permissions. admin = everything. */
function can(array $user, string $perm): bool
{
    $map = [
        'admin'      => ['content', 'create', 'delete', 'inbox', 'settings', 'users'],
        'editor'     => ['content', 'create', 'delete', 'inbox'],
        'translator' => ['content'],
    ];
    return in_array($perm, $map[$user['role']] ?? [], true);
}

function require_admin(string $perm = ''): array
{
    $u = admin_user();
    if (!$u) redirect(admin_url('login.php'));
    if ($perm !== '' && !can($u, $perm)) {
        http_response_code(403);
        admin_header('Not allowed', $u);
        echo '<h1>Not allowed</h1><p class="a-muted">Your role does not have access to this section. Ask an administrator if you need it.</p>';
        admin_footer();
        exit;
    }
    return $u;
}

function require_post_csrf(string $backTo): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_check()) {
        flash('!Your session expired. Please try again.');
        redirect($backTo);
    }
}

/** Renders the pending flash message ('!' prefix = error). */
function admin_flash(): void
{
    $m = flash();
    if ($m === null || $m === '') return;
    $err = str_starts_with($m, '!');
    echo '<div class="a-msg ' . ($err ? 'a-err' : 'a-ok') . '" role="' . ($err ? 'alert' : 'status') . '">'
       . e($err ? substr($m, 1) : $m) . '</div>';
}

function unread_count(): int
{
    try {
        return (int)db()->query("SELECT COUNT(*) FROM submissions WHERE is_read = 0")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function admin_nav(array $user): array
{
    $unread = unread_count();
    $nav = [
        'Overview' => [['index.php', 'Dashboard', 'dashboard']],
        'Content' => [
            ['content.php?type=news',          'News & articles', 'news'],
            ['content.php?type=notices',       'Notices',         'notices'],
            ['content.php?type=events',        'Events',          'events'],
            ['content.php?type=projects',      'Projects',        'projects'],
            ['content.php?type=documents',     'Documents & reports', 'documents'],
            ['content.php?type=gallery',       'Photos & Videos', 'gallery'],
            ['content.php?type=publications',  'Publications',    'publications'],
            ['content.php?type=pages',         'Pages',           'pages'],
        ],
        'Homepage & people' => [
            ['home-cards.php',                  'Home card manager', 'home_cards'],
            ['content.php?type=featured_cards', 'Featured cards', 'featured_cards'],
            ['content.php?type=team',           'Team',           'team'],
        ],
    ];
    if (can($user, 'inbox')) {
        $nav['Inbox'] = [
            ['submissions.php?type=contact',   'Messages',   'contact', $unread],
            ['submissions.php?type=member',    'Members',    'member'],
            ['submissions.php?type=volunteer', 'Volunteers', 'volunteer'],
            ['submissions.php?type=partner',   'Partners',   'partner'],
            ['submissions.php?type=donations', 'Donations',  'donations'],
            ['submissions.php?type=newsletter','Newsletter', 'newsletter'],
        ];
    }
    $site = [['profile.php', 'My account', 'profile']];
    if (can($user, 'settings')) array_unshift($site, ['settings.php', 'Site settings', 'settings']);
    if (can($user, 'users'))    array_unshift($site, ['users.php', 'Users', 'users']);
    if (can($user, 'content')) {
        $nav['Content'][] = ['media.php', 'Media Library', 'media'];
    }
    $nav['Site'] = $site;
    return $nav;
}

function admin_header(string $title, array $user, string $active = ''): void
{
    $cssV = (string)@filemtime(APP_ROOT . '/assets/css/admin.css');
    $jsV  = (string)@filemtime(APP_ROOT . '/assets/js/admin.js');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#1B1B1F">
<title><?= e($title) ?> · HeartCare Admin</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>?v=<?= e($cssV) ?>">
<script src="<?= e(asset('assets/js/admin-media-picker.js')) ?>?v=<?= e((string)@filemtime(APP_ROOT . '/assets/js/admin-media-picker.js')) ?>" defer></script>
<script src="<?= e(asset('assets/js/admin.js')) ?>?v=<?= e($jsV) ?>" defer></script>
</head>
<body>
<a class="a-skip" href="#main">Skip to content</a>
<header class="a-top">
  <button class="a-menu-btn" type="button" aria-controls="a-side" aria-expanded="false" aria-label="Open menu" data-a-menu>
    <span aria-hidden="true">☰</span>
  </button>
  <a class="a-brand" href="<?= e(admin_url()) ?>">Heart Care · Admin</a>
  <a class="a-top-link" href="<?= e(BASE_PATH) ?>/" target="_blank" rel="noopener">View site</a>
</header>
<div class="a-shell">
  <nav id="a-side" class="a-side" aria-label="Admin sections">
    <p class="a-user"><?= e($user['name']) ?> <span><?= e($user['role']) ?></span></p>
    <?php foreach (admin_nav($user) as $group => $items): ?>
      <p class="a-group"><?= e($group) ?></p>
      <?php foreach ($items as $it): ?>
        <a href="<?= e(admin_url($it[0])) ?>" class="<?= $active === $it[2] ? 'active' : '' ?>"<?= $active === $it[2] ? ' aria-current="page"' : '' ?>>
          <?= e($it[1]) ?>
          <?php if (!empty($it[3])): ?><span class="a-badge" aria-label="<?= (int)$it[3] ?> unread"><?= (int)$it[3] ?></span><?php endif; ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
    <a class="a-logout" href="<?= e(admin_url('logout.php')) ?>">Log out</a>
  </nav>
  <div class="a-overlay" data-a-overlay hidden></div>
  <main id="main" class="a-main">
<?php
    admin_flash();
}

function admin_footer(): void
{
    ?>
  </main>
</div>
<dialog class="a-dialog" id="a-confirm" aria-labelledby="a-confirm-title">
  <form method="dialog">
    <h2 id="a-confirm-title">Please confirm</h2>
    <p id="a-confirm-text"></p>
    <div class="a-actions">
      <button class="a-btn a-btn-sec" value="cancel" autofocus>Cancel</button>
      <button class="a-btn a-btn-danger" value="ok" id="a-confirm-ok">Delete</button>
    </div>
  </form>
</dialog>
</body>
</html>
<?php
}

/** Small POST form with a confirm dialog (used for every destructive action). */
function admin_post_button(string $action, array $fields, string $label, string $confirm = '', string $class = 'a-btn a-btn-sm'): string
{
    $h = '<form method="post" action="' . e($action) . '" class="a-inline"' . ($confirm !== '' ? ' data-confirm="' . e($confirm) . '"' : '') . '>'
       . csrf_field();
    foreach ($fields as $k => $v) {
        $h .= '<input type="hidden" name="' . e((string)$k) . '" value="' . e((string)$v) . '">';
    }
    return $h . '<button type="submit" class="' . e($class) . '">' . e($label) . '</button></form>';
}

function admin_pager(array $pg, array $query): void
{
    if ($pg['pages'] <= 1) return;
    $q = fn(int $p) => '?' . http_build_query($query + ['page' => $p]);
    echo '<nav class="a-pager" aria-label="Pagination">';
    echo $pg['page'] > 1 ? '<a class="a-btn a-btn-sec" href="' . e($q($pg['page'] - 1)) . '">‹ Previous</a>' : '<span></span>';
    echo '<span class="a-muted">Page ' . (int)$pg['page'] . ' of ' . (int)$pg['pages'] . ' · ' . (int)$pg['total'] . ' items</span>';
    echo $pg['page'] < $pg['pages'] ? '<a class="a-btn a-btn-sec" href="' . e($q($pg['page'] + 1)) . '">Next ›</a>' : '<span></span>';
    echo '</nav>';
}
