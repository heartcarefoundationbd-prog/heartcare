<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin('inbox');

$type = (string)($_GET['type'] ?? $_POST['type'] ?? 'contact');
$titles = ['contact' => 'Messages', 'member' => 'Membership requests', 'volunteer' => 'Volunteer requests',
           'partner' => 'Partner requests', 'donations' => 'Donations', 'newsletter' => 'Newsletter subscribers'];
if (!isset($titles[$type])) $type = 'contact';
$self = admin_url('submissions.php');
$listUrl = $self . '?type=' . urlencode($type);
$isSub = in_array($type, ['contact', 'member', 'volunteer', 'partner'], true);

/* ---------- POST actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { flash('!Your session expired. Please try again.'); redirect($listUrl); }
    $do = (string)($_POST['do'] ?? ''); $id = (int)($_POST['id'] ?? 0);

    if ($isSub && $id) {
        if ($do === 'read' || $do === 'unread') {
            db()->prepare('UPDATE submissions SET is_read = ? WHERE id = ? AND type = ?')->execute([$do === 'read' ? 1 : 0, $id, $type]);
            flash($do === 'read' ? 'Marked as read.' : 'Marked as unread.');
        } elseif ($do === 'delete') {
            db()->prepare('DELETE FROM submissions WHERE id = ? AND type = ?')->execute([$id, $type]);
            flash('Deleted.');
        }
    } elseif ($isSub && $do === 'read_all') {
        db()->prepare('UPDATE submissions SET is_read = 1 WHERE type = ?')->execute([$type]);
        flash('All marked as read.');
    } elseif ($type === 'donations' && $id) {
        if ($do === 'status' && in_array($_POST['status'] ?? '', ['pending', 'paid', 'failed'], true)) {
            db()->prepare('UPDATE donations SET status = ? WHERE id = ?')->execute([$_POST['status'], $id]);
            flash('Donation status updated.');
        } elseif ($do === 'delete') {
            db()->prepare('DELETE FROM donations WHERE id = ?')->execute([$id]);
            flash('Deleted.');
        }
    } elseif ($type === 'newsletter' && $id && $do === 'delete') {
        db()->prepare('DELETE FROM newsletter WHERE id = ?')->execute([$id]);
        flash('Removed.');
    }
    $back = (string)($_POST['back'] ?? '');
    redirect(($back !== '' && $back[0] === '/' && !str_starts_with($back, '//')) ? $back : $listUrl);
}

/* ---------- CSV export ---------- */
function csv_cell(mixed $v): string
{
    $s = (string)$v;
    if ($s !== '' && in_array($s[0], ['=', '+', '-', '@', "\t", "\r"], true)) $s = "'" . $s;   // spreadsheet formula guard
    return '"' . str_replace('"', '""', $s) . '"';
}
if (isset($_GET['export'])) {
    $sql = match (true) {
        $isSub => ['SELECT name,email,phone,subject,message,lang,is_read,created_at FROM submissions WHERE type = ? ORDER BY created_at DESC', [$type]],
        $type === 'donations' => ['SELECT name,email,phone,amount,method,trx_id,message,status,created_at FROM donations ORDER BY created_at DESC', []],
        default => ['SELECT email,lang,created_at FROM newsletter ORDER BY created_at DESC', []],
    };
    $st = db()->prepare($sql[0]); $st->execute($sql[1]);
    $rows = $st->fetchAll();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="heartcare-' . $type . '-' . date('Ymd') . '.csv"');
    echo "\xEF\xBB\xBF";                                   // BOM so Excel reads Bangla correctly
    if ($rows) {
        echo implode(',', array_map('csv_cell', array_keys($rows[0]))) . "\r\n";
        foreach ($rows as $r) echo implode(',', array_map('csv_cell', $r)) . "\r\n";
    }
    exit;
}

/* ---------- Detail view ---------- */
if ($isSub && isset($_GET['id'])) {
    $st = db()->prepare('SELECT * FROM submissions WHERE id = ? AND type = ?');
    $st->execute([(int)$_GET['id'], $type]);
    $r = $st->fetch();
    if ($r) {
        if (!$r['is_read']) db()->prepare('UPDATE submissions SET is_read = 1 WHERE id = ?')->execute([$r['id']]);
        admin_header($titles[$type], $user, $type);
        ?>
        <div class="a-pagehead"><h1><?= e($titles[$type]) ?></h1><a class="a-btn a-btn-sec" href="<?= e($listUrl) ?>">← Back to list</a></div>
        <div class="a-card">
          <dl class="a-dl">
            <dt>Name</dt><dd><?= e($r['name']) ?></dd>
            <dt>Email</dt><dd><?php if ($r['email']): ?><a href="mailto:<?= e($r['email']) ?>"><?= e($r['email']) ?></a><?php endif; ?></dd>
            <dt>Phone</dt><dd><?php if ($r['phone']): ?><a href="tel:<?= e($r['phone']) ?>"><?= e($r['phone']) ?></a><?php endif; ?></dd>
            <dt>Subject</dt><dd><?= e((string)$r['subject']) ?></dd>
            <dt>Language</dt><dd><?= e((string)$r['lang']) ?></dd>
            <dt>Received</dt><dd><?= e((string)$r['created_at']) ?></dd>
            <dt>Message</dt><dd class="a-pre"><?= e((string)$r['message']) ?></dd>
          </dl>
          <div class="a-actions">
            <?= admin_post_button($self, ['type' => $type, 'do' => 'unread', 'id' => $r['id']], 'Mark unread') ?>
            <?= admin_post_button($self, ['type' => $type, 'do' => 'delete', 'id' => $r['id']], 'Delete', 'Delete this entry? This cannot be undone.', 'a-btn a-btn-sm a-btn-danger-o') ?>
          </div>
        </div>
        <?php
        admin_footer();
        exit;
    }
}

