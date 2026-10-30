<?php
$s = all_settings();
$siteName = lang() === 'bn' ? ($s['site_name_bn'] ?? '') : ($s['site_name_en'] ?? '');
$styleVersion = filemtime(APP_ROOT . '/assets/css/style.css');
$respVersion  = is_file(APP_ROOT . '/assets/css/responsive.css') ? filemtime(APP_ROOT . '/assets/css/responsive.css') : 0;
$newsTweakVersion = filemtime(APP_ROOT . '/assets/css/news-tweak.css');
$pwaOn = setting('pwa_enabled') === '1';
$nav = [
    ['label' => t('nav_mission'),   'url' => url('mission')],
    ['label' => t('nav_impact'),    'url' => url('events')],
    ['label' => t('nav_community'), 'url' => url('team')],
    ['label' => t('nav_health'),    'url' => url('publications.php')],
    ['label' => t('nav_news'),      'url' => url('news')],
];
$navMore = [
    ['label' => t('nav_notices'),   'url' => url('notices')],
    ['label' => t('nav_projects'),  'url' => url('projects')],
    ['label' => t('nav_documents'), 'url' => url('documents')],
    ['label' => t('nav_gallery'),   'url' => url('gallery')],
];
?>
<!DOCTYPE html>
<html lang="<?= e(lang()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? $siteName) ?></title>
<meta name="description" content="<?= e($metaDescription ?? t('tagline')) ?>">
<?php if (!empty($metaKeywords)): ?><meta name="keywords" content="<?= e($metaKeywords) ?>"><?php endif; ?>
<?php if (!empty($canonicalUrl)): ?><link rel="canonical" href="<?= e($canonicalUrl) ?>"><?php endif; ?>
<meta property="og:title" content="<?= e($pageTitle ?? $siteName) ?>">
<meta property="og:description" content="<?= e($metaDescription ?? t('tagline')) ?>">
<meta property="og:type" content="<?= e($ogType ?? 'website') ?>">
<?php if (!empty($canonicalUrl)): ?><meta property="og:url" content="<?= e($canonicalUrl) ?>"><?php endif; ?>
<meta property="og:locale" content="<?= e(($contentLang ?? lang()) === 'bn' ? 'bn_BD' : 'en_US') ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= e($ogImage) ?>"><?php endif; ?>
<meta name="twitter:card" content="<?= !empty($ogImage) ? 'summary_large_image' : 'summary' ?>">
<meta name="twitter:title" content="<?= e($pageTitle ?? $siteName) ?>">
<meta name="twitter:description" content="<?= e($metaDescription ?? t('tagline')) ?>">
<?php if (!empty($ogImage)): ?><meta name="twitter:image" content="<?= e($ogImage) ?>"><?php endif; ?>

<?php if (!isset($hasLanguageAlternates) || $hasLanguageAlternates): ?>
<link rel="alternate" hreflang="en" href="<?= e(switch_lang_url('en')) ?>">
<link rel="alternate" hreflang="bn" href="<?= e(switch_lang_url('bn')) ?>">
<?php endif; ?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<?php if (lang() === 'bn'): ?>
  <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php else: ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('assets/css/style.css')) ?>?v=<?= e((string)$styleVersion) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/responsive.css')) ?>?v=<?= e((string)$respVersion) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/news-tweak.css')) ?>?v=<?= e((string)$newsTweakVersion) ?>">
<meta name="theme-color" content="#D7263D">
<?php if ($pwaOn): ?>
<link rel="manifest" href="<?= e(asset('manifest.webmanifest')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('assets/icons/icon-192.png')) ?>">
<?php endif; ?>
</head>
<body class="lang-<?= e(lang()) ?><?= !empty($isHome) ? ' page-home' : '' ?>">
<a class="skip" href="#main"><?= e(t('skip_to_content')) ?></a>

