(function () {
  'use strict';

  if (typeof dmcAccount === 'undefined') {
    return;
  }

  function postToggle(productId) {
    var body = new FormData();
    body.append('action', 'dmc_wishlist_toggle');
    body.append('nonce', dmcAccount.nonce);
    body.append('productId', String(productId));

    return fetch(dmcAccount.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      body: body,
    }).then(function (response) {
      return response.json();
    });
  }

  function updateBadges(count) {
    document.querySelectorAll('[data-dmc-wishlist-count]').forEach(function (el) {
      el.textContent = String(count);
      el.hidden = count < 1;
    });
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('.js-dmc-wishlist-toggle');
    if (!button) {
      return;
    }

    event.preventDefault();

    var productId = button.getAttribute('data-product-id');
    if (!productId) {
      return;
    }

    postToggle(productId)
      .then(function (payload) {
        if (!payload.success) {
          if (payload.data && payload.data.loginUrl) {
            window.location.href = payload.data.loginUrl;
          }
          return;
        }

        updateBadges(payload.data.count);

        if (button.classList.contains('dmc-wishlist-remove')) {
          var item = button.closest('.dmc-wishlist-item');
          if (item) {
            item.remove();
          }

          var grid = document.querySelector('.dmc-wishlist-grid');
          if (grid && !grid.children.length) {
            window.location.reload();
          }
        } else {
          button.classList.toggle('is-active', payload.data.added);
        }
      })
      .catch(function () {
        /* noop */
      });
  });
})();
