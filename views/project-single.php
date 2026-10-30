<article class="section">
  <div class="wrap narrow">
    <p class="meta"><a href="<?= e(url('projects')) ?>?year=<?= (int)$project['project_year'] ?>"><?= e(bn_num($project['project_year'])) ?></a></p>
    <h1 class="page-title"><?= e(lf($project, 'title')) ?></h1>
    <?php if (!empty($project['image'])): ?>
      <figure class="article-figure"><?= img_tag($project['image'], lf($project, 'title'), '(max-width: 800px) 100vw, 760px', '', true) ?></figure>
    <?php endif; ?>
    <?php if (field_missing_in_lang($project, 'body')): ?>
      <div class="notice"><?= e(t('not_available')) ?></div>
    <?php endif; ?>
    <div class="prose"><?= lf($project, 'body') ?></div>

    <?php if (!empty($docs)): ?>
      <h2 class="subhead"><?= e(t('related_documents')) ?></h2>
      <?php require APP_ROOT . '/views/partials/doc-list.php'; ?>
    <?php endif; ?>
    <p><a class="btn btn-ghost" href="<?= e(url('projects')) ?>?year=<?= (int)$project['project_year'] ?>">← <?= e(t('nav_projects')) ?></a></p>
  </div>
</article>
