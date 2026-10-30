<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title"><?= e(t('donate')) ?></h1>

    <?= flash_html() ?>

    <p class="lead"><?= e(t('home_cta_text')) ?></p>

    <form method="post" class="form">
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <label><?= e(t('name')) ?><input type="text" name="name" required autocomplete="name" maxlength="120"></label>
      <label><?= e(t('email')) ?><input type="email" name="email" autocomplete="email" inputmode="email" maxlength="190"></label>
      <label><?= e(t('phone')) ?><input type="tel" name="phone" autocomplete="tel" inputmode="tel" maxlength="40"></label>
      <label><?= e(t('amount')) ?>
        <input type="number" name="amount" min="100" step="50" value="1000" required inputmode="numeric">
      </label>
      <label><?= e(t('method')) ?>
        <select name="method">
          <option value="bkash">bKash</option>
          <option value="nagad">Nagad</option>
          <option value="rocket">Rocket</option>
          <option value="card">Card / Bank</option>
        </select>
      </label>
      <label>Transaction ID (optional)<input type="text" name="trx_id" maxlength="80"></label>
      <label><?= e(t('message')) ?><textarea name="message" rows="3"></textarea></label>
      <button class="btn btn-primary btn-lg" type="submit"><?= e(t('donate')) ?></button>
    </form>
  </div>
</section>