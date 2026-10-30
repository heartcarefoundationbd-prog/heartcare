<?php
$cards = home_cards();
if ($cards):
  $cardTypeLabels = [
    'mission' => lang() === 'bn' ? 'মিশন' : 'Mission',
    'goal' => lang() === 'bn' ? 'লক্ষ্য' : 'Goal',
    'program' => lang() === 'bn' ? 'কার্যক্রম' : 'Program',
    'campaign' => lang() === 'bn' ? 'প্রচারণা' : 'Campaign',
    'achievement' => lang() === 'bn' ? 'অর্জন' : 'Achievement',
    'impact_story' => lang() === 'bn' ? 'প্রভাবের গল্প' : 'Impact story',
    'department' => lang() === 'bn' ? 'বিভাগ' : 'Department',
    'health_service' => lang() === 'bn' ? 'স্বাস্থ্যসেবা' : 'Health service',
    'donation' => lang() === 'bn' ? 'সহায়তা' : 'Donation',
    'volunteer' => lang() === 'bn' ? 'স্বেচ্ছাসেবা' : 'Volunteer',
    'research' => lang() === 'bn' ? 'গবেষণা' : 'Research',
  ];
  $allowedThemes = ['light', 'dark', 'brand'];
  $allowedBackgrounds = ['solid', 'gradient', 'pattern', 'image', 'video', 'animated'];
  $allowedAnimations = ['none', 'fade-up', 'float', 'heartbeat', 'reveal'];
  $allowedHovers = ['none', 'elevation', 'glow', 'scale', 'border'];
  $safeColor = static fn($value, $fallback) => is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/D', $value) ? $value : $fallback;
