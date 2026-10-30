<section class="section observatory-section">
  <div class="wrap observatory-inner">
    <div class="observatory-content">
      <span class="eyebrow"><?= e(t('observatory_eyebrow')) ?></span>
      <h2 class="observatory-title"><?= e(t('observatory_title')) ?></h2>
      <p class="observatory-sub">
        <?= e(lang() === 'bn' 
            ? 'সহজ ও স্পষ্ট ভাষায় প্রতিরোধমূলক স্বাস্থ্য নির্দেশিকা — বাংলা ও ইংরেজিতে।' 
            : 'Clear, doctor-reviewed prevention guides in English and Bangla.') ?>
      </p>
    </div>
    <div class="observatory-action">
      <a class="pill" href="<?= e(url('publications')) ?>">
        <?= e(t('explore_topics')) ?><span class="pill-arrow">›</span>
      </a>
    </div>
  </div>
</section>