/* ---------- List ---------- */
if ($isSub) {
    $total = db()->prepare('SELECT COUNT(*) FROM submissions WHERE type = ?'); $total->execute([$type]);
    $pg = paginate((int)$total->fetchColumn(), 25, (int)($_GET['page'] ?? 1));
    $st = db()->prepare('SELECT * FROM submissions WHERE type = ? ORDER BY created_at DESC LIMIT ' . (int)$pg['per'] . ' OFFSET ' . (int)$pg['offset']);
    $st->execute([$type]);
} elseif ($type === 'donations') {
    $pg = paginate((int)db()->query('SELECT COUNT(*) FROM donations')->fetchColumn(), 25, (int)($_GET['page'] ?? 1));
    $st = db()->query('SELECT * FROM donations ORDER BY created_at DESC LIMIT ' . (int)$pg['per'] . ' OFFSET ' . (int)$pg['offset']);
} else {
    $pg = paginate((int)db()->query('SELECT COUNT(*) FROM newsletter')->fetchColumn(), 50, (int)($_GET['page'] ?? 1));
    $st = db()->query('SELECT * FROM newsletter ORDER BY created_at DESC LIMIT ' . (int)$pg['per'] . ' OFFSET ' . (int)$pg['offset']);
}
$rows = $st->fetchAll();

admin_header($titles[$type], $user, $type);
?>
<div class="a-pagehead">
  <h1><?= e($titles[$type]) ?></h1>
  <div class="a-actions">
    <?php if ($isSub && $rows): echo admin_post_button($self, ['type' => $type, 'do' => 'read_all'], 'Mark all read', '', 'a-btn a-btn-sec'); endif; ?>
    <a class="a-btn a-btn-sec" href="<?= e($listUrl) ?>&amp;export=1">Export CSV</a>
  </div>
</div>

<?php if (!$rows): ?>
  <p class="a-empty">Nothing here yet.</p>
<?php elseif ($isSub): ?>
<table class="a-table">
  <thead><tr><th scope="col">Name</th><th scope="col">Contact</th><th scope="col">Message</th><th scope="col">Received</th><th scope="col">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr class="<?= $r['is_read'] ? '' : 'a-unread' ?>">
      <td data-label="Name"><?= $r['is_read'] ? '' : '<span class="a-dot" title="Unread"></span>' ?><?= e($r['name']) ?></td>
      <td data-label="Contact"><?= e(trim($r['email'] . ' ' . $r['phone'])) ?></td>
      <td data-label="Message"><?= e(mb_strimwidth((string)$r['message'], 0, 90, '…')) ?></td>
      <td data-label="Received"><?= e((string)$r['created_at']) ?></td>
      <td data-label="Actions" class="a-row-actions">
        <a class="a-btn a-btn-sm" href="<?= e($listUrl) ?>&amp;id=<?= (int)$r['id'] ?>">Open</a>
        <?= admin_post_button($self, ['type' => $type, 'do' => $r['is_read'] ? 'unread' : 'read', 'id' => $r['id'], 'back' => $_SERVER['REQUEST_URI'] ?? ''], $r['is_read'] ? 'Mark unread' : 'Mark read') ?>
        <?= admin_post_button($self, ['type' => $type, 'do' => 'delete', 'id' => $r['id']], 'Delete', 'Delete this entry? This cannot be undone.', 'a-btn a-btn-sm a-btn-danger-o') ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php elseif ($type === 'donations'): ?>
<table class="a-table">
  <thead><tr><th scope="col">Donor</th><th scope="col">Amount</th><th scope="col">Method / Trx</th><th scope="col">Date</th><th scope="col">Status</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Donor"><?= e($r['name']) ?><br><small class="a-muted"><?= e(trim($r['email'] . ' ' . $r['phone'])) ?></small></td>
      <td data-label="Amount">৳ <?= e(number_format((float)$r['amount'])) ?></td>
      <td data-label="Method / Trx"><?= e($r['method']) ?><br><small class="a-muted"><?= e((string)$r['trx_id']) ?></small></td>
      <td data-label="Date"><?= e((string)$r['created_at']) ?></td>
      <td data-label="Status">
        <form method="post" action="<?= e($self) ?>" class="a-inline a-status-form">
          <?= csrf_field() ?>
          <input type="hidden" name="type" value="donations"><input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <input type="hidden" name="back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
          <label class="a-sr" for="st-<?= (int)$r['id'] ?>">Status</label>
          <select id="st-<?= (int)$r['id'] ?>" name="status">
            <?php foreach (['pending', 'paid', 'failed'] as $s): ?>
              <option value="<?= $s ?>"<?= $r['status'] === $s ? ' selected' : '' ?>><?= ucfirst($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="a-btn a-btn-sm" type="submit">Update</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php else: ?>
<table class="a-table">
  <thead><tr><th scope="col">Email</th><th scope="col">Language</th><th scope="col">Subscribed</th><th scope="col">Actions</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td data-label="Email"><?= e($r['email']) ?></td>
      <td data-label="Language"><?= e((string)$r['lang']) ?></td>
      <td data-label="Subscribed"><?= e((string)$r['created_at']) ?></td>
      <td data-label="Actions" class="a-row-actions">
        <?= admin_post_button($self, ['type' => 'newsletter', 'do' => 'delete', 'id' => $r['id']], 'Remove', 'Remove this subscriber?', 'a-btn a-btn-sm a-btn-danger-o') ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif;
if ($rows) admin_pager($pg, ['type' => $type]);
admin_footer();
