<article class="section" lang="<?= e(in_array($item['content_language'] ?? null, ['en', 'bn'], true) ? $item['content_language'] : lang()) ?>">
  <div class="wrap narrow">
    <span class="chip"><?= e($item['category']) ?></span>
    <h1 class="page-title"><?= e(news_field($item, 'title')) ?></h1>
    <p class="meta"><time datetime="<?= e($item['published_at']) ?>"><?= e(format_date($item['published_at'])) ?></time></p>
    <?php if (!empty($item['image'])): ?>
      <figure class="article-figure"><?= img_tag($item['image'], news_field($item, 'title'), '(max-width: 800px) 100vw, 760px', '', true) ?></figure>
    <?php endif; ?>
    <?php if (empty($item['content_language']) && field_missing_in_lang($item, 'body')): ?>
      <div class="notice"><?= e(t('not_available')) ?></div>
    <?php endif; ?>
    <div class="prose"><?= news_field($item, 'body') ?></div>
    <?php if (!empty($images)): ?>
      <div class="article-image-gallery">
        <?php foreach ($images as $image): ?>
          <figure><?= img_tag($image['image_path'], news_field($item, 'title'), '(max-width: 640px) 90vw, 360px') ?></figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!empty($tags)): ?>
      <ul class="article-tags" aria-label="<?= e(lang() === 'bn' ? 'ট্যাগ' : 'Tags') ?>">
        <?php foreach ($tags as $tag): ?><li><?= e($tag['name']) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p><a class="btn btn-ghost" href="<?= e(url('news')) ?>">← <?= e(t('latest_news')) ?></a></p>
  </div>
</article>
