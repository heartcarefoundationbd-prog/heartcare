<?php
$categories = gallery_categories();
$activeCategory = isset($categories[$category ?? '']) ? (string)$category : '';
$videos = [];
$albums = [];
foreach ($items as $item) {
    if (($item['media_type'] ?? 'photo') === 'video') {
        $youtubeId = youtube_video_id((string)($item['youtube_id'] ?? ''));
        if ($youtubeId !== null) $videos[] = [$item, $youtubeId];
        continue;
    }
    if (empty($item['image'])) continue;
    $albumTitle = lf($item, 'album');
    $albumKey = $albumTitle !== '' ? $albumTitle : t('gallery_photos');
    $albums[$albumKey][] = $item;
}
?>
<section class="section gallery-page">
  <div class="wrap">
    <header class="gallery-page-header">
      <span class="eyebrow"><?= e(t('gallery_collection')) ?></span>
      <h1 class="page-title"><?= e(t('gallery_title')) ?></h1>
      <p class="gallery-page-subtitle"><?= e(t('gallery_subtitle')) ?></p>
    </header>

    <nav class="gallery-category-filter" aria-label="<?= e(t('category')) ?>">
      <a class="<?= $activeCategory === '' ? 'is-active' : '' ?>" href="<?= e(url('gallery')) ?>"<?= $activeCategory === '' ? ' aria-current="page"' : '' ?>><?= e(t('gallery_all')) ?></a>
      <?php foreach ($categories as $key => $label): ?>
        <a class="<?= $activeCategory === $key ? 'is-active' : '' ?>" href="<?= e(url('gallery') . '?category=' . rawurlencode($key)) ?>"<?= $activeCategory === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>

    <?php if (!$videos && !$albums): ?>
      <p class="empty-state"><?= e(t('no_photos')) ?></p>
    <?php endif; ?>

    <?php if ($videos): ?>
      <section class="gallery-media-section" aria-labelledby="gallery-videos-title">
        <h2 class="gallery-section-title" id="gallery-videos-title"><?= e(t('gallery_videos')) ?></h2>
        <div class="gallery-video-grid">
          <?php foreach ($videos as [$item, $youtubeId]):
            $title = lf($item, 'title');
            $categoryLabel = $categories[$item['category'] ?? ''] ?? '';
          ?>
            <article class="gallery-video-card">
              <div class="gallery-video-player" data-gallery-player data-video-id="<?= e($youtubeId) ?>" data-video-title="<?= e($title) ?>">
                <button class="gallery-video-trigger" type="button" aria-label="<?= e(sprintf(t('gallery_play'), $title)) ?>">
                  <img src="<?= e('https://i.ytimg.com/vi/' . rawurlencode($youtubeId) . '/hqdefault.jpg') ?>" alt="" loading="lazy" decoding="async">
                  <span class="gallery-video-play" aria-hidden="true">▶</span>
                </button>
              </div>
              <div class="gallery-video-content">
                <?php if ($categoryLabel !== ''): ?><span class="gallery-category-label"><?= e($categoryLabel) ?></span><?php endif; ?>
                <h3><?= e($title) ?></h3>
                <a class="gallery-watch-link" href="<?= e('https://www.youtube.com/watch?v=' . rawurlencode($youtubeId)) ?>" target="_blank" rel="noopener noreferrer"><?= e(t('gallery_watch_youtube')) ?> <span aria-hidden="true">↗</span></a>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <?php foreach ($albums as $albumTitle => $photos): ?>
      <section class="gallery-media-section" aria-labelledby="gallery-album-<?= (int)$photos[0]['id'] ?>">
        <h2 class="gallery-section-title" id="gallery-album-<?= (int)$photos[0]['id'] ?>"><?= e($albumTitle) ?></h2>
        <ul class="gallery-grid">
          <?php foreach ($photos as $photo):
            $caption = lf($photo, 'title');
            $categoryLabel = $categories[$photo['category'] ?? ''] ?? '';
          ?>
            <li class="gallery-photo-card">
              <a class="gallery-link" href="<?= e(asset(img_variant((string)$photo['image'], ''))) ?>" data-lightbox
                 data-caption="<?= e($caption) ?>" aria-label="<?= e($caption !== '' ? $caption : t('open_file')) ?>">
                <?= img_tag((string)$photo['image'], $caption, '(max-width: 640px) 50vw, 280px') ?>
              </a>
              <div class="gallery-photo-caption">
                <?php if ($categoryLabel !== ''): ?><span class="gallery-category-label"><?= e($categoryLabel) ?></span><?php endif; ?>
                <?php if ($caption !== ''): ?><p class="gallery-cap"><?= e($caption) ?></p><?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endforeach; ?>

    <?php $pgExtra = $activeCategory !== '' ? 'category=' . rawurlencode($activeCategory) : ''; ?>
    <?php require APP_ROOT . '/views/partials/pagination.php'; ?>
  </div>
</section>
<dialog class="lightbox" id="lightbox" aria-label="<?= e(t('nav_gallery')) ?>">
  <button type="button" class="lightbox-close" data-lightbox-close aria-label="<?= e(t('close')) ?>">×</button>
  <img alt="" id="lightbox-img">
  <p id="lightbox-cap"></p>
</dialog>
