<?php
$eventItems = upcoming_events(3);
if (!empty($eventItems)):
?>
<section class="section section-events">
  <div class="wrap">
    <div class="section-head">
      <div>
        <span class="eyebrow"><?= e(t('upcoming_events')) ?></span>
        <h2 class="section-title"><?= e(lang() === 'bn' ? 'আসন্ন স্বাস্থ্য কর্মসূচি ও দিবস' : 'Upcoming Health Events') ?></h2>
      </div>
    </div>

    <div class="event-carousel" data-slider>
      <div class="event-grid" data-slider-track>
        <?php foreach ($eventItems as $ev): ?>
          <?php
          $eventBody = lf($ev, 'body');
          $eventExcerpt = html_entity_decode(strip_tags($eventBody), ENT_QUOTES | ENT_HTML5, 'UTF-8');
          $eventExcerpt = trim(preg_replace('/\s+/u', ' ', $eventExcerpt) ?? $eventExcerpt);
          if (mb_strlen($eventExcerpt) > 180) $eventExcerpt = rtrim(mb_substr($eventExcerpt, 0, 177)) . '…';
          $relatedUrl = valid_http_url((string)($ev['related_news_url'] ?? '')) ? (string)$ev['related_news_url'] : '';
          $eventUrl = $relatedUrl !== '' ? $relatedUrl : url('events/' . rawurlencode((string)lf($ev, 'slug')));
          ?>
          <article class="event-card" data-slider-item>
            <div class="event-card-head">
              <?php if (!empty($ev['image'])): ?>
                <?= img_tag((string)$ev['image'], lf($ev, 'title'), '(max-width: 768px) 86vw, 360px', 'event-card-image') ?>
              <?php else: ?>
                <span class="event-card-heart" aria-hidden="true">♥</span>
              <?php endif; ?>
              <span class="event-card-arrow" aria-hidden="true">›</span>
            </div>
            <div class="event-card-body">
              <div class="event-card-meta">
                <?php if (!empty($ev['is_health_day'])): ?>
                  <span class="event-tag"><?= e(t('health_day')) ?></span>
                  <span class="meta-sep">·</span>
                <?php endif; ?>
                <time class="event-date" datetime="<?= e((string)$ev['event_date']) ?>"><?= e(format_date((string)$ev['event_date'])) ?></time>
              </div>
              <h3 class="event-title"><?= e(lf($ev, 'title')) ?></h3>
              <?php if (lf($ev, 'location') !== ''): ?><p class="event-loc"><?= e(lf($ev, 'location')) ?></p><?php endif; ?>
              <?php if ($eventExcerpt !== ''): ?><p class="event-description"><?= e($eventExcerpt) ?></p><?php endif; ?>
              <div class="event-card-action">
                <a class="pill pill-sm" href="<?= e($eventUrl) ?>"<?= $relatedUrl !== '' ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                  <?= e(t('read_more')) ?><span class="pill-arrow" aria-hidden="true">›</span>
                </a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php if (count($eventItems) > 1): ?>
        <div class="carousel-dots" data-slider-dots aria-label="<?= e(lang() === 'bn' ? 'অনুষ্ঠান বেছে নিন' : 'Choose an event') ?>">
          <?php foreach ($eventItems as $i => $ev): ?>
            <button type="button" class="<?= $i === 0 ? 'active' : '' ?>" data-slide-to="<?= $i ?>" aria-label="<?= e((lang() === 'bn' ? 'অনুষ্ঠান ' : 'Event ') . ($i + 1)) ?>" aria-current="<?= $i === 0 ? 'true' : 'false' ?>"></button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
