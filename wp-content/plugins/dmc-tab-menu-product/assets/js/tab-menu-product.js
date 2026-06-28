(function () {
  'use strict';

  const section = document.getElementById('dmcTabMenuProduct');
  if (!section || typeof Swiper === 'undefined') return;

  const config = window.dmcTabMenuProduct || {};
  const desktopSlides = Math.max(2, Math.min(8, parseInt(config.slidesPerView, 10) || 5));
  const tabButtons = section.querySelectorAll('.dmc-tab-menu-product__tab');
  const panels = section.querySelectorAll('.dmc-tab-menu-product__panel');
  const moreLink = section.querySelector('#dmcTabMenuMore');

  function buildBreakpoints(count) {
    return {
      0: { slidesPerView: Math.min(count, 2.15) },
      480: { slidesPerView: Math.min(count, 2.5) },
      640: { slidesPerView: Math.min(count, 3) },
      900: { slidesPerView: Math.min(count, 4) },
      1100: { slidesPerView: count },
    };
  }

  function updateNavState(swiper, wrap) {
    if (!wrap) return;

    wrap.classList.toggle('is-locked', swiper.isLocked);
    wrap.classList.toggle('is-at-start', swiper.isBeginning);
    wrap.classList.toggle('is-at-end', swiper.isEnd);
  }

  function initSwiper(panel) {
    const el = panel.querySelector('.dmc-tab-product-swiper');
    if (!el || el.swiper) return;

    const slidesPerView = Math.max(2, Math.min(8, parseInt(el.dataset.slidesPerView, 10) || desktopSlides));
    const autoplayEnabled = el.dataset.autoplay === '1' || config.autoplay === true;
    const autoplayDelay = (parseInt(el.dataset.autoplayDelay, 10) || parseInt(config.autoplayDelay, 10) / 1000 || 4) * 1000;
    const wrap = panel.querySelector('.dmc-swiper-wrap');
    const prevEl = wrap ? wrap.querySelector('.dmc-swiper-prev') : null;
    const nextEl = wrap ? wrap.querySelector('.dmc-swiper-next') : null;

    const swiperOptions = {
      slidesPerView: Math.min(slidesPerView, 2.15),
      spaceBetween: 10,
      grabCursor: true,
      watchOverflow: true,
      freeMode: {
        enabled: true,
        momentum: true,
        momentumRatio: 0.72,
        momentumBounce: false,
        sticky: false,
      },
      navigation: {
        prevEl: prevEl,
        nextEl: nextEl,
      },
      breakpoints: buildBreakpoints(slidesPerView),
      on: {
        init: function () {
          updateNavState(this, wrap);
        },
        resize: function () {
          updateNavState(this, wrap);
        },
        slideChange: function () {
          updateNavState(this, wrap);
        },
        progress: function () {
          updateNavState(this, wrap);
        },
        touchEnd: function () {
          updateNavState(this, wrap);
        },
      },
    };

    if (autoplayEnabled) {
      swiperOptions.autoplay = {
        delay: autoplayDelay,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      };
    }

    const swiper = new Swiper(el, swiperOptions);

    return swiper;
  }

  function activateTab(index) {
    tabButtons.forEach(function (btn, i) {
      const active = i === index;
      btn.classList.toggle('is-active', active);
      btn.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    panels.forEach(function (panel, i) {
      const active = i === index;
      panel.classList.toggle('is-active', active);
      if (active) {
        panel.removeAttribute('hidden');
        initSwiper(panel);
        const swiperEl = panel.querySelector('.dmc-tab-product-swiper');
        if (swiperEl && swiperEl.swiper) {
          swiperEl.swiper.update();
          const wrap = panel.querySelector('.dmc-swiper-wrap');
          updateNavState(swiperEl.swiper, wrap);
        }
      } else {
        panel.setAttribute('hidden', '');
      }
    });

    const activeBtn = tabButtons[index];
    if (moreLink && activeBtn && activeBtn.dataset.moreUrl) {
      moreLink.href = activeBtn.dataset.moreUrl;
    }
  }

  tabButtons.forEach(function (btn, index) {
    btn.addEventListener('click', function () {
      activateTab(index);
    });
  });

  const firstPanel = section.querySelector('.dmc-tab-menu-product__panel.is-active');
  if (firstPanel) {
    initSwiper(firstPanel);
  }
})();
