<article class="section">
  <div class="wrap narrow">
    <p class="meta"><time datetime="<?= e($event['event_date']) ?>"><?= e(format_date($event['event_date'])) ?></time></p>
    <h1 class="page-title"><?= e(lf($event, 'title')) ?></h1>
    <?php if (lf($event, 'location') !== ''): ?><p class="lead"><?= e(lf($event, 'location')) ?></p><?php endif; ?>
    <?php if (!empty($event['image'])): ?>
      <figure class="article-figure"><?= img_tag($event['image'], lf($event, 'title'), '(max-width: 800px) 100vw, 760px', '', true) ?></figure>
    <?php endif; ?>
    <div class="prose"><?= lf($event, 'body') ?></div>
    <p><a class="btn btn-ghost" href="<?= e(url('events')) ?>">← <?= e(t('upcoming_events')) ?></a></p>
  </div>
</article>
