<?php
$isBn = lang() === 'bn';
$imageBase = 'public/images/our-impact/';

$stats = $isBn
    ? [
        ['number' => '৪,০০,০০০+', 'label' => 'মানুষের কাছে বিনামূল্যে স্বাস্থ্যসেবা'],
        ['number' => '১০০+', 'label' => 'বিনামূল্যে হার্ট ক্যাম্প'],
        ['number' => '১৪', 'label' => 'প্রকাশিত স্বাস্থ্যবিষয়ক বই'],
        ['number' => '৭', 'label' => 'হৃদস্বাস্থ্য ও হৃদরোগ প্রতিরোধের বই'],
    ]
    : [
        ['number' => '400,000+', 'label' => 'people reached through free healthcare'],
        ['number' => '100+', 'label' => 'free heart camps'],
        ['number' => '14', 'label' => 'published health-awareness books'],
        ['number' => '7', 'label' => 'books focused on heart health'],
    ];

$timeline = $isBn
    ? [
        [
            'years' => '২০০০',
            'title' => 'বিশ্ব হার্ট দিবসের সূচনা',
            'themes' => ['“I Love My Heart, Let It Beat”'],
            'description' => 'ওয়ার্ল্ড হার্ট ফেডারেশনের উদ্যোগে এবং বিশ্ব স্বাস্থ্য সংস্থার সহযোগিতায় বিশ্ব হার্ট দিবসের যাত্রা শুরু হয়।',
        ],
        [
            'years' => '২০০১–২০০২',
            'title' => 'সুস্থ হৃদয়ের জন্য সচেতনতা',
            'themes' => ['“A Heart for Life”'],
            'description' => 'জীবনের প্রতিটি পর্যায়ে হৃদযন্ত্রের সুস্থতা এবং শৈশব থেকেই স্বাস্থ্যকর অভ্যাস গড়ে তোলার গুরুত্ব তুলে ধরা হয়।',
        ],
        [
            'years' => '২০০৩',
            'title' => 'নারীদের হৃদস্বাস্থ্য',
            'themes' => ['“Women, Heart Disease & Stroke”'],
            'description' => 'নারীদের হৃদরোগ ও স্ট্রোকের ঝুঁকি সম্পর্কে সচেতনতা বৃদ্ধিতে গুরুত্ব দেওয়া হয়।',
        ],
        [
            'years' => '২০০৪',
            'title' => 'ফাউন্ডেশনের প্রতিষ্ঠা',
            'themes' => ['“Children, Adolescents and Heart Disease”'],
            'description' => '১ সেপ্টেম্বর অধ্যাপক ডা. তৃপ্তীশ চন্দ্র ঘোষের নেতৃত্বে কুমিল্লায় Heart Care Foundation Comilla প্রতিষ্ঠিত হয়।',
        ],
        [
            'years' => '২০০৫–২০০৭',
            'title' => 'স্বাস্থ্যকর জীবনযাপন',
            'themes' => [
                '২০০৫ — “Healthy Weight, Healthy Shape”',
                '২০০৬ — “How Young Is Your Heart?”',
                '২০০৭ — “Team Up for Healthy Hearts”',
            ],
            'description' => 'স্বাস্থ্যকর ওজন, ব্যায়াম, ধূমপান বর্জন এবং পরিবার ও সমাজের সম্মিলিত উদ্যোগের বার্তা ছড়িয়ে পড়ে।',
        ],
        [
            'years' => '২০০৮–২০১০',
            'title' => 'ঝুঁকি চিহ্নিতকরণ ও নিয়ন্ত্রণ',
            'themes' => [
                '২০০৮ — “Know Your Risk”',
                '২০০৯ — “Work with Heart”',
                '২০১০ — “I Work with Heart”',
            ],
            'description' => 'উচ্চ রক্তচাপ, কোলেস্টেরল ও ডায়াবেটিসের ঝুঁকি এবং কর্মক্ষেত্রে হৃদবান্ধব পরিবেশের গুরুত্ব তুলে ধরা হয়।',
            'images' => [
                ['path' => 'public/images/worldheartdayfeatured.jpg', 'alt' => '২০০৮ সালের Know Your Risk বিশ্ব হার্ট দিবসের প্রচারচিত্র'],
            ],
        ],
        [
            'years' => '২০১১–২০১৫',
            'title' => 'পরিবার ও সমাজভিত্তিক সচেতনতা',
            'themes' => [
                '২০১১–২০১২ — “One World, One Home, One Heart”',
                '২০১৩ — “Take the Road to a Healthy Heart”',
                '২০১৪ — “Heart Choices NOT Hard Choices”',
                '২০১৫ — “Healthy Heart Choices for Everyone, Everywhere”',
            ],
            'description' => 'পরিবার থেকে শুরু করে সবার জন্য, সর্বত্র হৃদস্বাস্থ্য রক্ষায় সচেতন সিদ্ধান্তের আহ্বান জানানো হয়।',
            'images' => [
                ['file' => 'world-heart-day-2011.png', 'alt' => '২০১১ সালের One World, One Home, One Heart বিশ্ব হার্ট দিবসের পোস্টার'],
                ['file' => 'world-heart-day-2013.png', 'alt' => '২০১৩ সালের বিশ্ব হার্ট দিবসের প্রচারপোস্টার'],
            ],
        ],
        [
            'years' => '২০১৬–২০১৯',
            'title' => 'হৃদস্বাস্থ্য রক্ষায় ক্ষমতায়ন',
            'themes' => [
                '২০১৬ — “Power Your Life”',
                '২০১৭ — “Share the Power”',
                '২০১৮ — “My Heart, Your Heart”',
                '২০১৯ — “Be A Heart Hero”',
            ],
            'description' => 'নিজের ও পরিবারের হৃদস্বাস্থ্য রক্ষায় দায়িত্বশীল এবং সচেতন হওয়ার অনুপ্রেরণা দেওয়া হয়।',
        ],
        [
            'years' => '২০২০–২০২২',
            'title' => 'মহামারির মধ্যেও হৃদযত্ন',
            'themes' => [
                '২০২০ — “Use Heart to Beat Cardiovascular Disease”',
                '২০২১ — “Use Heart to Connect”',
                '২০২২ — “Use Heart for Every Heart”',
            ],
            'description' => 'কোভিড-১৯ মহামারির কঠিন সময়েও হৃদরোগ প্রতিরোধ ও সচেতনতার বার্তা অব্যাহত থাকে।',
            'images' => [
                ['file' => 'world-heart-day-2022-community.jpg', 'alt' => 'বিশ্ব হার্ট দিবসের ব্যানারসহ কমিউনিটির বৃক্ষরোপণ কার্যক্রম'],
            ],
        ],
        [
            'years' => '২০২৩',
            'title' => 'আন্তর্জাতিক স্বীকৃতি ও বাংলা ভাষার অর্জন',
            'themes' => ['“Use Heart, Know Heart”'],
            'description' => 'Heart Care Foundation Comilla “Most Inspiring World Heart Day Award 2023” অর্জন করে। ফাউন্ডেশনের উদ্যোগের পর বিশ্ব হার্ট দিবসের যোগাযোগ ও প্রচারণামূলক উপকরণে বাংলা ভাষা অন্তর্ভুক্ত হয়।',
            'images' => [
                ['file' => '2023-world-heart-day-campaign.jpg', 'alt' => 'Use Heart, Know Heart প্রচারণায় অংশগ্রহণকারীরা'],
            ],
        ],
        [
            'years' => '২০২৪',
            'title' => 'সচেতনতা থেকে কার্যকর পদক্ষেপ',
            'themes' => ['“Use Heart for Action”'],
            'description' => 'হৃদরোগ প্রতিরোধে কার্যকর উদ্যোগ, স্বাস্থ্যনীতি উন্নয়ন এবং সরকার ও নীতিনির্ধারকদের সক্রিয় ভূমিকার গুরুত্ব তুলে ধরা হয়।',
        ],
    ]
    : [
        [
            'years' => '2000',
            'title' => 'World Heart Day begins',
            'themes' => ['“I Love My Heart, Let It Beat”'],
            'description' => 'The World Heart Federation, with support from the World Health Organization, began the worldwide World Heart Day movement.',
        ],
        [
            'years' => '2001–2002',
            'title' => 'Awareness for a heart for life',
            'themes' => ['“A Heart for Life”'],
            'description' => 'The campaigns highlighted heart health throughout life and the value of building healthy habits from childhood.',
        ],
        [
            'years' => '2003',
            'title' => 'Women’s heart health',
            'themes' => ['“Women, Heart Disease & Stroke”'],
            'description' => 'The campaign focused attention on women’s risks of heart disease and stroke.',
        ],
        [
            'years' => '2004',
            'title' => 'Heart Care Foundation Comilla is established',
            'themes' => ['“Children, Adolescents and Heart Disease”'],
            'description' => 'On 1 September, Heart Care Foundation Comilla was established in Cumilla, Bangladesh, under the leadership of Professor Dr. Triptish Chandra Ghose.',
        ],
        [
            'years' => '2005–2007',
            'title' => 'Healthy lifestyles and collective action',
            'themes' => [
                '2005 — “Healthy Weight, Healthy Shape”',
                '2006 — “How Young Is Your Heart?”',
                '2007 — “Team Up for Healthy Hearts”',
            ],
            'description' => 'Campaigns promoted healthy weight, exercise, avoiding tobacco, and action by families and communities.',
        ],
        [
            'years' => '2008–2010',
            'title' => 'Identifying and managing risk',
            'themes' => [
                '2008 — “Know Your Risk”',
                '2009 — “Work with Heart”',
                '2010 — “I Work with Heart”',
            ],
            'description' => 'The themes raised awareness of cardiovascular risks—including blood pressure, cholesterol and diabetes—and healthier workplaces.',
            'images' => [
                ['path' => 'public/images/worldheartdayfeatured.jpg', 'alt' => '2008 Know Your Risk World Heart Day campaign image'],
            ],
        ],
        [
            'years' => '2011–2015',
            'title' => 'Family and community awareness',
            'themes' => [
                '2011–2012 — “One World, One Home, One Heart”',
                '2013 — “Take the Road to a Healthy Heart”',
                '2014 — “Heart Choices NOT Hard Choices”',
                '2015 — “Healthy Heart Choices for Everyone, Everywhere”',
            ],
            'description' => 'Campaigns encouraged heart-healthy choices at home and opportunities for everyone, everywhere.',
            'images' => [
                ['file' => 'world-heart-day-2011.png', 'alt' => '2011 One World, One Home, One Heart World Heart Day poster'],
                ['file' => 'world-heart-day-2013.png', 'alt' => '2013 World Heart Day campaign poster'],
            ],
        ],
        [
            'years' => '2016–2019',
            'title' => 'Empowering heart-health champions',
            'themes' => [
                '2016 — “Power Your Life”',
                '2017 — “Share the Power”',
                '2018 — “My Heart, Your Heart”',
                '2019 — “Be A Heart Hero”',
            ],
            'description' => 'These campaigns encouraged people to take responsibility for the heart health of themselves and their families.',
        ],
        [
            'years' => '2020–2022',
            'title' => 'Heart health through the pandemic',
            'themes' => [
                '2020 — “Use Heart to Beat Cardiovascular Disease”',
                '2021 — “Use Heart to Connect”',
                '2022 — “Use Heart for Every Heart”',
            ],
            'description' => 'Heart-health awareness and prevention remained important through the difficult COVID-19 pandemic years.',
            'images' => [
                ['file' => 'world-heart-day-2022-community.jpg', 'alt' => 'Community tree-planting activity with a World Heart Day banner'],
            ],
        ],
        [
            'years' => '2023',
            'title' => 'International recognition and Bengali-language achievement',
            'themes' => ['“Use Heart, Know Heart”'],
            'description' => 'Heart Care Foundation Comilla received the “Most Inspiring World Heart Day Award 2023.” Following the Foundation’s initiative, Bengali was included in World Heart Day communications and campaign materials.',
            'images' => [
                ['file' => '2023-world-heart-day-campaign.jpg', 'alt' => 'Participants at the Use Heart, Know Heart campaign'],
            ],
        ],
        [
            'years' => '2024',
            'title' => 'From awareness to action',
            'themes' => ['“Use Heart for Action”'],
            'description' => 'The theme called for practical action to prevent cardiovascular disease, stronger health policy, and active engagement by governments and decision-makers.',
        ],
    ];

