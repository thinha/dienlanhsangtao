(function () {
  'use strict';

  const config = window.dmcLiveSearch || {};
  const debounceMs = config.debounce || 400;
  const minChars = config.minChars || 2;
  const labels = config.labels || {};

  const selectors = ['#dmcSearchInput', '#dmcPlSearchInput'];

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function getSuggestEl(input) {
    const wrap = input.closest('.search-wrap');
    return wrap ? wrap.querySelector('.search-suggest') : null;
  }

  function hideSuggest(suggestEl) {
    if (!suggestEl) return;
    suggestEl.hidden = true;
    suggestEl.classList.remove('is-open');
    suggestEl.innerHTML = '';
  }

  function showSuggest(suggestEl, html) {
    if (!suggestEl) return;
    suggestEl.innerHTML = html;
    suggestEl.hidden = false;
    suggestEl.classList.add('is-open');
  }

  function renderResults(data) {
    const categories = data.categories || [];
    const products = data.products || [];
    let html = '';

    if (categories.length) {
      html += '<div class="search-suggest__group">';
      html += '<div class="search-suggest__title">' + escapeHtml(labels.categories || 'Danh mục') + '</div>';
      html += '<ul class="search-suggest__list">';
      categories.forEach(function (item) {
        html += '<li><a class="search-suggest__item search-suggest__item--cat" href="' + escapeHtml(item.url) + '">';
        html += '<span class="search-suggest__label">' + escapeHtml(item.name) + '</span>';
        if (item.count) {
          html += '<span class="search-suggest__meta">' + escapeHtml(String(item.count)) + ' SP</span>';
        }
        html += '</a></li>';
      });
      html += '</ul></div>';
    }

    if (products.length) {
      html += '<div class="search-suggest__group">';
      html += '<div class="search-suggest__title">' + escapeHtml(labels.products || 'Sản phẩm') + '</div>';
      html += '<ul class="search-suggest__list">';
      products.forEach(function (item) {
        html += '<li><a class="search-suggest__item search-suggest__item--product" href="' + escapeHtml(item.url) + '">';
        if (item.image) {
          html += '<img class="search-suggest__thumb" src="' + escapeHtml(item.image) + '" alt="" loading="lazy" width="40" height="40">';
        }
        html += '<span class="search-suggest__body">';
        html += '<span class="search-suggest__label">' + escapeHtml(item.name) + '</span>';
        if (item.price && item.price.current) {
          html += '<span class="search-suggest__price">';
          if (item.price.regular) {
            html += '<del class="search-suggest__price-old">' + escapeHtml(item.price.regular) + '</del>';
          }
          html += '<span class="search-suggest__price-current">' + escapeHtml(item.price.current) + '</span>';
          html += '</span>';
        }
        html += '</span></a></li>';
      });
      html += '</ul></div>';
    }

    if (!html) {
      html = '<div class="search-suggest__empty">' + escapeHtml(labels.empty || 'Không tìm thấy kết quả.') + '</div>';
    }

    return html;
  }

  function initInput(input) {
    const suggestEl = getSuggestEl(input);
    if (!suggestEl || !config.ajaxUrl) return;

    let timer = null;
    let requestId = 0;
    let abortController = null;

    function fetchSuggestions(query) {
      if (abortController) {
        abortController.abort();
      }

      abortController = new AbortController();
      const currentId = ++requestId;
      showSuggest(suggestEl, '<div class="search-suggest__loading">' + escapeHtml(labels.loading || 'Đang tìm...') + '</div>');

      const url = new URL(config.ajaxUrl, window.location.origin);
      url.searchParams.set('action', 'dmc_live_search');
      url.searchParams.set('nonce', config.nonce || '');
      url.searchParams.set('q', query);

      fetch(url.toString(), {
        method: 'GET',
        credentials: 'same-origin',
        signal: abortController.signal,
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (payload) {
          if (currentId !== requestId) return;
          if (!payload || !payload.success) {
            hideSuggest(suggestEl);
            return;
          }
          showSuggest(suggestEl, renderResults(payload.data || {}));
        })
        .catch(function (err) {
          if (err && err.name === 'AbortError') return;
          if (currentId !== requestId) return;
          hideSuggest(suggestEl);
        });
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      const query = input.value.trim();

      if (query.length < minChars) {
        requestId++;
        if (abortController) {
          abortController.abort();
        }
        hideSuggest(suggestEl);
        return;
      }

      timer = setTimeout(function () {
        fetchSuggestions(query);
      }, debounceMs);
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        hideSuggest(suggestEl);
      }
    });

    input.addEventListener('blur', function () {
      setTimeout(function () {
        hideSuggest(suggestEl);
      }, 180);
    });

    document.addEventListener('mousedown', function (e) {
      const wrap = input.closest('.search-wrap');
      if (wrap && !wrap.contains(e.target)) {
        hideSuggest(suggestEl);
      }
    });
  }

  selectors.forEach(function (selector) {
    document.querySelectorAll(selector).forEach(initInput);
  });
})();
