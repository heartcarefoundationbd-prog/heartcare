<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title"><?= e(lf($page, 'title')) ?></h1>
    <?php if (field_missing_in_lang($page, 'title')): ?>
      <div class="notice"><?= e(t('not_available')) ?></div>
    <?php endif; ?>
    <div class="prose"><?= lf($page, 'body') ?></div>
  </div>
</section>