<!-- 1. Hero Section -->
<section class="hero hero-home">
  <div class="wrap hero-inner">
    <span class="eyebrow hero-eyebrow"><?= e(t('hero_kicker')) ?></span>
    <h1 class="hero-title"><?= e(t('tagline')) ?></h1>
    <p class="hero-sub">
      <?= e(lang() === 'bn' 
          ? 'সচেতনতা বৃদ্ধি, বিনামূল্যে স্ক্রিনিং ক্যাম্প এবং তৃণমূল পর্যায়ে কমিউনিটি স্বাস্থ্যসেবা।' 
          : 'Awareness, free screening camps, and community heart healthcare across Cumilla.') ?>
    </p>
    <div class="hero-actions">
      <a class="pill" href="<?= e(url('donate')) ?>">
        <?= e(t('donate')) ?><span class="pill-arrow">›</span>
      </a>
      <a class="pill pill-outline" href="<?= e(url('publications')) ?>">
        <?= e(t('learn_more')) ?><span class="pill-arrow">›</span>
      </a>
    </div>
  </div>
</section>

<!-- The approved bilingual foundation introduction remains visible alongside CMS cards. -->
<?php require APP_ROOT . '/views/partials/home-introduction.php'; ?>

<!-- 2. Admin-managed homepage cards -->
<?php require APP_ROOT . '/views/partials/home-cards.php'; ?>

<!-- 2b. Latest notices (only shown when notices exist) -->
<?php require APP_ROOT . '/views/partials/notices-strip.php'; ?>

<!-- 3. Vision 2030 + Stats Block -->
<?php require APP_ROOT . '/views/partials/vision.php'; ?>

<!-- 4. Featured Cards (3 Cards) -->
<?php require APP_ROOT . '/views/partials/featured.php'; ?>

<!-- 5. Upcoming Events / Health Days -->
<?php require APP_ROOT . '/views/partials/events.php'; ?>

<!-- 6. Latest News + Swipe Carousel -->
<?php require APP_ROOT . '/views/partials/news-carousel.php'; ?>

<!-- 7. Leadership Quote -->
<?php require APP_ROOT . '/views/partials/leadership.php'; ?>

<!-- 8. Video Block -->
<?php require APP_ROOT . '/views/partials/video.php'; ?>

<!-- 8b. Photo and video gallery directly follows the community video. -->
<?php require APP_ROOT . '/views/partials/gallery-cta.php'; ?>

<!-- 9. Observatory / Knowledge Base Strip -->
<?php require APP_ROOT . '/views/partials/observatory.php'; ?>

<!-- 10. Topics Image Grid -->
<?php require APP_ROOT . '/views/partials/topics-grid.php'; ?>

<!-- 11. Donate CTA Band -->
<section class="cta-band">
  <div class="wrap cta-inner">
    <div class="cta-text">
      <h2><?= e(t('home_cta_title')) ?></h2>
      <p><?= e(t('home_cta_text')) ?></p>
    </div>
    <div class="cta-actions">
      <a class="pill" href="<?= e(url('donate')) ?>">
        <?= e(t('donate')) ?><span class="pill-arrow">›</span>
      </a>
      <a class="pill pill-outline" href="<?= e(url('join')) ?>">
        <?= e(t('join_us')) ?><span class="pill-arrow">›</span>
      </a>
    </div>
  </div>
</section>

<!-- 12. Newsletter Strip -->
<section class="section section-newsletter">
  <div class="wrap newsletter">
    <span class="eyebrow"><?= e(t('newsletter')) ?></span>
    <h2 class="section-title"><?= e(lang() === 'bn' ? 'আমাদের সংবাদপত্রের সাথে যুক্ত থাকুন' : 'Subscribe to our updates') ?></h2>
    <?= flash_html() ?>
    <form method="post" action="<?= e(url('contact')) ?>" class="newsletter-form">
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
      <input type="hidden" name="form" value="newsletter">
      <input type="email" name="email" required autocomplete="email" inputmode="email" placeholder="<?= e(t('email')) ?>" aria-label="<?= e(t('email')) ?>">
      <button class="pill" type="submit">
        <?= e(t('subscribe')) ?><span class="pill-arrow">›</span>
      </button>
    </form>
  </div>
</section>
