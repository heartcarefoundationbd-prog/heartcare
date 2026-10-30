<?php
$youtubeId = setting('video_youtube_id');
?>
<section class="section video-section">
  <div class="wrap">
    <div class="video-header center">
      <span class="eyebrow"><?= e(t('video_eyebrow')) ?></span>
      <h2 class="section-title video-heading"><?= e(t('video_title')) ?></h2>
    </div>

    <div class="video-container">
      <div class="video-player-wrap" id="videoWrapper" data-youtube-id="<?= e($youtubeId) ?>">
        <div class="video-poster">
          <div class="video-overlay">
            <button class="video-play-btn" id="playVideoBtn" aria-label="<?= e(t('video_play')) ?>">
              <span class="play-icon">▶</span>
            </button>
            <p class="video-caption">
              <?= e(lang() === 'bn' 
                  ? 'হার্ট কেয়ার ফাউন্ডেশন কুমিল্লার তৃণমূল পর্যায়ের স্বাস্থ্যসেবা ও ক্যাম্পেইনের ভিডিও' 
                  : 'Heart Care Foundation Comilla — Community screening & outreach in action') ?>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
