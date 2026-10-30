<?php
$stripNotices = notices_active(3);
if (!empty($stripNotices)):
?>
<section class="section notices-strip" aria-label="<?= e(t('latest_notices')) ?>">
  <div class="wrap">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?= e(t('nav_notices')) ?></span>
        <h2 class="section-title"><?= e(t('latest_notices')) ?></h2>
      </div>
      <a class="link-more" href="<?= e(url('notices')) ?>"><?= e(t('all_notices')) ?> →</a>
    </div>
    <ul class="notice-list">
      <?php foreach ($stripNotices as $n): ?>
        <li class="notice-item<?= !empty($n['is_pinned']) ? ' is-pinned' : '' ?>">
          <a class="notice-link" href="<?= e(url('notices/' . lf($n, 'slug'))) ?>">
            <span class="notice-date"><?= e(format_date($n['notice_date'])) ?></span>
            <h3 class="notice-title"><?= e(lf($n, 'title')) ?></h3>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
<?php endif; ?>
