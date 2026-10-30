<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin('users');
$self = admin_url('users.php');
$roles = ['admin' => 'Administrator — everything', 'editor' => 'Editor — content and inbox', 'translator' => 'Translator — edit existing content only'];

function admin_count(): int { return (int)db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf($self);
    $do = (string)($_POST['do'] ?? ''); $id = (int)($_POST['id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $role = (string)($_POST['role'] ?? 'editor');
    $pass = (string)($_POST['password'] ?? '');

    if ($do === 'delete') {
        $t = db()->prepare('SELECT role FROM users WHERE id = ?'); $t->execute([$id]); $row = $t->fetch();
        if (!$row) flash('!User not found.');
        elseif ($id === (int)$user['id']) flash('!You cannot delete your own account.');
        elseif ($row['role'] === 'admin' && admin_count() <= 1) flash('!You cannot delete the last administrator.');
        else { db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]); flash('User deleted.'); }
        redirect($self);
    }

    $err = '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err = 'Enter a name and a valid email.';
    elseif (!isset($roles[$role])) $err = 'Choose a role.';
    elseif ($do === 'create' && strlen($pass) < 10) $err = 'The password needs at least 10 characters.';
    elseif ($do === 'update' && $pass !== '' && strlen($pass) < 10) $err = 'The new password needs at least 10 characters.';
    if ($err === '') {
        $dup = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?'); $dup->execute([$email, $id]);
        if ($dup->fetch()) $err = 'That email is already used.';
    }
    if ($err === '' && $do === 'update' && $id === (int)$user['id'] && $role !== 'admin' && admin_count() <= 1) $err = 'You are the last administrator; keep the Administrator role.';
    if ($err !== '') { flash('!' . $err); redirect($self . ($do === 'update' ? '?edit=' . $id : '?new=1')); }

    if ($do === 'create') {
        db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $role]);
        flash('User created.');
    } elseif ($do === 'update') {
        db()->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?')->execute([$name, $email, $role, $id]);
        if ($pass !== '') db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
        flash('User saved.');
    }
    redirect($self);
}

$editing = null;
if (isset($_GET['edit'])) {
    $st = db()->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([(int)$_GET['edit']]); $editing = $st->fetch() ?: null;
}
$creating = isset($_GET['new']);

admin_header('Users', $user, 'users');
if ($editing || $creating):
    $u = $editing ?: ['id' => 0, 'name' => '', 'email' => '', 'role' => 'editor'];
?>
  <div class="a-pagehead"><h1><?= $editing ? 'Edit user' : 'New user' ?></h1><a class="a-btn a-btn-sec" href="<?= e($self) ?>">← Back to users</a></div>
  <form method="post" class="a-card a-form">
    <?= csrf_field() ?>
    <input type="hidden" name="do" value="<?= $editing ? 'update' : 'create' ?>"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
    <div class="a-field"><label for="u-name">Name</label><input id="u-name" name="name" value="<?= e($u['name']) ?>" required maxlength="120"></div>
    <div class="a-field"><label for="u-email">Email (used to log in)</label><input id="u-email" type="email" name="email" value="<?= e($u['email']) ?>" required inputmode="email" maxlength="190"></div>
    <div class="a-field"><label for="u-role">Role</label>
      <select id="u-role" name="role"><?php foreach ($roles as $k => $l): ?><option value="<?= e($k) ?>"<?= $u['role'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
    <div class="a-field"><label for="u-pass"><?= $editing ? 'New password (leave empty to keep the current one)' : 'Password (10+ characters)' ?></label>
      <input id="u-pass" type="password" name="password" autocomplete="new-password" minlength="10"<?= $editing ? '' : ' required' ?>></div>
    <div class="a-savebar"><button class="a-btn a-btn-primary" type="submit">Save user</button></div>
  </form>
<?php else:
    $rows = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY name')->fetchAll();
?>
  <div class="a-pagehead"><h1>Users</h1><a class="a-btn a-btn-primary" href="<?= e($self) ?>?new=1">+ New user</a></div>
  <table class="a-table">
    <thead><tr><th scope="col">Name</th><th scope="col">Email</th><th scope="col">Role</th><th scope="col">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td data-label="Name"><?= e($r['name']) ?><?= (int)$r['id'] === (int)$user['id'] ? ' <span class="a-muted">(you)</span>' : '' ?></td>
        <td data-label="Email"><?= e($r['email']) ?></td>
        <td data-label="Role"><?= e($r['role']) ?></td>
        <td data-label="Actions" class="a-row-actions">
          <a class="a-btn a-btn-sm" href="<?= e($self) ?>?edit=<?= (int)$r['id'] ?>">Edit</a>
          <?php if ((int)$r['id'] !== (int)$user['id']) echo admin_post_button($self, ['do' => 'delete', 'id' => $r['id']], 'Delete', 'Delete this user?', 'a-btn a-btn-sm a-btn-danger-o'); ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif;
admin_footer();
