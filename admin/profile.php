<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';
$user = require_admin();
$self = admin_url('profile.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post_csrf($self);
    $name = trim((string)($_POST['name'] ?? ''));
    $cur  = (string)($_POST['current'] ?? '');
    $new  = (string)($_POST['password'] ?? '');
    if ($name === '') { flash('!Enter your name.'); redirect($self); }
    db()->prepare('UPDATE users SET name = ? WHERE id = ?')->execute([mb_substr($name, 0, 120), $user['id']]);
    if ($new !== '') {
        $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?'); $st->execute([$user['id']]);
        if (!password_verify($cur, (string)$st->fetchColumn())) { flash('!Your current password is not correct.'); redirect($self); }
        if (strlen($new) < 10) { flash('!The new password needs at least 10 characters.'); redirect($self); }
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
    }
    flash('Your account was updated.');
    redirect($self);
}
admin_header('My account', $user, 'profile');
?>
<div class="a-pagehead"><h1>My account</h1></div>
<form method="post" class="a-card a-form">
  <?= csrf_field() ?>
  <div class="a-field"><label for="p-name">Name</label><input id="p-name" name="name" value="<?= e($user['name']) ?>" required maxlength="120"></div>
  <div class="a-field"><label for="p-email">Email</label><input id="p-email" value="<?= e($user['email']) ?>" disabled></div>
  <fieldset class="a-group-box"><legend>Change password</legend>
    <div class="a-field"><label for="p-cur">Current password</label><input id="p-cur" type="password" name="current" autocomplete="current-password"></div>
    <div class="a-field"><label for="p-new">New password (10+ characters)</label><input id="p-new" type="password" name="password" minlength="10" autocomplete="new-password"></div>
  </fieldset>
  <div class="a-savebar"><button class="a-btn a-btn-primary" type="submit">Save</button></div>
</form>
<?php admin_footer(); ?>
