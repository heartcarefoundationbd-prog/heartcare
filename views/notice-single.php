<article class="section">
  <div class="wrap narrow">
    <p class="meta">
      <?= e(t('posted_on')) ?> <?= e(format_date($notice['notice_date'])) ?>
      <?php if (!empty($notice['expires_at'])): ?> · <?= e(t('valid_until')) ?> <?= e(format_date($notice['expires_at'])) ?><?php endif; ?>
    </p>
    <h1 class="page-title"><?= e(lf($notice, 'title')) ?></h1>
    <?php if (field_missing_in_lang($notice, 'body')): ?>
      <div class="notice"><?= e(t('not_available')) ?></div>
    <?php endif; ?>
    <div class="prose"><?= lf($notice, 'body') ?></div>
    <?php if (!empty($notice['attachment'])): ?>
      <p><a class="pill" href="<?= e(asset($notice['attachment'])) ?>" target="_blank" rel="noopener">
        <?= e($notice['attachment_name'] ?: t('attachment')) ?><span class="pill-arrow" aria-hidden="true">›</span></a></p>
    <?php endif; ?>
    <p><a class="btn btn-ghost" href="<?= e(url('notices')) ?>">← <?= e(t('nav_notices')) ?></a></p>
  </div>
</article>
