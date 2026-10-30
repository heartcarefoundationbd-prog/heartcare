<section class="section">
  <div class="wrap">
    <h1 class="page-title"><?= e(t('nav_projects')) ?></h1>

    <?php if (empty($years)): ?>
      <p class="empty-state"><?= e(t('no_projects')) ?></p>
    <?php else: ?>
      <nav class="year-tabs" aria-label="<?= e(t('year')) ?>">
        <?php foreach ($years as $y): ?>
          <a href="?year=<?= (int)$y ?>" class="year-tab<?= $y === $year ? ' active' : '' ?>"
             <?= $y === $year ? 'aria-current="page"' : '' ?>><?= e(bn_num($y)) ?></a>
        <?php endforeach; ?>
      </nav>

      <h2 class="subhead"><?= e(t('projects_in')) ?> <?= e(bn_num($year)) ?></h2>
      <div class="cards-3">
        <?php foreach ($items as $p): ?>
          <article class="news-card project-card">
            <a href="<?= e(url('projects/' . lf($p, 'slug'))) ?>">
              <?php if (!empty($p['image'])): ?>
                <div class="card-thumb"><?= img_tag($p['image'], lf($p, 'title'), '(max-width: 640px) 90vw, 360px') ?></div>
              <?php endif; ?>
              <h3><?= e(lf($p, 'title')) ?></h3>
              <p><?= e(lf($p, 'summary')) ?></p>
            </a>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if (!empty($reports)): ?>
        <h2 class="subhead"><?= e(t('reports_for')) ?> <?= e(bn_num($year)) ?></h2>
        <?php $docs = $reports; require APP_ROOT . '/views/partials/doc-list.php'; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
