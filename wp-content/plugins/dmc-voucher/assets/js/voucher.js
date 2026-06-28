(function ($) {
	'use strict';

	function formatVnd(amount) {
		if (typeof dmcProductList !== 'undefined') {
			return Number(amount).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
		}
		return Number(amount).toLocaleString('vi-VN') + ' ₫';
	}

	function getQty() {
		var qty = parseInt($('form.cart input.qty').val(), 10);
		return qty > 0 ? qty : 1;
	}

	function getProductId() {
		var $box = $('#dmc-voucher-box');
		if ($box.length) {
			return parseInt($box.data('product-id'), 10) || 0;
		}
		return dmcVoucher.productId || 0;
	}

	function getBasePrice() {
		var $priceBox = $('.pl-price[data-base-price]');
		if ($priceBox.length) {
			return parseFloat($priceBox.data('original-base-price')) || parseFloat($priceBox.data('base-price')) || 0;
		}
		return dmcVoucher.basePrice || 0;
	}

	function storeOriginalBasePrice() {
		var $priceBox = $('.pl-price[data-base-price]');
		if ($priceBox.length && !$priceBox.data('original-base-price')) {
			$priceBox.data('original-base-price', parseFloat($priceBox.data('base-price')) || 0);
		}
	}

	function ensureVoucherRow() {
		var $breakdown = $('#pl-price-breakdown');
		if (!$breakdown.length || $('#pl-voucher-discount-row').length) {
			return;
		}

		var row = [
			'<div class="pl-price__row pl-price__row--item pl-price__row--voucher" id="pl-voucher-discount-row" hidden>',
			'<span class="pl-price__label" id="pl-voucher-discount-label">' + (dmcVoucher.i18n.voucherRow || 'Giảm voucher') + '</span>',
			'<span class="pl-price__value pl-price__value--item pl-price__value--voucher" id="pl-voucher-discount-value"></span>',
			'</div>',
		].join('');

		var $shippingRow = $('#pl-shipping-fee-row');
		if ($shippingRow.length) {
			$shippingRow.before(row);
		} else {
			$breakdown.find('.pl-price__row--total').before(row);
		}
	}

	function getVoucherDiscountTotal(discount, qty) {
		return (parseFloat(discount) || 0) * Math.max(1, qty || 1);
	}

	function updatePriceDisplay(data) {
		var qty = getQty();
		var originalUnit = getBasePrice();
		var unitAfter = parseFloat(data.unit_after) || 0;
		var unitDiscount = parseFloat(data.discount) / Math.max(1, qty) || (originalUnit - unitAfter);
		var totalDiscount = getVoucherDiscountTotal(unitDiscount, qty);
		var saleSubtotal = originalUnit * qty;
		var saleTotal = Math.max(0, saleSubtotal - totalDiscount);
		var $priceBox = $('.pl-price[data-base-price]');

		if ($priceBox.length) {
			$priceBox.data('voucher-discount', unitDiscount);
			$priceBox.data('voucher-code', data.code || '');
			$('#pl-base-sale-price').text(formatVnd(originalUnit));
		}

		if (totalDiscount > 0 && data.code) {
			$('#pl-voucher-discount-main-row, #pl-voucher-discount-row').removeAttr('hidden');
			$('#pl-price-after-voucher-row').removeAttr('hidden');
			var label = (dmcVoucher.i18n.voucherRow || 'Voucher') + ' (' + data.code + ')';
			$('#pl-voucher-discount-main-label, #pl-voucher-discount-label').text(label);
			$('#pl-voucher-discount-main-value, #pl-voucher-discount-value').text('-' + formatVnd(totalDiscount));
			$('#pl-price-after-voucher-value').text(formatVnd(saleTotal / qty));
			$('#pl-breakdown-sale').text(formatVnd(saleSubtotal));
			$('#pl-price-breakdown').removeAttr('hidden');
		} else {
			$('#pl-voucher-discount-main-row, #pl-voucher-discount-row, #pl-price-after-voucher-row').attr('hidden', 'hidden');
			$('#pl-breakdown-sale').text(formatVnd(originalUnit * qty));
			if ($priceBox.length) {
				$priceBox.data('voucher-discount', 0);
				$priceBox.data('voucher-code', '');
			}
		}

		$('input[name="price_final"]').each(function () {
			var shippingFee = parseInt($('select[name="shipping_fee"]').val(), 10) || 0;
			var location = $('select[name="shipping_fee"] option:selected').data('location') || '';
			var priceFinal = saleTotal + (shippingFee > 0 && location ? shippingFee : 0);
			$(this).val(priceFinal);
			$('#price_final').text(formatVnd(priceFinal));
		});

		$(document).trigger('dmc:voucher:updated', [data]);
	}

	function showMessage(message, isError) {
		var $msg = $('#dmc-voucher-message');
		if (!$msg.length) {
			return;
		}
		$msg.text(message).toggleClass('is-error', !!isError).removeAttr('hidden');
	}

	function ajaxRequest(action, payload) {
		return $.ajax({
			url: dmcVoucher.ajaxUrl,
			type: 'POST',
			data: $.extend(
				{
					action: action,
					nonce: dmcVoucher.nonce,
					product_id: getProductId(),
					qty: getQty(),
				},
				payload || {}
			),
		});
	}

	function applyVoucher(code) {
		return ajaxRequest('dmc_voucher_apply', { code: code }).done(function (response) {
			if (!response.success) {
				showMessage(response.data && response.data.message ? response.data.message : 'Lỗi', true);
				return;
			}

			updatePriceDisplay(response.data);
			$('#dmc-voucher-code-input').val(response.data.code || '');
			$('#dmc-voucher-box .dmc-voucher-list-item, #dmc-voucher-box .dmc-voucher-ticket').removeClass('is-active');
			$('#dmc-voucher-box .js-dmc-voucher-product-pick').each(function () {
				var $pick = $(this);
				if (String($pick.data('voucher-code')) === response.data.code) {
					$pick.closest('.dmc-voucher-list-item, .dmc-voucher-ticket').addClass('is-active');
					$pick.prop('disabled', true).text('Đang dùng');
				} else {
					$pick.prop('disabled', false).text('Áp dụng');
				}
			});

			if (response.data.code) {
				if (!$('#dmc-voucher-active').length) {
					$('#dmc-voucher-box .dmc-voucher-box__form').after(
						'<div class="dmc-voucher-box__active" id="dmc-voucher-active"><span></span><button type="button" class="dmc-voucher-box__remove" id="dmc-voucher-remove">' +
							dmcVoucher.i18n.remove +
							'</button></div>'
					);
				}
				$('#dmc-voucher-active span').text('Đang dùng: ' + response.data.code);
			}

			showMessage(response.data.message, false);
			dmcVoucher.appliedCode = response.data.code;
		});
	}

	function removeVoucher() {
		return ajaxRequest('dmc_voucher_remove').done(function (response) {
			if (!response.success) {
				return;
			}

			var original = parseFloat($('.pl-price[data-base-price]').data('original-base-price')) || dmcVoucher.basePrice;
			response.data.unit_after = original;
			response.data.sale_total = original * getQty();
			response.data.discount = 0;
			response.data.code = '';

			updatePriceDisplay(response.data);
			$('#dmc-voucher-code-input').val('');
			$('#dmc-voucher-active').remove();
			$('#dmc-voucher-box .dmc-voucher-list-item, #dmc-voucher-box .dmc-voucher-ticket').removeClass('is-active');
			$('#dmc-voucher-box .js-dmc-voucher-product-pick').prop('disabled', false).text('Áp dụng');
			showMessage(response.data.message, false);
			dmcVoucher.appliedCode = '';
		});
	}

	function saveVoucher(voucherId, $btn) {
		return ajaxRequest('dmc_voucher_save', { voucher_id: voucherId }).done(function (response) {
			if (!response.success) {
				showMessage(response.data && response.data.message ? response.data.message : 'Lỗi', true);
				return;
			}

			if ($btn && $btn.length) {
				$btn.text(dmcVoucher.i18n.saved).prop('disabled', true);
				$btn.closest('.dmc-voucher-ticket').addClass('is-saved');
			}
			showMessage(response.data.message, false);
		});
	}

	function initProductVouchers() {
		storeOriginalBasePrice();
		ensureVoucherRow();

		$(document).on('click', '#dmc-voucher-apply-code', function () {
			var code = String($('#dmc-voucher-code-input').val() || '').trim();
			if (!code) {
				return;
			}
			applyVoucher(code);
		});

		$(document).on('click', '#dmc-voucher-remove', function () {
			removeVoucher();
		});

		$(document).on('click', '#dmc-voucher-apply-best', function () {
			ajaxRequest('dmc_voucher_apply_best').done(function (response) {
				if (!response.success) {
					showMessage(response.data && response.data.message ? response.data.message : 'Lỗi', true);
					return;
				}
				updatePriceDisplay(response.data);
				$('#dmc-voucher-code-input').val(response.data.code || '');
				$('#dmc-voucher-box .dmc-voucher-list-item, #dmc-voucher-box .dmc-voucher-ticket').removeClass('is-active');
				$('#dmc-voucher-box .js-dmc-voucher-product-pick').each(function () {
					var $pick = $(this);
					if (String($pick.data('voucher-code')) === response.data.code) {
						$pick.closest('.dmc-voucher-list-item, .dmc-voucher-ticket').addClass('is-active');
						$pick.prop('disabled', true).text('Đang dùng');
					} else {
						$pick.prop('disabled', false).text('Áp dụng');
					}
				});
				if (response.data.code && !$('#dmc-voucher-active').length) {
					$('#dmc-voucher-box .dmc-voucher-box__form').after(
						'<div class="dmc-voucher-box__active" id="dmc-voucher-active"><span></span><button type="button" class="dmc-voucher-box__remove" id="dmc-voucher-remove">' +
							dmcVoucher.i18n.remove +
							'</button></div>'
					);
				}
				if (response.data.code) {
					$('#dmc-voucher-active span').text('Đang dùng: ' + response.data.code);
				}
				showMessage(response.data.message, false);
				dmcVoucher.appliedCode = response.data.code;
			});
		});

		$(document).on('click', '#dmc-voucher-box .js-dmc-voucher-product-pick', function () {
			var $btn = $(this);
			if ($btn.prop('disabled') || $btn.closest('.dmc-voucher-list-item, .dmc-voucher-ticket').hasClass('is-active')) {
				return;
			}

			var code = String($btn.data('voucher-code') || '');
			if (!code) {
				return;
			}

			applyVoucher(code);
		});

		$(document).on('change input', 'form.cart input.qty', function () {
			if (!dmcVoucher.appliedCode) {
				return;
			}
			applyVoucher(dmcVoucher.appliedCode);
		});
	}

	function initHomepageVouchers() {
		$(document).on('click', '.dmc-voucher-save-btn', function () {
			var $btn = $(this);
			if ($btn.prop('disabled')) {
				return;
			}
			saveVoucher(parseInt($btn.data('voucher-id'), 10), $btn);
		});
	}

	function showWalletMessage(message, isError) {
		var $wrap = $('#dmc-voucher-wallet');
		if (!$wrap.length) {
			return;
		}

		var $msg = $wrap.find('.dmc-voucher-wallet__notice');
		if (!$msg.length) {
			$msg = $('<p class="dmc-voucher-wallet__notice"></p>').prependTo($wrap);
		}

		$msg.text(message).toggleClass('is-error', !!isError);
	}

	function initAccountWallet() {
		if (!$('#dmc-voucher-wallet').length) {
			return;
		}

		$(document).on('click', '.dmc-voucher-wallet__copy', function () {
			var code = String($(this).data('code') || '');
			if (!code || !navigator.clipboard) {
				return;
			}
			navigator.clipboard.writeText(code);
			showWalletMessage('Đã sao chép mã: ' + code, false);
		});

		$(document).on('click', '.dmc-voucher-wallet__remove', function () {
			var $btn = $(this);
			var voucherId = parseInt($btn.data('voucher-id'), 10);
			if (!voucherId) {
				return;
			}

			$.ajax({
				url: dmcVoucher.ajaxUrl,
				type: 'POST',
				data: {
					action: 'dmc_voucher_wallet_remove',
					nonce: dmcVoucher.nonce,
					voucher_id: voucherId,
				},
				success: function (response) {
					if (!response.success) {
						showWalletMessage(response.data && response.data.message ? response.data.message : 'Lỗi', true);
						return;
					}
					$btn.closest('.dmc-voucher-wallet__card').remove();
					showWalletMessage(response.data.message, false);
				},
			});
		});

		$(document).on('click', '#dmc-voucher-wallet .dmc-voucher-save-btn', function () {
			var $btn = $(this);
			if ($btn.prop('disabled')) {
				return;
			}
			saveVoucher(parseInt($btn.data('voucher-id'), 10), $btn).done(function () {
				window.location.reload();
			});
		});
	}

	$(function () {
		if (typeof dmcVoucher === 'undefined') {
			return;
		}

		initHomepageVouchers();
		initAccountWallet();

		if ($('#dmc-voucher-box').length || $('.pl-price[data-base-price]').length) {
			initProductVouchers();
		}

		if ($('#dmc-voucher-cart-box').length) {
			initCartVouchers();
		}
	});

	function initCartVouchers() {
		var $box = $('#dmc-voucher-cart-box');
		if (!$box.length) {
			return;
		}

		var pending = false;

		function getTotalsEl() {
			return $('.cart_totals').first();
		}

		function updateTotals(html) {
			var $currentTotals = getTotalsEl();
			if (html && $currentTotals.length) {
				$currentTotals.replaceWith(html);
				$(document.body).trigger('updated_cart_totals');
			}
		}

		var i18n = dmcVoucher.i18n || {};

		function setLoading(isLoading) {
			$box.toggleClass('is-loading', isLoading);
			$box.find('button, input, select').prop('disabled', isLoading);
		}

		function ensureActiveBanner(code, discountFmt) {
			var $active = $('#dmc-voucher-cart-active');
			var text = (i18n.activePrefix || 'Đang dùng: ') + code;

			if (discountFmt) {
				text += ' (−' + discountFmt + ')';
			}

			if (!$active.length) {
				$active = $('<div class="dmc-voucher-box__active" id="dmc-voucher-cart-active"><span></span></div>');
				$box.find('.dmc-voucher-box__form').after($active);
			}

			$active.find('span').text(text);
		}

		function updateUI(data) {
			var code = String((data && data.code) || '');
			var $action = $('#dmc-voucher-cart-action');
			var $input = $('#dmc-voucher-cart-code-input');

			if (code) {
				$action
					.text(i18n.remove || 'Gỡ bỏ')
					.removeClass('dmc-voucher-box__apply')
					.addClass('dmc-voucher-box__remove')
					.attr('data-applied', '1');
				$input.val(code);
				ensureActiveBanner(code, data.discount_fmt || '');
			} else {
				$action
					.text(i18n.apply || 'Áp dụng')
					.removeClass('dmc-voucher-box__remove')
					.addClass('dmc-voucher-box__apply')
					.attr('data-applied', '0');
				$input.val('');
				$('#dmc-voucher-cart-active').remove();
			}

			$box.find('.dmc-voucher-list-item').each(function () {
				var $item = $(this);
				var $pick = $item.find('.js-dmc-voucher-cart-pick');
				var itemCode = String($pick.data('voucher-code') || '');

				if ( code && itemCode.toUpperCase() === code.toUpperCase() ) {
					$item.addClass('is-active');
					$pick
						.text(i18n.remove || 'Gỡ bỏ')
						.addClass('dmc-voucher-ticket__btn--remove')
						.prop('disabled', false);
				} else {
					$item.removeClass('is-active');
					$pick
						.text(i18n.apply || 'Áp dụng')
						.removeClass('dmc-voucher-ticket__btn--remove')
						.prop('disabled', false);
				}
			});

			updateTotals(data && data.totals_html);
			dmcVoucher.appliedCode = code;
		}

		function cartRequest(action, payload) {
			if (pending) {
				return $.Deferred()
					.reject({ message: i18n.busy || 'Đang xử lý, vui lòng thử lại.' })
					.promise();
			}

			pending = true;
			setLoading(true);

			return $.ajax({
				url: dmcVoucher.ajaxUrl,
				type: 'POST',
				data: $.extend(
					{
						action: action,
						nonce: dmcVoucher.nonce,
					},
					payload || {}
				),
			})
				.always(function () {
					pending = false;
					setLoading(false);
				});
		}

		function showCartError(xhr, status, error) {
			var message =
				(xhr && xhr.message) ||
				(xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ||
				error ||
				(i18n.error || 'Không thể xử lý voucher. Vui lòng thử lại.');

			window.alert(message);
		}

		function applyCartCode(code) {
			return cartRequest('dmc_voucher_apply_cart', { code: code })
				.done(function (response) {
					if (response.success) {
						updateUI(response.data);
						return;
					}

					if (response.data && response.data.message) {
						window.alert(response.data.message);
					}
				})
				.fail(showCartError);
		}

		function removeCartVoucher() {
			return cartRequest('dmc_voucher_remove_cart')
				.done(function (response) {
					if (response.success) {
						updateUI(response.data);
					}
				})
				.fail(showCartError);
		}

		$('#dmc-voucher-cart-action').on('click', function () {
			if ($(this).hasClass('dmc-voucher-box__remove')) {
				removeCartVoucher();
				return;
			}

			var code = String($('#dmc-voucher-cart-code-input').val() || '').trim();
			if (!code) {
				return;
			}

			applyCartCode(code);
		});

		$('#dmc-voucher-cart-best').on('click', function () {
			cartRequest('dmc_voucher_apply_cart_best').done(function (response) {
				if (response.success) {
					updateUI(response.data);
					return;
				}

				if (response.data && response.data.message) {
					window.alert(response.data.message);
				}
			});
		});

		$box.on('click', '.js-dmc-voucher-cart-pick', function () {
			var $btn = $(this);
			var $item = $btn.closest('.dmc-voucher-list-item');

			if ($item.hasClass('is-active')) {
				removeCartVoucher();
				return;
			}

			var code = String($btn.data('voucher-code') || '');
			var voucherId = parseInt($btn.data('voucher-id'), 10);
			var isSaved = String($btn.data('voucher-saved')) === '1';

			if (!isSaved && voucherId) {
				$.ajax({
					url: dmcVoucher.ajaxUrl,
					type: 'POST',
					data: {
						action: 'dmc_voucher_save',
						nonce: dmcVoucher.nonce,
						voucher_id: voucherId,
					},
					complete: function () {
						applyCartCode(code);
					},
				});
				return;
			}

			applyCartCode(code);
		});

		$box.find('.dmc-voucher-cart-form').on('submit', function (event) {
			event.preventDefault();
			$('#dmc-voucher-cart-action').trigger('click');
		});
	}
})(jQuery);
