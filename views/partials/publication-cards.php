<div class="publication-grid">
  <?php foreach (($items ?? []) as $publication):
    $slug = lf($publication, 'slug');
    $detailUrl = url('publications/' . rawurlencode($slug));
    $imagePath = (string)($publication['cover_image'] ?? '');
    $imageUrl = $imagePath !== '' && !str_contains($imagePath, '..') && !str_starts_with($imagePath, '/') ? asset($imagePath) : '';
    $pdfUrl = publication_pdf_url($publication);
    $externalUrl = publication_external_url($publication);
  ?>
    <article class="publication-card">
      <a class="publication-card-image" href="<?= e($detailUrl) ?>" aria-label="<?= e(lf($publication, 'title')) ?>">
        <?php if ($imageUrl !== ''): ?>
          <img src="<?= e($imageUrl) ?>" alt="" loading="lazy" decoding="async">
        <?php else: ?>
          <span class="publication-card-placeholder" aria-hidden="true">▤</span>
        <?php endif; ?>
      </a>
      <div class="publication-card-content">
        <h3><a href="<?= e($detailUrl) ?>"><?= e(lf($publication, 'title')) ?></a></h3>
        <?php if (lf($publication, 'subtitle') !== ''): ?>
          <p class="publication-card-subtitle"><?= e(lf($publication, 'subtitle')) ?></p>
        <?php endif; ?>
        <?php if (lf($publication, 'summary') !== ''): ?>
          <p class="publication-card-summary"><?= e(lf($publication, 'summary')) ?></p>
        <?php endif; ?>
        <?php if ($pdfUrl || $externalUrl): ?>
          <div class="publication-card-actions">
            <?php if ($pdfUrl): ?>
              <a class="btn btn-primary" href="<?= e($pdfUrl) ?>" target="_blank" rel="noopener"><?= e(t('publication_pdf')) ?></a>
            <?php endif; ?>
            <?php if ($externalUrl): ?>
              <a class="btn btn-ghost" href="<?= e($externalUrl) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('publication_external')) ?></a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </article>
  <?php endforeach; ?>
</div>
