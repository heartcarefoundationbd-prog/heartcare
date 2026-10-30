<section class="section">
  <div class="wrap narrow publication-detail">
    <?php if (!empty($publication['cover_image'])):
      $imagePath = (string)$publication['cover_image'];
      if (!str_contains($imagePath, '..') && !str_starts_with($imagePath, '/')): ?>
        <img class="publication-detail-image" src="<?= e(asset($imagePath)) ?>" alt="<?= e(lf($publication, 'title')) ?>" loading="eager">
      <?php endif;
    endif; ?>
    <h1 class="page-title"><?= e(lf($publication, 'title')) ?></h1>
    <?php if (lf($publication, 'subtitle') !== ''): ?><p class="publication-detail-subtitle"><?= e(lf($publication, 'subtitle')) ?></p><?php endif; ?>
    <p class="lead"><?= e(lf($publication, 'summary')) ?></p>
    <?php if (field_missing_in_lang($publication, 'body')): ?><div class="notice"><?= e(t('not_available')) ?></div><?php endif; ?>
    <?php if (lf($publication, 'body') !== ''): ?><div class="prose"><?= lf($publication, 'body') ?></div><?php endif; ?>
    <div class="publication-card-actions publication-detail-actions">
      <?php if ($pdfUrl = publication_pdf_url($publication)): ?>
        <a class="btn btn-primary" href="<?= e($pdfUrl) ?>" target="_blank" rel="noopener"><?= e(t('publication_pdf')) ?></a>
      <?php endif; ?>
      <?php if ($externalUrl = publication_external_url($publication)): ?>
        <a class="btn btn-ghost" href="<?= e($externalUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('publication_external')) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
