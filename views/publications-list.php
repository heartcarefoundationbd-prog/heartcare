<section class="section">
  <div class="wrap">
    <h1 class="page-title"><?= e(t('nav_health')) ?></h1>
    <p class="lead"><?= e(t('explore_topics')) ?></p>
    <?php $items = $publications ?? []; require APP_ROOT . '/views/partials/publication-cards.php'; ?>
  </div>
</section>
