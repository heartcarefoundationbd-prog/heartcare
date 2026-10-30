<?php /* expects $docs (rows from documents_list) */ ?>
<ul class="doc-list">
  <?php foreach ($docs as $d): $isPdf = $d['file_ext'] === 'pdf'; ?>
    <li class="doc-item">
      <div class="doc-ext" aria-hidden="true"><?= e(strtoupper((string)$d['file_ext'])) ?></div>
      <div class="doc-main">
        <h3 class="doc-title"><?= e(lf($d, 'title')) ?></h3>
        <p class="doc-meta">
          <?= e(document_category_label((string)$d['category'])) ?>
          <?php if (!empty($d['doc_year'])): ?> · <?= e(bn_num($d['doc_year'])) ?><?php endif; ?>
          · <?= e(bn_num(human_size((int)$d['file_size']))) ?>
        </p>
        <?php if (lf($d, 'description') !== ''): ?><p class="doc-desc"><?= e(lf($d, 'description')) ?></p><?php endif; ?>
      </div>
      <a class="doc-action" href="<?= e(url('documents/file/' . (int)$d['id'])) ?>"
         <?= $isPdf ? 'target="_blank" rel="noopener"' : '' ?>
         aria-label="<?= e(($isPdf ? t('open_file') : t('download')) . ': ' . lf($d, 'title')) ?>">
        <?= e($isPdf ? t('open_file') : t('download')) ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>
