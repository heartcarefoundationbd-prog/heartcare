<section class="section">
  <div class="wrap">
    <h1 class="page-title"><?= e(t('upcoming_events')) ?></h1>
    <?php if (empty($items)): ?>
      <p class="empty-state"><?= e(t('no_events')) ?></p>
    <?php else: ?>
    <ul class="event-list">
      <?php foreach ($items as $ev): [$d,$m] = day_badge($ev['event_date']); ?>
        <li class="event-row">
          <div class="event-badge"><span><?= e($d) ?></span><small><?= e($m) ?></small></div>
          <div class="event-body">
            <h3><a href="<?= e(url('events/' . rawurlencode(lf($ev, 'slug')))) ?>"><?= e(lf($ev, 'title')) ?></a></h3>
            <p><?= e(lf($ev, 'location')) ?></p>
            <?php if (!empty($ev['image'])): ?>
              <div class="card-thumb"><?= img_tag($ev['image'], lf($ev, 'title'), '(max-width: 640px) 90vw, 480px') ?></div>
            <?php endif; ?>
            <div class="prose"><?= lf($ev, 'body') ?></div>
          </div>
        </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>

    <?php if (!empty($past)): ?>
      <h2 class="subhead"><?= e(t('past_events')) ?></h2>
      <ul class="event-list is-past">
        <?php foreach ($past as $ev): [$d,$m] = day_badge($ev['event_date']); ?>
          <li class="event-row">
            <div class="event-badge"><span><?= e($d) ?></span><small><?= e($m) ?></small></div>
            <div class="event-body">
              <h3><a href="<?= e(url('events/' . rawurlencode(lf($ev, 'slug')))) ?>"><?= e(lf($ev, 'title')) ?></a></h3>
              <p><?= e(lf($ev, 'location')) ?> · <?= e(format_date($ev['event_date'])) ?></p>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
