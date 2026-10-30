<section class="section">
  <div class="wrap narrow">
    <h1 class="page-title"><?= e(t('nav_documents')) ?></h1>

    <form method="get" class="filter-bar">
      <label><span><?= e(t('category')) ?></span>
        <select name="category">
          <option value=""><?= e(t('all_categories')) ?></option>
          <?php foreach (document_categories() as $k => $labels): ?>
            <option value="<?= e($k) ?>"<?= $cat === $k ? ' selected' : '' ?>><?= e($labels[lang()]) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label><span><?= e(t('year')) ?></span>
        <select name="year">
          <option value=""><?= e(t('all_years')) ?></option>
          <?php foreach ($years as $y): ?>
            <option value="<?= (int)$y ?>"<?= $yr === $y ? ' selected' : '' ?>><?= e(bn_num($y)) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn-primary" type="submit"><?= e(t('filter')) ?></button>
    </form>

    <?php if (empty($items)): ?>
      <p class="empty-state"><?= e(t('no_documents')) ?></p>
    <?php else: ?>
      <?php $docs = $items; require APP_ROOT . '/views/partials/doc-list.php'; ?>
    <?php endif; ?>
  </div>
</section>
