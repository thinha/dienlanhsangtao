(function ($) {
	'use strict';

	function initCartShipping() {
		var $box = $('#dmc-cart-shipping-box');
		if (!$box.length || typeof dmcCart === 'undefined') {
			return;
		}

		var $select = $('#dmc-cart-shipping-location');
		var pending = false;

		function updateActiveState(location) {
			var $active = $('#dmc-cart-shipping-active');
			if (location) {
				if (!$active.length) {
					$active = $('<div class="dmc-cart-shipping-box__active" id="dmc-cart-shipping-active"><span></span></div>');
					$select.after($active);
				}
				$active.find('span').text(dmcCart.i18n.activePrefix + location);
				$active.removeAttr('hidden');
			} else if ($active.length) {
				$active.remove();
			}
		}

		function setLoading(isLoading) {
			$box.toggleClass('is-loading', isLoading);
			$select.prop('disabled', isLoading);
		}

		function updateTotals(html) {
			var $currentTotals = $('.cart_totals').first();
			if (html && $currentTotals.length) {
				$currentTotals.replaceWith(html);
			}
		}

		function request(action, location) {
			if (pending) {
				return;
			}

			pending = true;
			setLoading(true);

			$.ajax({
				url: dmcCart.ajaxUrl,
				type: 'POST',
				data: {
					action: action,
					nonce: dmcCart.nonce,
					location: location || '',
				},
			})
				.done(function (response) {
					if (!response.success) {
						return;
					}

					updateTotals(response.data.totals_html);
					updateActiveState(response.data.location);

					if (response.data.location) {
						$select.val(response.data.location);
					} else {
						$select.val('');
					}
				})
				.always(function () {
					pending = false;
					setLoading(false);
				});
		}

		$select.on('change', function () {
			var location = String($select.val() || '');
			if (!location) {
				request('dmc_cart_remove_shipping', '');
				return;
			}
			request('dmc_cart_update_shipping', location);
		});

		updateActiveState(String($select.val() || ''));
	}

	function initCartAutoUpdate() {
		var $form = $('.woocommerce-cart-form');
		if (!$form.length) {
			return;
		}

		var debounceTimer = null;
		var pending = false;

		function snapshotQty() {
			var snap = {};
			$form.find('input.qty').each(function () {
				snap[this.name] = String($(this).val() || '');
			});
			return snap;
		}

		var lastQty = snapshotQty();

		function setLoading(isLoading) {
			$form.toggleClass('is-updating', isLoading);
			$('.cart_totals').toggleClass('is-updating', isLoading);
		}

		function updateHeaderCount() {
			var count = 0;
			$form.find('input.qty').each(function () {
				count += parseInt($(this).val(), 10) || 0;
			});

			$('#dmcCartCount').text(count);

			var $headCount = $('.dmc-cart-head__count');
			if ($headCount.length && typeof dmcCart !== 'undefined' && dmcCart.i18n) {
				$headCount.text(
					count === 1 ? dmcCart.i18n.itemCountOne : dmcCart.i18n.itemCountMany.replace('%d', count)
				);
			}
		}

		function updateFromHtml(html) {
			var $parsed = $('<div>').append($.parseHTML(html, document, true));
			var $newForm = $parsed.find('.woocommerce-cart-form').first();
			var $newTotals = $parsed.find('.cart_totals').first();
			var $newHeadCount = $parsed.find('.dmc-cart-head__count').first();

			if (!$newForm.length) {
				window.location.reload();
				return;
			}

			$form.replaceWith($newForm);
			$form = $('.woocommerce-cart-form');

			if ($newTotals.length) {
				$('.cart_totals').replaceWith($newTotals);
			}

			if ($newHeadCount.length) {
				$('.dmc-cart-head__count').replaceWith($newHeadCount);
			} else {
				updateHeaderCount();
			}

			lastQty = snapshotQty();
			$(document.body).trigger('updated_cart_totals');
		}

		function submitUpdate() {
			if (pending) {
				return;
			}

			var current = snapshotQty();
			var changed = false;

			$.each(current, function (name, val) {
				if (lastQty[name] !== val) {
					changed = true;
				}
			});

			if (!changed) {
				return;
			}

			pending = true;
			setLoading(true);

			var data = $form.serializeArray();
			data.push({ name: 'update_cart', value: '1' });

			$.ajax({
				type: 'POST',
				url: $form.attr('action'),
				data: $.param(data),
				dataType: 'html',
			})
				.done(function (response) {
					updateFromHtml(response);
				})
				.fail(function () {
					window.location.reload();
				})
				.always(function () {
					pending = false;
					setLoading(false);
				});
		}

		function scheduleUpdate() {
			window.clearTimeout(debounceTimer);
			debounceTimer = window.setTimeout(submitUpdate, 450);
		}

		$(document).on('change', '.woocommerce-cart-form input.qty', scheduleUpdate);
		$(document).on('input', '.woocommerce-cart-form input.qty', scheduleUpdate);
	}

	$(function () {
		initCartShipping();
		initCartAutoUpdate();
	});
})(jQuery);
