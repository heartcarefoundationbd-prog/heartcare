document.addEventListener('DOMContentLoaded', function () {
  // Keep footer link groups open on desktop and compact on mobile.
  var footerAccordions = document.querySelectorAll('.footer-accordion');
  if (footerAccordions.length) {
    var footerMq = window.matchMedia('(max-width: 580px)');
    var setFooterState = function () {
      footerAccordions.forEach(function (item) { item.open = !footerMq.matches; });
    };
    setFooterState();
    if (footerMq.addEventListener) { footerMq.addEventListener('change', setFooterState); }
    else if (footerMq.addListener) { footerMq.addListener(setFooterState); }
  }

  // Keep the full leadership message available on desktop and expandable on mobile.
  var leadershipToggle = document.querySelector('[data-leadership-toggle]');
  var leadershipRest = document.querySelector('[data-leadership-rest]');
  var leadershipExtra = document.querySelector('[data-leadership-extra]');
  if (leadershipToggle && leadershipExtra) {
    var leadershipMq = window.matchMedia('(max-width: 768px)');
    var setLeadershipExpanded = function (expanded) {
      if (leadershipRest) { leadershipRest.hidden = !expanded; }
      leadershipExtra.hidden = !expanded;
      leadershipToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      leadershipToggle.textContent = leadershipToggle.getAttribute(expanded ? 'data-label-less' : 'data-label-more') || '';
    };
    var syncLeadershipMode = function () {
      leadershipToggle.hidden = !leadershipMq.matches;
      setLeadershipExpanded(!leadershipMq.matches);
    };
    leadershipToggle.addEventListener('click', function () {
      setLeadershipExpanded(leadershipToggle.getAttribute('aria-expanded') !== 'true');
    });
    syncLeadershipMode();
    if (leadershipMq.addEventListener) { leadershipMq.addEventListener('change', syncLeadershipMode); }
    else if (leadershipMq.addListener) { leadershipMq.addListener(syncLeadershipMode); }
  }

  // 1. Mobile Navigation Toggle (accessible: localized labels, Escape, outside tap, focus return)
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    var icon = toggle.querySelector('span') || toggle;
    var setNav = function (open, returnFocus) {
      nav.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open
        ? (toggle.getAttribute('data-label-close') || 'Close menu')
        : (toggle.getAttribute('data-label-open') || 'Open menu'));
      icon.textContent = open ? '×' : '☰';
      document.documentElement.classList.toggle('nav-open', open);
      if (!open && returnFocus) { toggle.focus(); }
    };
    toggle.addEventListener('click', function () {
      setNav(!nav.classList.contains('open'), false);
    });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape' && nav.classList.contains('open')) { setNav(false, true); }
    });
    document.addEventListener('click', function (ev) {
      if (nav.classList.contains('open') && !nav.contains(ev.target) && !toggle.contains(ev.target)) {
        setNav(false, false);
      }
    });
    nav.addEventListener('click', function (ev) {
      if (ev.target.closest('a')) { setNav(false, false); }
    });
    var mq = window.matchMedia('(min-width: 861px)');
    var onMq = function () { if (mq.matches) { setNav(false, false); } };
    if (mq.addEventListener) { mq.addEventListener('change', onMq); }
  }

  // 2. Touch-friendly news and event sliders with accessible position dots.
  document.querySelectorAll('[data-slider]').forEach(function (slider) {
    var track = slider.querySelector('[data-slider-track]');
    var cards = track ? Array.from(track.querySelectorAll('[data-slider-item]')) : [];
    var dots = Array.from(slider.querySelectorAll('[data-slider-dots] [data-slide-to]'));
    if (!track || cards.length < 2 || dots.length === 0) { return; }

    var updateActiveDot = function () {
      var center = track.scrollLeft + track.clientWidth / 2;
      var activeIndex = 0;
      var minDistance = Infinity;
      cards.forEach(function (card, idx) {
        var cardCenter = card.offsetLeft + card.offsetWidth / 2;
        var distance = Math.abs(center - cardCenter);
        if (distance < minDistance) {
          minDistance = distance;
          activeIndex = idx;
        }
      });
      dots.forEach(function (dot, idx) {
        var active = idx === activeIndex;
        dot.classList.toggle('active', active);
        dot.setAttribute('aria-current', active ? 'true' : 'false');
      });
    };

    track.addEventListener('scroll', updateActiveDot, { passive: true });
    dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        var idx = Number.parseInt(dot.getAttribute('data-slide-to') || '', 10);
        if (!Number.isInteger(idx) || !cards[idx]) { return; }
        track.scrollTo({
          left: cards[idx].offsetLeft - (track.clientWidth - cards[idx].offsetWidth) / 2,
          behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        });
      });
    });
    updateActiveDot();
  });

  // Home card motion is progressive enhancement; content stays visible without JavaScript.
  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var animateCounter = function (element) {
    if (element.dataset.counterPlayed === '1') { return; }
    element.dataset.counterPlayed = '1';
    var target = Number.parseInt(element.getAttribute('data-counter-target') || '', 10);
    if (!Number.isFinite(target) || target < 0 || reduceMotion) { return; }
    var suffix = element.getAttribute('data-counter-suffix') || '';
    var start = performance.now();
    var duration = 900;
    element.textContent = '0' + suffix;
    var step = function (now) {
      var progress = Math.min(1, (now - start) / duration);
      var eased = 1 - Math.pow(1 - progress, 3);
      element.textContent = Math.round(target * eased).toLocaleString() + suffix;
      if (progress < 1) { window.requestAnimationFrame(step); }
    };
    window.requestAnimationFrame(step);
  };
  var homeCards = Array.from(document.querySelectorAll('.home-card'));
  if (homeCards.length) {
    var cardObserver = 'IntersectionObserver' in window && !reduceMotion
      ? new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          var card = entry.target;
          var video = card.querySelector('[data-card-video]');
          if (entry.isIntersecting) {
            card.classList.add('is-visible');
            card.querySelectorAll('[data-counter-target]').forEach(animateCounter);
            if (video && document.visibilityState === 'visible') {
              var playback = video.play();
              if (playback && typeof playback.catch === 'function') { playback.catch(function () {}); }
            }
          } else if (video) {
            video.pause();
          }
        });
      }, { threshold: 0.18 })
      : null;
    homeCards.forEach(function (card) {
      if (card.getAttribute('data-animation-trigger') === 'load') { card.classList.add('is-visible'); }
      if (cardObserver) { cardObserver.observe(card); }
      else {
        card.classList.add('is-visible');
        card.querySelectorAll('[data-counter-target]').forEach(animateCounter);
        var video = card.querySelector('[data-card-video]');
        if (video && !reduceMotion) {
          var playback = video.play();
          if (playback && typeof playback.catch === 'function') { playback.catch(function () {}); }
        }
      }
    });
    document.addEventListener('visibilitychange', function () {
      if (document.visibilityState === 'hidden') {
        homeCards.forEach(function (card) {
          var video = card.querySelector('[data-card-video]');
          if (video) { video.pause(); }
        });
      }
    });
  }

  // 3. Lazy YouTube Video Player
  var playBtn = document.getElementById('playVideoBtn');
  var videoWrapper = document.getElementById('videoWrapper');
  if (playBtn && videoWrapper) {
    playBtn.addEventListener('click', function () {
      var youtubeId = (videoWrapper.getAttribute('data-youtube-id') || '').trim();
      if (!youtubeId) {
        alert(document.body.classList.contains('lang-bn') 
          ? 'এইচসিএফ কুমিল্লার অফিশিয়াল প্রামাণ্যচিত্র ভিডিও শীঘ্রই যুক্ত করা হবে।' 
          : 'HCF Comilla official documentary video is coming soon.');
        return;
      }
      
      // Inject lazy YouTube iframe
      var iframe = document.createElement('iframe');
      iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
      iframe.setAttribute('src', 'https://www.youtube.com/embed/' + encodeURIComponent(youtubeId) + '?autoplay=1&rel=0');
      iframe.setAttribute('title', 'Heart Care Foundation Comilla Video');
      iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
      iframe.setAttribute('allowfullscreen', 'true');
      iframe.style.width = '100%';
      iframe.style.height = '100%';
      iframe.style.border = 'none';

      videoWrapper.innerHTML = '';
      videoWrapper.appendChild(iframe);
    });
  }

  // Gallery videos load their YouTube player only after an explicit play action.
  document.querySelectorAll('[data-gallery-player]').forEach(function (player) {
    var trigger = player.querySelector('.gallery-video-trigger');
    if (!trigger) { return; }
    trigger.addEventListener('click', function () {
      var videoId = player.getAttribute('data-video-id') || '';
      if (!/^[A-Za-z0-9_-]{11}$/.test(videoId)) { return; }
      var iframe = document.createElement('iframe');
      iframe.src = 'https://www.youtube-nocookie.com/embed/' + videoId + '?autoplay=1&rel=0';
      iframe.title = player.getAttribute('data-video-title') || 'Gallery video';
      iframe.loading = 'lazy';
      iframe.referrerPolicy = 'strict-origin-when-cross-origin';
      iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
      iframe.allowFullscreen = true;
      player.replaceChildren(iframe);
    });
  });

  // 4. Gallery lightbox (uses <dialog>; falls back to opening the image)
  var lb = document.getElementById('lightbox');
  if (lb && typeof lb.showModal === 'function') {
    var lbImg = document.getElementById('lightbox-img');
    var lbCap = document.getElementById('lightbox-cap');
    document.querySelectorAll('[data-lightbox]').forEach(function (a) {
      a.addEventListener('click', function (ev) {
        ev.preventDefault();
        lbImg.src = a.getAttribute('href');
        lbImg.alt = a.getAttribute('data-caption') || '';
        lbCap.textContent = a.getAttribute('data-caption') || '';
        lb.showModal();
      });
    });
    lb.addEventListener('click', function (ev) {
      if (ev.target === lb || ev.target.hasAttribute('data-lightbox-close')) { lb.close(); }
    });
    lb.addEventListener('close', function () { lbImg.removeAttribute('src'); });
  }
});
