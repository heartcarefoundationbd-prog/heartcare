<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title"><?= e(t('join')) ?></h1>
    <?= flash_html() ?>

    <form method="post" class="form">
      <?= csrf_field() ?>
      <div class="hp" aria-hidden="true"><label>Website<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
      <label><?= e(t('name')) ?><input type="text" name="name" required autocomplete="name" maxlength="120"></label>
      <label><?= e(t('email')) ?><input type="email" name="email" autocomplete="email" inputmode="email" maxlength="190"></label>
      <label><?= e(t('phone')) ?><input type="tel" name="phone" autocomplete="tel" inputmode="tel" maxlength="40"></label>
      <label><?= e(lang()==='bn'?'আমি হতে চাই':'I want to be a') ?>
        <select name="type">
          <option value="member"><?= e(lang()==='bn'?'সদস্য':'Member') ?></option>
          <option value="volunteer"><?= e(lang()==='bn'?'স্বেচ্ছাসেবক':'Volunteer') ?></option>
          <option value="partner"><?= e(lang()==='bn'?'অংশীদার':'Partner') ?></option>
        </select>
      </label>
      <label><?= e(t('message')) ?><textarea name="message" rows="4" maxlength="4000"></textarea></label>
      <button class="btn btn-primary" type="submit"><?= e(t('send')) ?></button>
    </form>
  </div>
</section>