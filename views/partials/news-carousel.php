<?php
$newsItems = latest_news(3);
?>
<section class="section section-news">
  <div class="wrap">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?= e(t('latest_news')) ?></span>
        <h2 class="section-title"><?= e(lang() === 'bn' ? 'সাম্প্রতিক খবর ও প্রতিবেদন' : 'Latest News & Stories') ?></h2>
      </div>
    </div>

    <div class="news-carousel-container" data-slider>
      <div class="news-carousel-track" data-slider-track>
        <?php foreach ($newsItems as $n): ?>
          <article class="news-carousel-card" data-slider-item lang="<?= e(in_array($n['content_language'] ?? null, ['en', 'bn'], true) ? $n['content_language'] : lang()) ?>">
            <a class="news-card-inner" href="<?= e(news_url($n)) ?>">
              <div class="news-thumb-wrap">
                <?php if (!empty($n['image'])): ?>
                  <?= img_tag($n['image'], news_field($n, 'title'), '(max-width: 640px) 90vw, 380px') ?>
                <?php else: ?>
                  <div class="news-thumb-fallback">♥</div>
                <?php endif; ?>
              </div>
              <div class="news-card-body">
                <div class="news-meta">
                  <span><?= e(format_date($n['published_at'])) ?></span>
                  <span class="meta-sep">·</span>
                  <?php $newsLanguage = in_array($n['content_language'] ?? null, ['en', 'bn'], true) ? $n['content_language'] : lang(); ?>
                  <span class="news-dept"><?= e($newsLanguage === 'bn' ? ($n['department_bn'] ?: 'যোগাযোগ') : ($n['department_en'] ?: 'Communications')) ?></span>
                </div>
                <h3 class="news-card-title"><?= e(news_field($n, 'title')) ?></h3>
                <?php if (news_field($n, 'excerpt') !== ''): ?>
                  <p class="news-card-excerpt"><?= e(news_field($n, 'excerpt')) ?></p>
                <?php endif; ?>
                <span class="news-readmore"><?= e(t('read_more')) ?> <span aria-hidden="true">›</span></span>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if (count($newsItems) > 1): ?>
      <div class="carousel-dots" data-slider-dots aria-label="<?= e(lang() === 'bn' ? 'খবর বেছে নিন' : 'Choose a news story') ?>">
        <?php for ($i = 0; $i < count($newsItems); $i++): ?>
          <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-slide-to="<?= $i ?>" aria-label="<?= e((lang() === 'bn' ? 'খবর ' : 'News story ') . ($i + 1)) ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>

    <div class="section-cta center" style="margin-top: 36px;">
      <a class="pill" href="<?= e(url('all-news.php')) ?>">
        <?= e(t('view_all_news')) ?><span class="pill-arrow">›</span>
      </a>
    </div>
  </div>
</section>
