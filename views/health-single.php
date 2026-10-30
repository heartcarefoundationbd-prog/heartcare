<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title"><?= e(lf($topic, 'title')) ?></h1>
    <p class="lead"><?= e(lf($topic, 'summary')) ?></p>
    <?php if (field_missing_in_lang($topic, 'body')): ?>
      <div class="notice"><?= e(t('not_available')) ?></div>
    <?php endif; ?>
    <div class="prose"><?= lf($topic, 'body') ?></div>
    <div class="disclaimer"><?= e(t('medical_note')) ?></div>
  </div>
</section>