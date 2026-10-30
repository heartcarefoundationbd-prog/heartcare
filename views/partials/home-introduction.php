<section class="section section-home-intro" aria-labelledby="home-intro-title">
  <div class="wrap">
    <h2 class="section-title home-intro-title" id="home-intro-title"><?= e(t('intro_section_label')) ?></h2>
    <div class="home-intro-grid">
      <article class="home-intro-card">
        <span class="home-intro-icon" aria-hidden="true"><?= home_card_icon('people') ?></span>
        <h3><?= e(t('intro_who_title')) ?></h3>
        <p><?= e(t('intro_who_text')) ?></p>
      </article>
      <article class="home-intro-card">
        <span class="home-intro-icon" aria-hidden="true"><?= home_card_icon('campaign') ?></span>
        <h3><?= e(t('intro_do_title')) ?></h3>
        <p><?= e(t('intro_do_text')) ?></p>
      </article>
      <article class="home-intro-card">
        <span class="home-intro-icon" aria-hidden="true"><?= home_card_icon('heart') ?></span>
        <h3><?= e(t('intro_goal_title')) ?></h3>
        <p><?= e(t('intro_goal_text')) ?></p>
      </article>
    </div>
  </div>
</section>
