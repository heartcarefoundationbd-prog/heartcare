<?php
declare(strict_types=1);

/* ==========================================================================
   Public form handlers. They run BEFORE any HTML is sent so they can
   redirect after saving (Post/Redirect/Get) and a refresh never re-submits.
   ========================================================================== */

/** Renders the pending flash message. A leading '!' marks an error. */
function flash_html(): string
{
    $m = flash();
    if ($m === null || $m === '') return '';
    $err = str_starts_with($m, '!');
    $text = $err ? substr($m, 1) : $m;
    return '<div class="notice ' . ($err ? 'notice-error' : 'notice-success') . '" role="' . ($err ? 'alert' : 'status') . '">' . e($text) . '</div>';
}

function form_spam(): bool
{
    // Honeypot: real visitors never see or fill this field.
    return trim((string)($_POST['website'] ?? '')) !== '';
}

function form_throttled(string $bucket, int $max = 8, int $windowSec = 600): bool
{
    $now = time();
    $hits = array_filter($_SESSION['throttle'][$bucket] ?? [], fn($t) => $t > $now - $windowSec);
    if (count($hits) >= $max) { $_SESSION['throttle'][$bucket] = $hits; return true; }
    $hits[] = $now;
    $_SESSION['throttle'][$bucket] = $hits;
    return false;
}

function form_done(string $path, string $msgKey = 'thank_you'): never
{
    flash(t($msgKey));
    redirect(url($path));
}

function handle_public_post(string $route): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!in_array($route, ['contact', 'join', 'donate'], true)) return;

    if (!csrf_check()) {
        flash('!' . t('form_expired'));
        redirect(url($route));
    }
    if (form_spam()) form_done($route);               // pretend success to bots
    if (form_throttled($route)) { flash('!' . t('form_slow_down')); redirect(url($route)); }

    $clip = fn(string $k, int $max) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);
    $email = $clip('email', 190);
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('!' . t('form_bad_email'));
        redirect(url($route));
    }

    if ($route === 'contact') {
        if (($_POST['form'] ?? '') === 'newsletter') {
            if ($email === '') { flash('!' . t('form_bad_email')); redirect(url('contact')); }
            db()->prepare('INSERT IGNORE INTO newsletter (email, lang) VALUES (?,?)')->execute([$email, lang()]);
            form_done('contact');
        }
        if ($clip('name', 120) === '' || $clip('message', 4000) === '') {
            flash('!' . t('form_required')); redirect(url('contact'));
        }
        db()->prepare('INSERT INTO submissions (type,name,email,phone,subject,message,lang) VALUES ("contact",?,?,?,?,?,?)')
            ->execute([$clip('name', 120), $email, $clip('phone', 40), $clip('subject', 190), $clip('message', 4000), lang()]);
        form_done('contact');
    }

    if ($route === 'join') {
        $type = in_array($_POST['type'] ?? '', ['member', 'volunteer', 'partner'], true) ? $_POST['type'] : 'member';
        if ($clip('name', 120) === '') { flash('!' . t('form_required')); redirect(url('join')); }
        db()->prepare('INSERT INTO submissions (type,name,email,phone,subject,message,lang) VALUES (?,?,?,?,?,?,?)')
            ->execute([$type, $clip('name', 120), $email, $clip('phone', 40), $clip('subject', 190), $clip('message', 4000), lang()]);
        form_done('join');
    }

    if ($route === 'donate') {
        $amount = round((float)($_POST['amount'] ?? 0), 2);
        $method = in_array($_POST['method'] ?? '', ['bkash', 'nagad', 'rocket', 'card'], true) ? $_POST['method'] : 'bkash';
        if ($clip('name', 120) === '' || $amount < 1 || $amount > 99999999) {
            flash('!' . t('form_required')); redirect(url('donate'));
        }
        db()->prepare('INSERT INTO donations (name,email,phone,amount,method,trx_id,message,status) VALUES (?,?,?,?,?,?,?,"pending")')
            ->execute([$clip('name', 120), $email, $clip('phone', 40), $amount, $method, $clip('trx_id', 80), $clip('message', 400)]);
        form_done('donate');
    }
}
