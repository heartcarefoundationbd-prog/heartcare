<?php
declare(strict_types=1);
/* One-time first administrator. Works only while the users table is empty. Delete this file afterwards. */
require dirname(__DIR__) . '/config/config.php';
require dirname(__DIR__) . '/includes/functions.php';

try {
    $count = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Throwable $e) {
    exit('The database tables are missing. Import sql/install.sql first (see README).');
}
if ($count > 0) { http_response_code(403); exit('Setup is already complete. Delete admin/setup.php from the server.'); }

$msg = ''; $ok = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string)($_POST['name'] ?? ''));
    $email = filter_var(trim((string)($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
    $pass  = (string)($_POST['password'] ?? '');
    if (!csrf_check()) {
        $msg = 'Your session expired. Please try again.';
    } elseif ($name !== '' && $email && strlen($pass) >= 10) {
        db()->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,"admin")')
            ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        $ok = true;
        $msg = 'Administrator created. Log in, then delete admin/setup.php.';
    } else {
        $msg = 'Fill in all fields. The password needs at least 10 characters.';
    }
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex"><title>Create administrator</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>"></head>
<body class="a-login-body"><main class="a-login">
<h1>Create the first administrator</h1>
<?php if ($msg): ?><div class="a-msg <?= $ok ? 'a-ok' : 'a-err' ?>" role="status"><?= e($msg) ?> <?php if ($ok): ?><a href="login.php">Log in</a><?php endif; ?></div><?php endif; ?>
<?php if (!$ok): ?>
<form method="post" class="a-form">
  <?= csrf_field() ?>
  <div class="a-field"><label for="name">Name</label><input id="name" name="name" required autocomplete="name"></div>
  <div class="a-field"><label for="email">Email</label><input id="email" type="email" name="email" required autocomplete="username"></div>
  <div class="a-field"><label for="password">Password (10+ characters)</label><input id="password" type="password" name="password" minlength="10" required autocomplete="new-password"></div>
  <button class="a-btn a-btn-primary a-btn-block" type="submit">Create administrator</button>
</form>
<?php endif; ?>
</main></body></html>