<?php $socialLinks = social_links(); ?>
<?php if ($socialLinks): ?>
<div class="social-topbar">
  <div class="wrap social-topbar-inner">
    <nav class="social-links" aria-label="<?= e(lang() === 'bn' ? 'সামাজিক যোগাযোগমাধ্যম' : 'Social media') ?>">
      <?php foreach ($socialLinks as $social): ?>
        <a class="social-icon-link" href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($social['label']) ?>" title="<?= e($social['label']) ?>">
          <?php if ($social['key'] === 'facebook'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.5 21v-8h2.7l.4-3h-3.1V8.1c0-.9.3-1.5 1.6-1.5h1.7V3.9c-.3 0-1.3-.1-2.5-.1-2.5 0-4.2 1.5-4.2 4.3V10H7.3v3h2.8v8h3.4Z"/></svg>
          <?php elseif ($social['key'] === 'instagram'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.8" r="1.2"/></svg>
          <?php elseif ($social['key'] === 'x'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 14.8 5.7 22H2.5l7.3-8.4L1.9 2h6.5l4.5 6.7L18.9 2Zm-1.1 18h1.7L7.3 3.9H5.5L17.8 20Z"/></svg>
          <?php elseif ($social['key'] === 'youtube'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.6 12 3.6 12 3.6s-7.5 0-9.4.5A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.5 9.4.5 9.4.5s7.5 0 9.4-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.6 15.6V8.4l6.3 3.6-6.3 3.6Z"/></svg>
          <?php elseif ($social['key'] === 'linkedin'): ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5.2 3a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4ZM3.3 9h3.8v12H3.3V9Zm6.2 0h3.6v1.6h.1A4 4 0 0 1 17 8.5c4 0 4.7 2.6 4.7 6V21h-3.8v-5.8c0-1.4 0-3.2-2-3.2s-2.4 1.5-2.4 3.1V21H9.5V9Z"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.2a9.8 9.8 0 0 0-9.8 9.9c0 5.4 4.4 9.7 9.8 9.7s9.8-4.3 9.8-9.7A9.8 9.8 0 0 0 12 2.2Zm0 3.1c2.4 0 4.4 1.9 4.4 4.3h-2.7c0-.9-.7-1.6-1.7-1.6s-1.7.7-1.7 1.6.7 1.5 1.7 1.5c2.5 0 4.4 1.9 4.4 4.3S14.4 19.8 12 19.8s-4.4-1.9-4.4-4.4h2.7c0 1 .7 1.7 1.7 1.7s1.7-.7 1.7-1.7c0-.9-.7-1.6-1.7-1.6-2.5 0-4.4-1.9-4.4-4.2s1.9-4.3 4.4-4.3Z"/></svg>
          <?php endif; ?>
          <span class="sr-only"><?= e($social['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
</div>
<?php endif; ?>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(url()) ?>" aria-label="<?= e($siteName) ?>">
      <img src="<?= e(asset('public/images/logo.svg')) ?>"
           alt="<?= e($siteName) ?>"
           class="brand-logo"
           width="140"
           height="70">
    </a>
    <!-- বাকি nav এখানে -->
  

    <button class="nav-toggle" type="button" aria-label="<?= e(t('menu_open')) ?>" aria-controls="main-navigation" aria-expanded="false"
            data-nav-toggle data-label-open="<?= e(t('menu_open')) ?>" data-label-close="<?= e(t('menu_close')) ?>"><span aria-hidden="true">☰</span></button>

    <nav class="main-nav" id="main-navigation" data-nav>
      <ul>
        <?php foreach ($nav as $navItem): ?>
          <li><a href="<?= e($navItem['url']) ?>"><?= e($navItem['label']) ?></a></li>
        <?php endforeach; ?>
        <li class="nav-more-item" data-nav-more>
          <?php foreach ($navMore as $navItem): ?>
            <a class="nav-more-link" href="<?= e($navItem['url']) ?>"><?= e($navItem['label']) ?></a>
          <?php endforeach; ?>
        </li>
      </ul>

      <div class="header-actions">
        <div class="lang-switch" aria-label="<?= e(lang() === 'bn' ? 'ভাষা নির্বাচন' : 'Choose language') ?>">
          <a href="<?= e(switch_lang_url('en')) ?>" class="<?= lang()==='en'?'active':'' ?>">EN</a>
          <span aria-hidden="true">|</span>
          <a href="<?= e(switch_lang_url('bn')) ?>" class="<?= lang()==='bn'?'active':'' ?>">বাংলা</a>
        </div>
        <a class="btn btn-ghost header-join" href="<?= e(url('join')) ?>">
          <span class="header-action-icon" aria-hidden="true">+</span>
          <?= e(t('join')) ?>
        </a>
        <a class="btn btn-primary header-donate" href="<?= e(url('donate')) ?>">
          <?= e(t('donate')) ?>
          <span class="header-action-icon" aria-hidden="true">♥</span>
        </a>
      </div>
    </nav>
  </div>
</header>

<main id="main">