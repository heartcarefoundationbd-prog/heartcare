<?php
$items = publications(4);
?>
<section class="section section-topics">
  <div class="wrap">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?= e(t('health_topics')) ?></span>
        <h2 class="section-title"><?= e(t('all_topics')) ?></h2>
      </div>
      <a class="link-more" href="<?= e(url('publications')) ?>"><?= e(t('view_all_publications')) ?> →</a>
    </div>
    <?php require APP_ROOT . '/views/partials/publication-cards.php'; ?>
  </div>
</section>
