<?php
$featured = featured_cards();
if (!empty($featured)):
?>
<section class="section section-featured">
  <div class="wrap">
    <div class="featured-grid">
      <?php foreach ($featured as $fc): ?>
        <a class="featured-card" href="<?= e(url($fc['link_path'])) ?>">
          <div class="featured-img-wrap">
            <img src="<?= e(asset($fc['image'])) ?>" alt="<?= e(lf($fc, 'caption')) ?>" loading="lazy">
          </div>
          <div class="featured-caption">
            <?= e(lf($fc, 'caption')) ?>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
