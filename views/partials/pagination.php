<?php
/* expects $pg = paginate(...) and optional $pgExtra (query string without page) */
if (!empty($pg) && $pg['pages'] > 1):
    $extra = !empty($pgExtra) ? $pgExtra . '&' : '';
    $link = fn(int $p) => '?' . $extra . 'page=' . $p;
?>
<nav class="pager" aria-label="<?= e(t('page')) ?>">
  <?php if ($pg['page'] > 1): ?>
    <a class="pager-btn" rel="prev" href="<?= e($link($pg['page'] - 1)) ?>">‹ <?= e(t('previous')) ?></a>
  <?php endif; ?>
  <span class="pager-info"><?= e(t('page')) ?> <?= e(bn_num($pg['page'])) ?> / <?= e(bn_num($pg['pages'])) ?></span>
  <?php if ($pg['page'] < $pg['pages']): ?>
    <a class="pager-btn" rel="next" href="<?= e($link($pg['page'] + 1)) ?>"><?= e(t('next')) ?> ›</a>
  <?php endif; ?>
</nav>
<?php endif; ?>
