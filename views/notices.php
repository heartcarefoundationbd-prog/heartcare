<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title"><?= e(t('nav_notices')) ?></h1>

    <?php if (empty($items)): ?>
      <p class="empty-state"><?= e(t('no_notices')) ?></p>
    <?php else: ?>
      <ul class="notice-list">
        <?php foreach ($items as $n): ?>
          <li class="notice-item<?= !empty($n['is_pinned']) ? ' is-pinned' : '' ?>">
            <a class="notice-link" href="<?= e(url('notices/' . lf($n, 'slug'))) ?>">
              <span class="notice-date"><?= e(format_date($n['notice_date'])) ?></span>
              <?php if (!empty($n['is_pinned'])): ?><span class="chip"><?= e(t('pinned')) ?></span><?php endif; ?>
              <h2 class="notice-title"><?= e(lf($n, 'title')) ?></h2>
            </a>
            <?php if (!empty($n['attachment'])): ?>
              <a class="file-link" href="<?= e(asset($n['attachment'])) ?>" target="_blank" rel="noopener">
                <span aria-hidden="true">📎</span> <?= e($n['attachment_name'] ?: t('attachment')) ?>
              </a>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <?php require APP_ROOT . '/views/partials/pagination.php'; ?>
    <?php endif; ?>

    <?php if (!empty($expired)): ?>
      <h2 class="subhead"><?= e(t('expired_notices')) ?></h2>
      <ul class="notice-list is-muted">
        <?php foreach ($expired as $n): ?>
          <li class="notice-item">
            <a class="notice-link" href="<?= e(url('notices/' . lf($n, 'slug'))) ?>">
              <span class="notice-date"><?= e(format_date($n['notice_date'])) ?></span>
              <h3 class="notice-title"><?= e(lf($n, 'title')) ?></h3>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
