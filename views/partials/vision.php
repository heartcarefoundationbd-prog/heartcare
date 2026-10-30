<?php
$sourceNote = lang() === 'bn' ? setting('source_note_bn') : setting('source_note_en');
?>
<section class="section vision-block">
  <div class="wrap vision-inner">
    <div class="vision-content">
      <span class="eyebrow vision-eyebrow"><?= e(t('vision_eyebrow')) ?></span>
      <h2 class="vision-title"><?= e(t('vision_title')) ?></h2>
      <p class="vision-lead"><?= e(t('vision_body_1')) ?></p>
      <p><?= e(t('vision_body_2')) ?></p>
      
      <div class="vision-stats">
        <div class="vision-stat">
          <span class="v-stat-num"><?= e(t('stat_deaths_val')) ?></span>
          <span class="v-stat-label"><?= e(t('stat_deaths_label')) ?></span>
        </div>
        <div class="vision-stat">
          <span class="v-stat-num"><?= e(t('stat_share_val')) ?></span>
          <span class="v-stat-label"><?= e(t('stat_share_label')) ?></span>
        </div>
        <div class="vision-stat">
          <span class="v-stat-num"><?= e(setting('local_screened') !== '' ? setting('local_screened') : t('stat_screened_val')) ?></span>
          <span class="v-stat-label"><?= e(t('stat_screened_label')) ?></span>
        </div>
      </div>

      <div class="vision-actions">
        <a class="pill" href="<?= e(url('join')) ?>">
          <?= e(t('join_us')) ?><span class="pill-arrow">›</span>
        </a>
      </div>

      <?php if (!empty($sourceNote)): ?>
        <p class="vision-source"><small><?= e($sourceNote) ?></small></p>
      <?php endif; ?>
    </div>
  </div>
</section>
