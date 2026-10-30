<section class="section">
  <div class="wrap">
    <h1 class="page-title"><?= e(t('latest_news')) ?></h1>
    <div class="cards-3 news-list-grid">
      <?php foreach ($items as $n): ?>
        <article class="news-card news-list-card" lang="<?= e(in_array($n['content_language'] ?? null, ['en', 'bn'], true) ? $n['content_language'] : lang()) ?>">
          <a href="<?= e(news_url($n)) ?>">
            <div class="card-thumb">
              <?php if (!empty($n['image'])): ?>
                <?= img_tag($n['image'], news_field($n, 'title'), '(max-width: 640px) 90vw, 360px') ?>
              <?php else: ?>
                <div class="news-thumb-fallback" aria-hidden="true">♥</div>
              <?php endif; ?>
            </div>
            <span class="chip"><?= e($n['category']) ?></span>
            <h3><?= e(news_field($n, 'title')) ?></h3>
            <?php if (news_field($n, 'excerpt') !== ''): ?><p><?= e(news_field($n, 'excerpt')) ?></p><?php endif; ?>
            <time datetime="<?= e($n['published_at']) ?>"><?= e(format_date($n['published_at'])) ?></time>
          </a>
        </article>
      <?php endforeach; ?>
    </div>
    <?php if (!$items): ?><p class="empty-state"><?= e(t('no_news')) ?></p><?php endif; ?>
    <?php require APP_ROOT . '/views/partials/pagination.php'; ?>
  </div>
</section>
