<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin();

function count_of(string $sql): int
{
    try { return (int)db()->query($sql)->fetchColumn(); } catch (Throwable $e) { return 0; }
}

$cards = [
    ['News',        'SELECT COUNT(*) FROM news',          'content.php?type=news'],
    ['Notices',     'SELECT COUNT(*) FROM notices',       'content.php?type=notices'],
    ['Events',      'SELECT COUNT(*) FROM events',        'content.php?type=events'],
    ['Projects',    'SELECT COUNT(*) FROM projects',      'content.php?type=projects'],
    ['Documents',   'SELECT COUNT(*) FROM documents',     'content.php?type=documents'],
    ['Photos',      'SELECT COUNT(*) FROM gallery_items', 'content.php?type=gallery'],
];
$inbox = [];
if (can($user, 'inbox')) {
    $inbox = [
        ['Unread messages', "SELECT COUNT(*) FROM submissions WHERE is_read = 0", 'submissions.php?type=contact'],
        ['Pending donations', "SELECT COUNT(*) FROM donations WHERE status = 'pending'", 'submissions.php?type=donations'],
        ['Subscribers', 'SELECT COUNT(*) FROM newsletter', 'submissions.php?type=newsletter'],
    ];
}

admin_header('Dashboard', $user, 'dashboard');
?>
<h1>Dashboard</h1>

<?php if (can($user, 'create')): ?>
<section aria-labelledby="qa">
  <h2 id="qa" class="a-h2">Quick actions</h2>
  <div class="a-quick">
    <a class="a-btn a-btn-primary" href="<?= e(admin_url('content.php?type=news&action=new')) ?>">+ Write article</a>
    <a class="a-btn a-btn-primary" href="<?= e(admin_url('content.php?type=notices&action=new')) ?>">+ Post notice</a>
    <a class="a-btn a-btn-primary" href="<?= e(admin_url('content.php?type=events&action=new')) ?>">+ Add event</a>
    <a class="a-btn a-btn-primary" href="<?= e(admin_url('content.php?type=documents&action=new')) ?>">+ Upload report</a>
    <a class="a-btn a-btn-primary" href="<?= e(admin_url('content.php?type=gallery&action=new')) ?>">+ Add photo</a>
    <a class="a-btn a-btn-primary" href="<?= e(admin_url('content.php?type=projects&action=new')) ?>">+ Add project</a>
  </div>
</section>
<?php endif; ?>

<?php if ($inbox): ?>
<h2 class="a-h2">Inbox</h2>
<div class="a-stats">
  <?php foreach ($inbox as [$label, $sql, $href]): $n = count_of($sql); ?>
    <a class="a-stat<?= $n > 0 && $label !== 'Subscribers' ? ' a-stat-alert' : '' ?>" href="<?= e(admin_url($href)) ?>">
      <strong><?= $n ?></strong><span><?= e($label) ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<h2 class="a-h2">Content</h2>
<div class="a-stats">
  <?php foreach ($cards as [$label, $sql, $href]): ?>
    <a class="a-stat" href="<?= e(admin_url($href)) ?>"><strong><?= count_of($sql) ?></strong><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
</div>

<div class="a-card a-tips">
  <h2 class="a-h2">Tips</h2>
  <ul>
    <li>Choose the language an article is written in; English and Bangla article versions are not required.</li>
    <li>Health articles should be reviewed by a cardiologist before you publish them.</li>
    <li>Photos from your phone are resized automatically. Documents can be PDF, Word or Excel, up to 10 MB.</li>
    <li>Use <em>Unpublish</em> to hide an item without deleting it.</li>
  </ul>
</div>
<?php admin_footer(); ?>
