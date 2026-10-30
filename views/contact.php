<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title">Contact</h1>
    <?= flash_html() ?>

    <p><strong><?= e(lang()==='bn' ? setting('address_bn') : setting('address_en')) ?></strong><br>
       <a href="tel:<?= e(setting('phone')) ?>"><?= e(setting('phone')) ?></a> ·
       <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>

    <form method="post" class="form">
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <label><?= e(t('name')) ?><input type="text" name="name" required autocomplete="name" maxlength="120"></label>
      <label><?= e(t('email')) ?><input type="email" name="email" autocomplete="email" inputmode="email" maxlength="190"></label>
      <label><?= e(t('phone')) ?><input type="tel" name="phone" autocomplete="tel" inputmode="tel" maxlength="40"></label>
      <label>Subject<input type="text" name="subject" maxlength="190"></label>
      <label><?= e(t('message')) ?><textarea name="message" rows="5" required maxlength="4000"></textarea></label>
      <button class="btn btn-primary" type="submit"><?= e(t('send')) ?></button>
    </form>

    <div class="map">
      <iframe src="https://www.google.com/maps?q=Cumilla,Bangladesh&output=embed"
              width="100%" height="320" style="border:0" loading="lazy"
              referrerpolicy="no-referrer-when-downgrade" title="Map"></iframe>
    </div>
  </div>
</section>