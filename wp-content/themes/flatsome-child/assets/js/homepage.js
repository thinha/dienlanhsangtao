(function () {
  'use strict';

  const root = document.querySelector('.dmc-homepage');
  if (!root) return;

  const config = window.dmcHomepage || {};

  // Cart badge
  const cartCount = root.querySelector('#dmcCartCount');
  if (cartCount && config.cartCount) {
    cartCount.textContent = config.cartCount;
  }

  // Search form
  const searchForm = root.querySelector('#dmcSearchForm');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      const input = root.querySelector('#dmcSearchInput');
      const q = input && input.value.trim();
      if (!q) {
        e.preventDefault();
      }
    });
  }

  // Hero banner Swiper
  const heroSwiperEl = root.querySelector('.dmc-hero-swiper');
  if (heroSwiperEl && typeof Swiper !== 'undefined') {
    function heroRealSlideCount(swiper) {
      return Array.prototype.filter.call(swiper.slides, function (slide) {
        return !slide.classList.contains('swiper-slide-duplicate');
      }).length;
    }

    function setHeroSoundState(muted) {
      heroSwiperEl.querySelectorAll('video').forEach(function (video) {
        video.muted = muted;
      });
      heroSwiperEl.querySelectorAll('.hero-slide__sound').forEach(function (btn) {
        btn.classList.toggle('is-muted', muted);
        btn.setAttribute('aria-label', muted ? 'Bật tiếng' : 'Tắt tiếng');
      });
    }

    function resumeHeroAutoplay(swiper) {
      if (heroRealSlideCount(swiper) < 2) return;
      if (swiper.autoplay && typeof swiper.autoplay.start === 'function') {
        swiper.autoplay.start();
      }
    }

    function playHeroVideo(video, swiper) {
      const attempt = video.play();
      if (!attempt || typeof attempt.catch !== 'function') return;
      attempt.catch(function () {
        if (!video.muted) {
          setHeroSoundState(true);
          video.play().catch(function () {
            resumeHeroAutoplay(swiper);
          });
          return;
        }
        resumeHeroAutoplay(swiper);
      });
    }

    function syncHeroMedia(swiper) {
      const active = swiper.slides[swiper.activeIndex];
      Array.prototype.forEach.call(swiper.slides, function (slide) {
        if (slide === active) return;
        const other = slide.querySelector('video');
        if (other) other.pause();
      });

      const video = active ? active.querySelector('video') : null;
      if (!video) {
        resumeHeroAutoplay(swiper);
        return;
      }

      if (swiper.autoplay && typeof swiper.autoplay.stop === 'function') {
        swiper.autoplay.stop();
      }
      try {
        video.currentTime = 0;
      } catch (err) {
        // Metadata may not be ready yet; playback still starts at 0.
      }
      playHeroVideo(video, swiper);
    }

    const heroSwiper = new Swiper(heroSwiperEl, {
      slidesPerView: 1,
      spaceBetween: 0,
      loop: true,
      autoHeight: true,
      speed: config.slideSpeed || 600,
      freeMode: {
        enabled: true,
        momentum: true,
        momentumRatio: 0.6,
      },
      autoplay: {
        delay: config.slideDelay || 4000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      },
      pagination: {
        el: root.querySelector('.dmc-hero-pagination'),
        clickable: true,
      },
      on: {
        init: function () {
          syncHeroMedia(this);
        },
        slideChangeTransitionEnd: function () {
          syncHeroMedia(this);
        },
        autoplayStart: function () {
          const active = this.slides[this.activeIndex];
          if (active && active.querySelector('video') && this.autoplay) {
            this.autoplay.stop();
          }
        },
      },
    });

    heroSwiperEl.addEventListener('ended', function (event) {
      const video = event.target;
      if (!video || video.tagName !== 'VIDEO') return;
      if (heroRealSlideCount(heroSwiper) < 2) {
        video.currentTime = 0;
        playHeroVideo(video, heroSwiper);
        return;
      }
      heroSwiper.slideNext();
    }, true);

    heroSwiperEl.addEventListener('click', function (event) {
      const btn = event.target.closest('.hero-slide__sound');
      if (!btn || !heroSwiperEl.contains(btn)) return;
      event.preventDefault();
      event.stopPropagation();
      const wrap = btn.closest('.hero-slide__video-wrap');
      const video = wrap ? wrap.querySelector('video') : null;
      if (!video) return;
      setHeroSoundState(!video.muted);
      if (video.paused) {
        playHeroVideo(video, heroSwiper);
      }
    });
  }

  // Product section swipers
  if (typeof Swiper !== 'undefined') {
    function updateProductNavState(swiper, wrap) {
      if (!wrap) return;
      wrap.classList.toggle('is-locked', swiper.isLocked);
      wrap.classList.toggle('is-at-start', swiper.isBeginning);
      wrap.classList.toggle('is-at-end', swiper.isEnd);
    }

    root.querySelectorAll('.dmc-product-swiper').forEach(function (el) {
      const wrap = el.closest('.dmc-swiper-wrap');
      const prevEl = wrap ? wrap.querySelector('.dmc-swiper-prev') : null;
      const nextEl = wrap ? wrap.querySelector('.dmc-swiper-next') : null;
      const slidesPerView = parseFloat(el.dataset.slidesPerView) || 4;
      const autoplayEnabled = el.dataset.autoplay === '1';
      const autoplayDelay = (parseInt(el.dataset.autoplayDelay, 10) || 4) * 1000;

      const swiperConfig = {
        slidesPerView: Math.min(slidesPerView, 2.2),
        spaceBetween: 10,
        freeMode: {
          enabled: true,
          momentum: true,
          momentumRatio: 0.65,
        },
        navigation: {
          prevEl: prevEl,
          nextEl: nextEl,
        },
        breakpoints: {
          480: { slidesPerView: Math.min(slidesPerView, 2.5) },
          640: { slidesPerView: Math.min(slidesPerView, 3.2) },
          900: { slidesPerView: Math.min(slidesPerView, 4.2) },
          1100: { slidesPerView: slidesPerView },
        },
        on: {
          init: function () {
            updateProductNavState(this, wrap);
          },
          resize: function () {
            updateProductNavState(this, wrap);
          },
          slideChange: function () {
            updateProductNavState(this, wrap);
          },
          progress: function () {
            updateProductNavState(this, wrap);
          },
          touchEnd: function () {
            updateProductNavState(this, wrap);
          },
        },
      };

      if (autoplayEnabled) {
        swiperConfig.autoplay = {
          delay: autoplayDelay,
          disableOnInteraction: false,
          pauseOnMouseEnter: true,
        };
      }

      new Swiper(el, swiperConfig);
    });

    root.querySelectorAll('.dmc-voucher-swiper').forEach(function (el) {
      new Swiper(el, {
        slidesPerView: 'auto',
        spaceBetween: 10,
        freeMode: {
          enabled: true,
          momentum: true,
          momentumRatio: 0.65,
        },
      });
    });
  }

  // Flash sale countdown
  let remain = config.flashEnd || 4 * 3600 + 23 * 60 + 59;
  const hEl = root.querySelector('#dmcCountH');
  const mEl = root.querySelector('#dmcCountM');
  const sEl = root.querySelector('#dmcCountS');

  function pad(n) {
    return String(n).padStart(2, '0');
  }

  function tickCountdown() {
    if (!hEl || !mEl || !sEl) return;
    remain = remain ? remain - 1 : config.flashEnd || 4 * 3600 + 23 * 60 + 59;
    const h = Math.floor(remain / 3600);
    const m = Math.floor((remain % 3600) / 60);
    const s = remain % 60;
    hEl.textContent = pad(h);
    mEl.textContent = pad(m);
    sEl.textContent = pad(s);
  }

  tickCountdown();
  setInterval(tickCountdown, 1000);

  // Mega menu — hover sidebar + panel switch (XANH style)
  const megaZone = root.querySelector('#dmcMegaZone');
  const megaMenu = root.querySelector('#dmcMegaMenu');
  const megaTrigger = root.querySelector('#dmcMegaTrigger');

  if (megaZone && megaMenu) {
    let closeTimer;

    function openMega() {
      clearTimeout(closeTimer);
      megaZone.classList.add('is-open');
      megaMenu.setAttribute('aria-hidden', 'false');
      if (megaTrigger) {
        megaTrigger.setAttribute('aria-expanded', 'true');
      }
    }

    function closeMega() {
      closeTimer = setTimeout(function () {
        megaZone.classList.remove('is-open');
        megaMenu.setAttribute('aria-hidden', 'true');
        if (megaTrigger) {
          megaTrigger.setAttribute('aria-expanded', 'false');
        }
      }, 150);
    }

    [megaZone.querySelector('#dmcMegaWrap'), megaMenu].forEach(function (el) {
      if (el) {
        el.addEventListener('mouseenter', openMega);
      }
    });

    megaZone.addEventListener('mouseleave', closeMega);

    if (megaTrigger) {
      megaTrigger.addEventListener('click', function (e) {
        e.preventDefault();
        if (megaZone.classList.contains('is-open')) {
          megaZone.classList.remove('is-open');
          megaMenu.setAttribute('aria-hidden', 'true');
          megaTrigger.setAttribute('aria-expanded', 'false');
        } else {
          openMega();
        }
      });
    }

    const sidebarItems = megaZone.querySelectorAll('.mega-sidebar__item');
    const panels = megaZone.querySelectorAll('.mega-panel');

    sidebarItems.forEach(function (item) {
      item.addEventListener('mouseenter', function () {
        const index = item.getAttribute('data-mega-index');
        sidebarItems.forEach(function (el) {
          el.classList.remove('is-active');
        });
        panels.forEach(function (panel) {
          panel.classList.toggle('is-active', panel.getAttribute('data-mega-index') === index);
        });
        item.classList.add('is-active');
      });
    });
  }

  // Mobile drawer
  const drawer = root.querySelector('#dmcDrawer');
  const drawerBackdrop = root.querySelector('#dmcDrawerBackdrop');
  const drawerOpen = root.querySelector('#dmcDrawerOpen');
  const drawerClose = root.querySelector('#dmcDrawerClose');

  function closeDrawer() {
    if (drawer) drawer.classList.remove('open');
    if (drawerBackdrop) drawerBackdrop.classList.remove('open');
  }

  if (drawerOpen) {
    drawerOpen.addEventListener('click', function () {
      if (drawer) drawer.classList.add('open');
      if (drawerBackdrop) drawerBackdrop.classList.add('open');
    });
  }

  if (drawerClose) drawerClose.addEventListener('click', closeDrawer);
  if (drawerBackdrop) drawerBackdrop.addEventListener('click', closeDrawer);

  const drawerSidebarItems = root.querySelectorAll('.drawer-sidebar__item');
  const drawerPanels = root.querySelectorAll('.drawer-panel');

  drawerSidebarItems.forEach(function (item) {
    const btn = item.querySelector('.drawer-sidebar__btn');
    if (!btn) return;

    btn.addEventListener('click', function () {
      const index = item.getAttribute('data-drawer-index');
      drawerSidebarItems.forEach(function (el) {
        el.classList.remove('is-active');
      });
      drawerPanels.forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-drawer-index') === index);
      });
      item.classList.add('is-active');
    });
  });

  // Mobile bar — open drawer on category
  const mobileCategoryBtn = root.querySelector('#dmcMobileCategory');
  if (mobileCategoryBtn && drawerOpen) {
    mobileCategoryBtn.addEventListener('click', function () {
      drawerOpen.click();
    });
  }
})();