$activities = $isBn
    ? [
        [
            'file' => 'community-cycling-rally.jpg',
            'alt' => 'হৃদস্বাস্থ্য সচেতনতায় সাইকেল র‍্যালি',
            'caption' => 'সাইকেল র‍্যালি ও জনসচেতনতা',
        ],
        [
            'file' => 'world-heart-day-2022-community.jpg',
            'alt' => 'বিশ্ব হার্ট দিবসের ব্যানারসহ কমিউনিটির বৃক্ষরোপণ',
            'caption' => 'কমিউনিটি কার্যক্রম',
        ],
        [
            'file' => 'world-heart-day-archive-banner.png',
            'alt' => 'বিশ্ব হার্ট দিবসের বাংলা ভাষার প্রচার ব্যানার',
            'caption' => 'বিশ্ব হার্ট দিবসের প্রচারচিত্র',
        ],
    ]
    : [
        [
            'file' => 'community-cycling-rally.jpg',
            'alt' => 'Cycling rally promoting heart-health awareness',
            'caption' => 'Cycling rallies and public awareness',
        ],
        [
            'file' => 'world-heart-day-2022-community.jpg',
            'alt' => 'Community tree-planting activity with a World Heart Day banner',
            'caption' => 'Community engagement',
        ],
        [
            'file' => 'world-heart-day-archive-banner.png',
            'alt' => 'Bengali World Heart Day campaign banner',
            'caption' => 'World Heart Day campaign material',
        ],
    ];
