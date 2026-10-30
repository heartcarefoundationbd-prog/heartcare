<?php
declare(strict_types=1);
require __DIR__ . '/includes.php';

if (admin_user()) redirect(admin_url());

function login_blocked(string $ip, string $email): bool
{
    try {
        $st = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE created_at > (NOW() - INTERVAL 15 MINUTE) AND (ip = ? OR email = ?)');
        $st->execute([$ip, $email]);
        return (int)$st->fetchColumn() >= 8;
    } catch (Throwable $e) {
        return false;               // table missing: do not lock anyone out
    }
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    if (!csrf_check()) {
        $err = 'Your session expired. Please try again.';
    } elseif (login_blocked($ip, $email)) {
        $err = 'Too many attempts. Please wait 15 minutes and try again.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify((string)($_POST['password'] ?? ''), $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int)$u['id'];
            try { db()->prepare('DELETE FROM login_attempts WHERE ip = ? OR email = ?')->execute([$ip, $email]); } catch (Throwable $e) {}
            redirect(admin_url());
        }
        try { db()->prepare('INSERT INTO login_attempts (ip, email) VALUES (?, ?)')->execute([$ip, $email]); } catch (Throwable $e) {}
        $err = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Admin login · HeartCare</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>?v=<?= e((string)@filemtime(APP_ROOT . '/assets/css/admin.css')) ?>">
</head>
<body class="a-login-body">
<main class="a-login">
  <h1>Heart Care · Admin</h1>
  <?php if ($err): ?><div class="a-msg a-err" role="alert"><?= e($err) ?></div><?php endif; ?>
  <form method="post" class="a-form">
    <?= csrf_field() ?>
    <div class="a-field"><label for="email">Email</label>
      <input type="email" id="email" name="email" required autocomplete="username" inputmode="email" autofocus></div>
    <div class="a-field"><label for="password">Password</label>
      <input type="password" id="password" name="password" required autocomplete="current-password"></div>
    <button class="a-btn a-btn-primary a-btn-block" type="submit">Log in</button>
  </form>
</main>
</body>
</html>
