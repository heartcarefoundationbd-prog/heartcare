<?php
$lqQuote = setting('leader_quote_' . lang());
$lqQuote = $lqQuote !== '' ? $lqQuote : t('leadership_message');
$lqName  = setting('leader_name_' . lang());
$lqRole  = setting('leader_role_' . lang());
$lqPhoto = setting('leader_photo');
$lqExcerpt = '';
$lqRest = '';
if (lang() === 'en') {
    $expectedExcerpt = 'At Heart Care Foundation Comilla, we are committed to preventing heart disease and stroke through awareness, education, and community engagement.';
    if (str_starts_with($lqQuote, $expectedExcerpt)) {
        $lqExcerpt = $expectedExcerpt;
        $lqRest = trim(mb_substr($lqQuote, mb_strlen($expectedExcerpt)));
    }
}
if ($lqExcerpt === '') {
    $sentenceEnd = mb_strpos($lqQuote, lang() === 'bn' ? '।' : '.');
    if ($sentenceEnd !== false) {
        $excerptLength = $sentenceEnd + 1;
        $lqExcerpt = mb_substr($lqQuote, 0, $excerptLength);
        $lqRest = trim(mb_substr($lqQuote, $excerptLength));
    } else {
        $lqExcerpt = $lqQuote;
    }
}
?>
<section class="section leader-quote-section">
  <div class="wrap">
    <div class="leader-quote-block">
      <div class="leader-photo-wrap">
        <img
          src="<?= e(asset($lqPhoto !== '' ? $lqPhoto : 'public/images/leader-triptish.webp')) ?>"
          alt="<?= e(t('leadership_photo_alt')) ?>"
          width="768"
          height="768"
          loading="lazy"
          decoding="async">
        <span class="leader-photo-caption"><?= e(t('leadership_founder_label')) ?></span>
      </div>
      <div class="leader-quote-content">
        <span class="eyebrow"><?= e(t('leadership_eyebrow')) ?></span>
        <span class="leader-quote-mark" aria-hidden="true">“</span>
        <blockquote class="leader-quote">
          <span><?= e($lqExcerpt) ?></span><?php if ($lqRest !== ''): ?> <span data-leadership-rest><?= e($lqRest) ?></span><?php endif; ?>
        </blockquote>
        <button class="leadership-toggle" type="button" data-leadership-toggle
                data-label-more="<?= e(t('see_more')) ?>" data-label-less="<?= e(t('see_less')) ?>"
                aria-expanded="true" hidden><?= e(t('see_more')) ?></button>
        <div data-leadership-extra>
          <p class="leader-message-thanks"><?= e(t('leadership_thanks')) ?></p>
          <div class="leader-meta">
            <strong class="leader-name"><?= e($lqName !== '' ? $lqName : t('leadership_name')) ?></strong>
            <span class="leader-role"><?= e($lqRole !== '' ? $lqRole : t('leadership_role')) ?></span>
            <span class="leader-organization"><?= e(t('leadership_organization')) ?></span>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