?>
<section class="section section-intro-cards" aria-labelledby="home-cards-title">
  <div class="wrap">
    <div class="home-cards-heading">
      <span class="eyebrow"><?= e(t('home_card_section_eyebrow')) ?></span>
      <h2 class="section-title" id="home-cards-title"><?= e(t('home_card_section_title')) ?></h2>
    </div>
    <div class="intro-cards home-card-grid">
      <div class="home-card-track">
        <?php foreach ($cards as $index => $card):
          $id = (int)$card['id'];
          $media = home_card_media($id) ?? [];
          $style = home_card_style($id) ?? [];
          $motion = home_card_animation($id) ?? [];
          $stats = home_card_stats($id);
          $buttons = home_card_buttons($id);
          if (!$buttons && !empty($card['link_path'])) {
              $buttons = [[
                  'label_en' => t('learn_more'),
                  'label_bn' => t('learn_more'),
                  'url' => $card['link_path'],
                  'button_type' => 'primary',
              ]];
          }
          $theme = in_array($style['theme'] ?? '', $allowedThemes, true) ? $style['theme'] : 'light';
          $background = in_array($style['background_type'] ?? '', $allowedBackgrounds, true) ? $style['background_type'] : 'solid';
          $animation = in_array($motion['animation_type'] ?? '', $allowedAnimations, true) ? $motion['animation_type'] : 'none';
          $hover = in_array($motion['hover_effect'] ?? '', $allowedHovers, true) ? $motion['hover_effect'] : 'elevation';
          $bgColor = $safeColor($style['background_color'] ?? '', '#FFFFFF');
          $gradientStart = $safeColor($style['gradient_start'] ?? '', '#FFFFFF');
          $gradientEnd = $safeColor($style['gradient_end'] ?? '', '#F7F7F8');
          $overlayColor = $safeColor($style['overlay_color'] ?? '', '#1B1B1F');
          $overlayOpacity = min(85, max(0, (int)($style['overlay_opacity'] ?? 30)));
          $visualType = in_array($media['visual_type'] ?? '', ['icon', 'image', 'video'], true) ? $media['visual_type'] : 'image';
          $imagePath = (string)($media['image_path'] ?? $card['image'] ?? '');
          $imageUrl = $imagePath !== '' && !str_contains($imagePath, '..') && !str_starts_with($imagePath, '/') ? asset($imagePath) : '';
          $videoPath = (string)($media['video_path'] ?? '');
          $videoUrl = preg_match('~^assets/uploads/videos/[a-f0-9]{24}\.(mp4|webm)$~D', $videoPath) ? asset($videoPath) : '';
          $posterPath = (string)($media['poster_path'] ?? $imagePath);
          $posterUrl = $posterPath !== '' && !str_contains($posterPath, '..') && !str_starts_with($posterPath, '/') ? asset($posterPath) : '';
          $iconKey = (string)($media['icon_key'] ?? 'heart');
          if (lf($card, 'category') !== '') {
              $eyebrow = lf($card, 'category');
          } else {
              $eyebrow = $cardTypeLabels[$card['content_type'] ?? ''] ?? t('home_card_default_eyebrow');
          }
          $styleVars = '--home-card-color:' . $bgColor
              . ';--home-card-gradient-start:' . $gradientStart
              . ';--home-card-gradient-end:' . $gradientEnd
              . ';--home-card-overlay:' . $overlayColor
              . ';--home-card-overlay-opacity:' . $overlayOpacity . '%'
              . ';--home-card-duration:' . min(2000, max(150, (int)($motion['duration_ms'] ?? 500))) . 'ms'
              . ';--home-card-delay:' . min(1500, max(0, (int)($motion['delay_ms'] ?? 0))) . 'ms';
          $cardClasses = 'intro-card home-card theme-' . $theme . ' background-' . $background
              . ' pattern-' . (($style['pattern_key'] ?? '') === 'dots' ? 'dots' : 'none')
              . ' radius-' . (in_array($style['border_radius'] ?? '', ['small', 'medium', 'large'], true) ? $style['border_radius'] : 'large')
              . ' shadow-' . (in_array($style['shadow'] ?? '', ['none', 'soft', 'strong'], true) ? $style['shadow'] : 'soft')
              . ' spacing-' . (in_array($style['spacing'] ?? '', ['compact', 'comfortable', 'spacious'], true) ? $style['spacing'] : 'comfortable')
              . ' hover-' . $hover . ' motion-' . $animation
              . (!empty($card['is_featured']) ? ' is-featured' : '')
              . (!empty($motion['heartbeat']) ? ' has-heartbeat' : '');
        ?>
        <article class="<?= e($cardClasses) ?>" style="<?= e($styleVars) ?>" data-card-animation="<?= e($animation) ?>" data-animation-trigger="<?= e(($motion['trigger_type'] ?? '') === 'load' ? 'load' : 'scroll') ?>">
          <?php if (!empty($card['is_featured'])): ?><span class="home-card-featured"><?= e(t('home_card_featured')) ?></span><?php endif; ?>
          <?php if ($visualType === 'video' && $videoUrl !== ''): ?>
            <div class="home-card-visual home-card-video" aria-hidden="true">
              <video muted playsinline loop preload="none"<?= $posterUrl !== '' ? ' poster="' . e($posterUrl) . '"' : '' ?> data-card-video>
                <source src="<?= e($videoUrl) ?>" type="<?= str_ends_with($videoUrl, '.webm') ? 'video/webm' : 'video/mp4' ?>">
              </video>
            </div>
          <?php elseif ($visualType === 'image' && $imageUrl !== ''): ?>
            <div class="home-card-visual">
              <img src="<?= e($imageUrl) ?>" alt="<?= e(lf($card, 'title')) ?>" loading="lazy" decoding="async">
            </div>
          <?php else: ?>
            <div class="home-card-icon-wrap"><?= home_card_icon($iconKey) ?></div>
          <?php endif; ?>

          <div class="home-card-content">
            <span class="home-card-eyebrow"><?= e($eyebrow) ?></span>
            <h3><?= e(lf($card, 'title')) ?></h3>
            <?php if (lf($card, 'subtitle') !== ''): ?><p class="home-card-subtitle"><?= e(lf($card, 'subtitle')) ?></p><?php endif; ?>
            <?php if (lf($card, 'body') !== ''): ?><p class="home-card-description"><?= e(lf($card, 'body')) ?></p><?php endif; ?>

            <?php if ($stats): ?>
              <ul class="home-card-stats" aria-label="<?= e(t('home_card_statistics')) ?>">
                <?php foreach ($stats as $stat):
                  $value = trim((string)$stat['value']);
                  $counterMatch = [];
                  $counter = !empty($stat['animate_counter']) && preg_match('/^([0-9][0-9,]*)(.*)$/u', $value, $counterMatch);
                  $statColor = in_array($stat['color_key'] ?? '', ['brand', 'teal', 'dark'], true) ? $stat['color_key'] : 'brand';
                ?>
                  <li class="home-card-stat">
                    <span class="home-card-stat-icon color-<?= e($statColor) ?>"><?= home_card_icon((string)($stat['icon_key'] ?? 'heart')) ?></span>
                    <span class="home-card-stat-value<?= $counter ? ' is-counter' : '' ?>"<?= $counter ? ' data-counter-target="' . e(str_replace(',', '', $counterMatch[1])) . '" data-counter-suffix="' . e($counterMatch[2]) . '"' : '' ?> aria-label="<?= e($value) ?>"><?= e($value) ?></span>
                    <span class="home-card-stat-label"><?= e(lf($stat, 'label')) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if (trim((string)($card['tags'] ?? '')) !== ''): ?>
              <ul class="home-card-tags" aria-label="<?= e(t('home_card_tags')) ?>">
                <?php foreach (array_slice(array_filter(array_map('trim', explode(',', (string)$card['tags']))), 0, 8) as $tag): ?>
                  <li><?= e($tag) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if ($buttons): ?>
              <div class="home-card-actions">
                <?php foreach ($buttons as $button):
                  $buttonUrl = home_card_url((string)$button['url']);
                  if ($buttonUrl === null) continue;
                  $external = valid_http_url((string)$button['url']);
                  $buttonType = in_array($button['button_type'] ?? '', ['primary', 'secondary', 'donation', 'volunteer'], true) ? $button['button_type'] : 'primary';
                ?>
                  <a class="home-card-button button-<?= e($buttonType) ?>" href="<?= e($buttonUrl) ?>"<?= $external ? ' target="_blank" rel="noopener noreferrer"' : '' ?>>
                    <?= e(lf($button, 'label')) ?><span aria-hidden="true">→</span>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
