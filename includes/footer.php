</main>

<?php $socialLinks = social_links(); ?>
<?php
$partners = [
    ['name' => 'Rotary International', 'image' => 'rotary-international.svg', 'url' => 'https://www.rotary.org/en', 'class' => 'partner-logo-rotary'],
    ['name' => 'International Inner Wheel', 'image' => 'international-inner-wheel.png', 'url' => 'https://www.internationalinnerwheel.org/', 'class' => 'partner-logo-square'],
    ['name' => 'American College of Cardiology', 'image' => 'american-college-of-cardiology.svg', 'url' => 'https://www.acc.org/', 'class' => ''],
    ['name' => 'European Society of Cardiology', 'image' => 'european-society-of-cardiology.webp', 'url' => 'https://www.escardio.org/', 'class' => ''],
    ['name' => 'World Health Organization', 'image' => 'world-health-organization.svg', 'url' => 'https://www.who.int/', 'class' => ''],
    ['name' => 'End Polio Now', 'image' => 'end-polio-now.jpg', 'url' => 'https://www.endpolio.org/', 'class' => 'partner-logo-square'],
];
?>
<footer class="site-footer">
  <section class="footer-partners" aria-label="<?= e(lang() === 'bn' ? 'আমাদের অংশীদার' : 'Our partners') ?>">
    <div class="wrap footer-partners-heading">
      <h2><?= e(lang() === 'bn' ? 'আমাদের অংশীদার' : 'Our partners') ?></h2>
    </div>
    <div class="partner-carousel" role="region" aria-label="<?= e(lang() === 'bn' ? 'অংশীদার প্রতিষ্ঠানের লোগো' : 'Partner organization logos') ?>">
      <div class="partner-track">
        <div class="partner-group">
          <?php foreach ($partners as $partner): ?>
            <a class="partner-logo-card <?= e($partner['class']) ?>" href="<?= e($partner['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($partner['name']) ?>">
              <img src="<?= e(asset('public/images/partners/' . $partner['image'])) ?>" alt="<?= e($partner['name']) ?>" loading="lazy">
            </a>
          <?php endforeach; ?>
        </div>
        <div class="partner-group partner-group-clone" aria-hidden="true">
          <?php foreach ($partners as $partner): ?>
            <span class="partner-logo-card <?= e($partner['class']) ?>">
              <img src="<?= e(asset('public/images/partners/' . $partner['image'])) ?>" alt="" loading="lazy">
            </span>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <div class="wrap footer-grid">
    <div class="footer-brand">
      <div class="brand brand-light">
        <span class="brand-mark">♥</span>
        <span class="brand-text"><?= e(lang()==='bn' ? setting('site_name_bn') : setting('site_name_en')) ?></span>
      </div>
      <p><?= e(lang()==='bn' ? setting('address_bn') : setting('address_en')) ?></p>
      <p><a href="tel:<?= e(setting('phone')) ?>"><?= e(setting('phone')) ?></a><br>
         <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></p>
      <?php if ($socialLinks): ?>
      <nav class="socials" aria-label="<?= e(lang() === 'bn' ? 'সামাজিক যোগাযোগমাধ্যম' : 'Social media') ?>">
        <?php foreach ($socialLinks as $social): ?>
          <a class="social-icon-link" href="<?= e($social['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($social['label']) ?>" title="<?= e($social['label']) ?>">
            <?= e($social['label']) ?>
          </a>
        <?php endforeach; ?>
      </nav>
      <?php endif; ?>
    </div>

    <details class="footer-col footer-accordion" open>
      <summary><?= e(t('nav_mission')) ?></summary>
      <ul>
        <li><a href="<?= e(url('team')) ?>"><?= e(lang()==='bn'?'আমাদের কমিউনিটি':'Our Community') ?></a></li>
        <li><a href="<?= e(url('mission')) ?>"><?= e(t('nav_mission')) ?></a></li>
        <li><a href="<?= e(url('our-impact.php')) ?>"><?= e(t('nav_impact')) ?></a></li>
        <li><a href="<?= e(url('about')) ?>"><?= e(lang()==='bn'?'নেতৃত্ব':'Our Leadership') ?></a></li>
      </ul>
    </details>

    <details class="footer-col footer-accordion" open>
      <summary><?= e(t('nav_health')) ?></summary>
      <ul>
        <?php foreach (publications(4) as $tp): ?>
          <li><a href="<?= e(url('publications/' . lf($tp, 'slug'))) ?>"><?= e(lf($tp, 'title')) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </details>

    <details class="footer-col footer-accordion" open>
      <summary><?= e(t('nav_news')) ?></summary>
      <ul>
        <li><a href="<?= e(url('news')) ?>"><?= e(t('latest_news')) ?></a></li>
        <li><a href="<?= e(url('events')) ?>"><?= e(t('upcoming_events')) ?></a></li>
        <li><a href="<?= e(url('notices')) ?>"><?= e(t('nav_notices')) ?></a></li>
        <li><a href="<?= e(url('projects')) ?>"><?= e(t('nav_projects')) ?></a></li>
        <li><a href="<?= e(url('documents')) ?>"><?= e(t('nav_documents')) ?></a></li>
        <li><a href="<?= e(url('gallery')) ?>"><?= e(t('nav_gallery')) ?></a></li>
      </ul>
    </details>

    <details class="footer-col footer-accordion" open>
      <summary><?= e(t('get_involved')) ?></summary>
      <ul>
        <li><a href="<?= e(url('donate')) ?>"><?= e(t('donate')) ?></a></li>
        <li><a href="<?= e(url('join')) ?>"><?= e(t('join')) ?></a></li>
        <li><a href="<?= e(url('contact')) ?>">Contact</a></li>
        <li><a href="<?= e(url('privacy')) ?>"><?= e(lang()==='bn'?'গোপনীয়তা নীতি':'Privacy Policy') ?></a></li>
      </ul>
    </details>
  </div>

  <div class="wrap footer-bottom">
    <p>© <?= date('Y') ?> <?= e(lang()==='bn' ? setting('site_name_bn') : setting('site_name_en')) ?>.
       <?= e(lang()==='bn'?'সর্বস্বত্ব সংরক্ষিত।':'All rights reserved.') ?></p>
    <p class="whf-badge"><?= e(lang()==='bn'?'ওয়ার্ল্ড হার্ট ফেডারেশনের সদস্য':'Proud member of the World Heart Federation') ?></p>
  </div>
</footer>

<a class="whatsapp-fab" href="https://wa.me/<?= e(preg_replace('/\D/','',setting('phone'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp">✆</a>

<?php if (setting('pwa_enabled') === '1'): ?>
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', function () {
    navigator.serviceWorker.register('<?= e(asset('sw.js')) ?>').catch(function () {});
  });
}
</script>
<?php endif; ?>
<script src="<?= e(asset('assets/js/main.js')) ?>?v=<?= e((string)filemtime(APP_ROOT . '/assets/js/main.js')) ?>" defer></script>
</body>
</html>