?>

<div class="impact-page">
  <section class="impact-hero">
    <img class="impact-hero-image"
         src="<?= e(asset($imageBase . 'community-cycling-rally.jpg')) ?>"
         alt="<?= e($isBn ? 'হৃদস্বাস্থ্য সচেতনতায় কমিউনিটি সাইকেল র‍্যালি' : 'Community cycling rally promoting heart-health awareness') ?>"
         fetchpriority="high">
    <div class="impact-hero-shade"></div>
    <div class="wrap impact-hero-content">
      <p class="impact-eyebrow"><?= e($isBn ? 'Heart Care Foundation Comilla' : 'Heart Care Foundation Comilla') ?></p>
      <h1><?= e($isBn ? 'আমাদের অর্জন ও প্রভাব' : 'Our Impact') ?></h1>
      <p class="impact-hero-subtitle"><?= e($isBn ? 'দুই দশকেরও বেশি সময় ধরে সুস্থ হৃদয়ের প্রত্যয়ে' : 'Two Decades of Inspiring Healthier Hearts') ?></p>
      <p class="impact-hero-copy">
        <?= e($isBn
            ? '১ সেপ্টেম্বর ২০০৪ থেকে হৃদরোগ প্রতিরোধ, সচেতনতা, স্বাস্থ্যশিক্ষা এবং কমিউনিটিভিত্তিক সেবায় আমাদের যাত্রা।'
            : 'Since 1 September 2004, we have worked to prevent heart disease through awareness, health education, and community-based care.') ?>
      </p>
    </div>
  </section>

  <section class="impact-intro section">
    <div class="wrap narrow center">
      <span class="impact-kicker"><?= e($isBn ? 'আমাদের পথচলা' : 'Our journey') ?></span>
      <h2><?= e($isBn ? 'সুস্থ হৃদয়ের জন্য একসঙ্গে' : 'Working together for healthier hearts') ?></h2>
      <p>
        <?= e($isBn
            ? 'বিশ্ব হার্ট দিবসের আন্তর্জাতিক আন্দোলন থেকে অনুপ্রাণিত হয়ে Heart Care Foundation Comilla কুমিল্লাসহ বাংলাদেশের বিভিন্ন অঞ্চলে হৃদরোগ ও স্ট্রোক প্রতিরোধের বার্তা ছড়িয়ে দিচ্ছে।'
            : 'Inspired by the global World Heart Day movement, Heart Care Foundation Comilla shares heart-disease and stroke-prevention messages in Cumilla and across Bangladesh.') ?>
      </p>
    </div>
  </section>

  <section class="impact-stats section" aria-labelledby="impact-stats-title">
    <div class="wrap">
      <div class="impact-section-heading">
        <span class="impact-kicker"><?= e($isBn ? 'আমাদের অর্জন এক নজরে' : 'By the numbers') ?></span>
        <h2 id="impact-stats-title"><?= e($isBn ? 'প্রভাব, যা মানুষের জীবনে পৌঁছায়' : 'Impact that reaches people') ?></h2>
      </div>
      <div class="impact-stat-grid">
        <?php foreach ($stats as $stat): ?>
          <article class="impact-stat-card">
            <p class="impact-stat-number"><?= e($stat['number']) ?></p>
            <p><?= e($stat['label']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <p class="impact-stat-note">
        <?= e($isBn
            ? 'প্রতিষ্ঠানের সরবরাহ করা তথ্য অনুযায়ী; ২০০৪ সাল থেকে শতাধিক ক্যাম্পের মাধ্যমে এই সেবা পৌঁছেছে।'
            : 'Figures provided by the Foundation; the free-camp service has been delivered since 2004.') ?>
      </p>
    </div>
  </section>

  <section class="impact-timeline-section section" aria-labelledby="impact-timeline-title">
    <div class="wrap">
      <div class="impact-section-heading">
        <span class="impact-kicker"><?= e($isBn ? '২০০০–২০২৪' : '2000–2024') ?></span>
        <h2 id="impact-timeline-title"><?= e($isBn ? 'আমাদের যাত্রাপথ' : 'Our journey through the years') ?></h2>
        <p><?= e($isBn
            ? 'বিশ্ব হার্ট দিবসের প্রচারণা এবং ২০০৪ সালে ফাউন্ডেশনের প্রতিষ্ঠা থেকে গড়ে ওঠা সচেতনতার ধারাবাহিকতা।'
            : 'A history of World Heart Day campaigns and the Foundation’s work since its establishment in 2004.') ?></p>
      </div>
      <ol class="impact-timeline">
        <?php foreach ($timeline as $entry): ?>
          <li class="impact-timeline-item">
            <div class="impact-timeline-year"><?= e($entry['years']) ?></div>
            <article class="impact-timeline-card">
              <h3><?= e($entry['title']) ?></h3>
              <ul class="impact-theme-list">
                <?php foreach ($entry['themes'] as $theme): ?>
                  <li><?= e($theme) ?></li>
                <?php endforeach; ?>
              </ul>
              <p><?= e($entry['description']) ?></p>
              <?php if (!empty($entry['images'])): ?>
                <div class="impact-timeline-images">
                  <?php foreach ($entry['images'] as $image): ?>
                    <figure>
                      <img src="<?= e(asset($image['path'] ?? $imageBase . $image['file'])) ?>"
                           alt="<?= e($image['alt']) ?>"
                           loading="lazy"
                           decoding="async">
                    </figure>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </article>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>

  <section class="impact-recognition section" aria-labelledby="impact-recognition-title">
    <div class="wrap impact-recognition-grid">
      <div class="impact-recognition-copy">
        <span class="impact-kicker"><?= e($isBn ? 'আন্তর্জাতিক স্বীকৃতি · ২০২৩' : 'International recognition · 2023') ?></span>
        <h2 id="impact-recognition-title"><?= e($isBn ? 'বিশ্বমঞ্চে হৃদস্বাস্থ্যের বার্তা' : 'A heart-health message recognized worldwide') ?></h2>
        <p>
          <?= e($isBn
              ? 'Heart Care Foundation Comilla ওয়ার্ল্ড হার্ট ফেডারেশনের “Most Inspiring World Heart Day Award 2023” অর্জন করে। হৃদস্বাস্থ্য সচেতনতায় ফাউন্ডেশনের দীর্ঘদিনের অবদানকে এই স্বীকৃতি তুলে ধরে।'
              : 'Heart Care Foundation Comilla received the World Heart Federation’s “Most Inspiring World Heart Day Award 2023,” recognizing the Foundation’s contribution to cardiovascular awareness.') ?>
        </p>
        <p>
          <?= e($isBn
              ? 'ফাউন্ডেশনের উদ্যোগ ও অনুরোধের পরিপ্রেক্ষিতে বিশ্ব হার্ট দিবসের যোগাযোগ ও প্রচারণামূলক উপকরণে বাংলা ভাষা অন্তর্ভুক্ত হয়—বিশ্বজুড়ে বাংলা ভাষাভাষীদের জন্য একটি গুরুত্বপূর্ণ অর্জন।'
              : 'Following the Foundation’s initiative and request, Bengali was included in World Heart Day communications and campaign materials—an important achievement for Bengali-speaking communities worldwide.') ?>
        </p>
        <div class="impact-bengali-mark">
          <img src="<?= e(asset($imageBase . 'world-heart-day-bengali-logo.png')) ?>"
               alt="<?= e($isBn ? 'বিশ্ব হার্ট দিবসের বাংলা ভাষার লোগো' : 'World Heart Day logo in Bengali') ?>"
               loading="lazy"
               decoding="async">
        </div>
      </div>
      <figure class="impact-award-photo">
        <img src="<?= e(asset($imageBase . '2023-award-ceremony.jpg')) ?>"
             alt="<?= e($isBn ? '২০২৩ সালের Most Inspiring World Heart Day Award-এর পুরস্কার ও স্বীকৃতির ছবি' : 'The 2023 Most Inspiring World Heart Day Award and recognition') ?>"
             loading="lazy"
             decoding="async">
        <figcaption><?= e($isBn ? 'Most Inspiring World Heart Day Award 2023' : 'Most Inspiring World Heart Day Award 2023') ?></figcaption>
      </figure>
    </div>
  </section>

  <section class="impact-community section" aria-labelledby="impact-community-title">
    <div class="wrap">
      <div class="impact-section-heading">
        <span class="impact-kicker"><?= e($isBn ? 'কমিউনিটির সঙ্গে' : 'Alongside our community') ?></span>
        <h2 id="impact-community-title"><?= e($isBn ? 'সচেতনতা থেকে অংশগ্রহণ' : 'Awareness brought to life') ?></h2>
        <p><?= e($isBn
            ? 'র‍্যালি, সাইকেল র‍্যালি, স্কুল-কলেজের কার্যক্রম, শরীরচর্চা ও শিক্ষামূলক প্রচারের মাধ্যমে হৃদস্বাস্থ্যের বার্তা ছড়িয়ে দেওয়া হয়।'
            : 'Heart-health messages are shared through rallies, cycling events, school and college programmes, physical activity, and educational outreach.') ?></p>
      </div>
      <div class="impact-activity-grid">
        <?php foreach ($activities as $activity): ?>
          <figure class="impact-activity-card">
            <img src="<?= e(asset($imageBase . $activity['file'])) ?>"
                 alt="<?= e($activity['alt']) ?>"
                 loading="lazy"
                 decoding="async">
            <figcaption><?= e($activity['caption']) ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
      <ul class="impact-activity-list">
        <?php foreach (($isBn
            ? ['বিনামূল্যে হার্ট ক্যাম্প', 'জনসচেতনতামূলক র‍্যালি', 'স্কুল ও কলেজে সচেতনতামূলক কার্যক্রম', 'শরীরচর্চা ও স্বাস্থ্যকর জীবনযাপন', 'শিক্ষামূলক লিফলেট বিতরণ']
            : ['Free heart camps', 'Public-awareness rallies', 'School and college awareness programmes', 'Physical exercise and healthy lifestyles', 'Educational leaflet distribution']) as $activity): ?>
          <li><?= e($activity) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </section>

  <section class="impact-pillars section" aria-label="<?= e($isBn ? 'শিক্ষা ও আন্তর্জাতিক সহযোগিতা' : 'Education and global collaboration') ?>">
    <div class="wrap impact-pillar-grid">
      <article class="impact-pillar-card">
        <span class="impact-pillar-icon" aria-hidden="true">▤</span>
        <span class="impact-kicker"><?= e($isBn ? 'স্বাস্থ্যশিক্ষা' : 'Health education') ?></span>
        <h2><?= e($isBn ? 'জ্ঞান ছড়িয়ে, প্রতিরোধ গড়ে' : 'Education for prevention') ?></h2>
        <p><?= e($isBn
            ? 'অধ্যাপক ডা. তৃপ্তীশ চন্দ্র ঘোষের স্বাস্থ্যসচেতনতামূলক ১৪টি বই প্রকাশিত হয়েছে; এর মধ্যে ৭টি হৃদস্বাস্থ্য ও হৃদরোগ প্রতিরোধবিষয়ক।'
            : 'Professor Dr. Triptish Chandra Ghose has published 14 books on health awareness and related subjects, including seven focused on heart health and cardiovascular disease prevention.') ?></p>
        <strong><?= e($isBn ? '১৪টি প্রকাশিত বই · হৃদস্বাস্থ্য বিষয়ে ৭টি' : '14 books published · 7 focused on heart health') ?></strong>
      </article>
      <article class="impact-pillar-card impact-pillar-card-accent">
        <span class="impact-pillar-icon" aria-hidden="true">♡</span>
        <span class="impact-kicker"><?= e($isBn ? 'আন্তর্জাতিক সহযোগিতা' : 'International collaboration') ?></span>
        <h2><?= e($isBn ? 'স্থানীয় উদ্যোগ, বৈশ্বিক সংযোগ' : 'Local action, global connection') ?></h2>
        <p><?= e($isBn
            ? 'ওয়ার্ল্ড হার্ট ফেডারেশনের পূর্ণ সদস্য হিসেবে Heart Care Foundation Comilla আন্তর্জাতিক হৃদরোগ প্রতিরোধ আন্দোলনের সঙ্গে যুক্ত।'
            : 'As a full member of the World Heart Federation, Heart Care Foundation Comilla is connected to the global movement for cardiovascular health.') ?></p>
        <strong><?= e($isBn ? 'ওয়ার্ল্ড হার্ট ফেডারেশনের পূর্ণ সদস্য' : 'Full member of the World Heart Federation') ?></strong>
      </article>
    </div>
  </section>

  <section class="impact-commitment section">
    <div class="wrap impact-commitment-inner">
      <span class="impact-kicker"><?= e($isBn ? 'আমাদের অঙ্গীকার অব্যাহত থাকবে' : 'Our commitment continues') ?></span>
      <h2><?= e($isBn ? 'আগামীর জন্য সুস্থ হৃদয়' : 'Building healthier hearts for the future') ?></h2>
      <p><?= e($isBn
          ? 'আমাদের যাত্রা শুধু অতীতের অর্জনের গল্প নয়; এটি ভবিষ্যৎ প্রজন্মের জন্য একটি সুস্থ সমাজ গড়ার প্রত্যয়। হৃদরোগ প্রতিরোধ, সচেতনতা, স্বাস্থ্যশিক্ষা, পুনর্বাসন ও কমিউনিটিভিত্তিক কার্যক্রমে আমরা প্রতিশ্রুতিবদ্ধ।'
          : 'Our journey is not just about the years behind us; it is about the lives we can positively influence in the years ahead. We remain committed to building a healthier society through cardiovascular disease prevention, awareness, education, rehabilitation, and community engagement.') ?></p>
      <p class="impact-tagline"><?= e($isBn ? 'সবার জন্য সুস্থ হৃদয়, সবার জন্য সুন্দর ভবিষ্যৎ।' : 'Heart Health for Everyone. A Healthier Future for All.') ?></p>
      <a class="pill impact-donate-link" href="<?= e(url('donate')) ?>">
        <span><?= e(t('donate')) ?></span><span class="pill-arrow" aria-hidden="true">→</span>
      </a>
    </div>
  </section>
</div>
