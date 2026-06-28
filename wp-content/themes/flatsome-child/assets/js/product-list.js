(function ($) {
	'use strict';

	function formatPrice(amount) {
		var params = window.dmcProductList || {};
		var decimals = params.currencyNumDecimals != null ? params.currencyNumDecimals : 0;
		var decimalSep = params.currencyDecimalSep || ',';
		var thousandSep = params.currencyThousandSep || '.';
		var symbol = params.currencySymbol || '₫';

		var fixed = Number(amount).toFixed(decimals);
		var parts = fixed.split('.');
		parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousandSep);
		var formatted = parts.join(decimalSep);

		return formatted + symbol;
	}

	function updatePriceLabel($form, min, max) {
		$form.find('.pl-price-filter__from').text(formatPrice(min));
		$form.find('.pl-price-filter__to').text(formatPrice(max));
	}

	function initPriceSlider() {
		var $form = $('.pl-price-filter');
		if (!$form.length || typeof $.fn.slider !== 'function') {
			return;
		}

		$form.each(function () {
			var $wrapper = $(this);
			var $slider = $wrapper.find('.pl-price-filter__slider');
			var $minInput = $wrapper.find('#pl_min_price');
			var $maxInput = $wrapper.find('#pl_max_price');

			if (!$slider.length || $slider.hasClass('ui-slider')) {
				return;
			}

			var minBound = parseInt($minInput.data('min'), 10);
			var maxBound = parseInt($maxInput.data('max'), 10);
			var step = parseInt($slider.data('step'), 10) || 1;
			var currentMin = parseInt($minInput.val(), 10);
			var currentMax = parseInt($maxInput.val(), 10);

			if (isNaN(minBound) || isNaN(maxBound) || minBound >= maxBound) {
				return;
			}

			currentMin = Math.max(minBound, Math.min(currentMin, maxBound));
			currentMax = Math.max(minBound, Math.min(currentMax, maxBound));
			if (currentMin > currentMax) {
				currentMin = minBound;
				currentMax = maxBound;
			}

			$slider.slider({
				range: true,
				animate: true,
				min: minBound,
				max: maxBound,
				step: step,
				values: [currentMin, currentMax],
				create: function () {
					$minInput.val(currentMin);
					$maxInput.val(currentMax);
					updatePriceLabel($wrapper, currentMin, currentMax);
				},
				slide: function (event, ui) {
					$minInput.val(ui.values[0]);
					$maxInput.val(ui.values[1]);
					updatePriceLabel($wrapper, ui.values[0], ui.values[1]);
				},
				change: function (event, ui) {
					$minInput.val(ui.values[0]);
					$maxInput.val(ui.values[1]);
					updatePriceLabel($wrapper, ui.values[0], ui.values[1]);
				},
			});
		});
	}

	function initProductGallery() {
		if (typeof Swiper === 'undefined') {
			return;
		}

		document.querySelectorAll('.pl-gallery').forEach(function (gallery) {
			var mainEl = gallery.querySelector('.pl-gallery-main');
			var thumbsEl = gallery.querySelector('.pl-gallery-thumbs');
			var counterEl = gallery.querySelector('.pl-gallery__counter-current');
			if (!mainEl) {
				return;
			}

			var thumbsSwiper = null;
			if (thumbsEl) {
				thumbsSwiper = new Swiper(thumbsEl, {
					spaceBetween: 8,
					slidesPerView: 'auto',
					freeMode: true,
					watchSlidesProgress: true,
				});
			}

			var mainSwiper = new Swiper(mainEl, {
				spaceBetween: 12,
				navigation: {
					nextEl: gallery.querySelector('.pl-gallery__nav--next'),
					prevEl: gallery.querySelector('.pl-gallery__nav--prev'),
				},
				thumbs: thumbsSwiper ? { swiper: thumbsSwiper } : undefined,
				on: {
					slideChange: function (swiper) {
						if (counterEl) {
							counterEl.textContent = String(swiper.activeIndex + 1);
						}
					},
				},
			});

			var giftBadge = gallery.querySelector('.pl-gallery__gift-badge');
			if (giftBadge) {
				giftBadge.addEventListener('click', function () {
					var slideIndex = parseInt(giftBadge.getAttribute('data-slide'), 10);
					if (!isNaN(slideIndex)) {
						mainSwiper.slideTo(slideIndex);
					}
				});
			}
		});
	}

	function initQtyStepper() {
		document.querySelectorAll('.pl-qty-stepper').forEach(function (stepper) {
			var input = stepper.querySelector('input.qty');
			if (!input) {
				return;
			}

			stepper.querySelectorAll('[data-qty]').forEach(function (btn) {
				btn.addEventListener('click', function () {
					var min = parseFloat(input.getAttribute('min')) || 1;
					var maxAttr = input.getAttribute('max');
					var max = maxAttr === '' || maxAttr === null ? Infinity : parseFloat(maxAttr);
					if (isNaN(max) || max <= 0) {
						max = Infinity;
					}
					var step = parseFloat(input.getAttribute('step')) || 1;
					var value = parseFloat(input.value) || min;
					var direction = btn.getAttribute('data-qty');

					if (direction === 'plus') {
						value = Math.min(max, value + step);
					} else {
						value = Math.max(min, value - step);
					}

					input.value = value;
					input.dispatchEvent(new Event('change', { bubbles: true }));
					input.dispatchEvent(new Event('input', { bubbles: true }));
				});
			});
		});
	}

	function formatVnd(amount) {
		return Number(amount).toLocaleString('vi-VN', { style: 'currency', currency: 'VND' });
	}

	function initProductPriceBreakdown() {
		var $priceBox = $('.pl-price[data-base-price]');
		if (!$priceBox.length) {
			return;
		}

		var $form = $('form.cart');
		var $shipping = $priceBox.find('select[name="shipping_fee"]');
		var $breakdown = $('#pl-price-breakdown');
		var $shippingRow = $('#pl-shipping-fee-row');

		if (!$form.length) {
			return;
		}

		function getBasePrice() {
			return parseInt($priceBox.data('base-price'), 10) || 0;
		}
		var feeLabelPrefix = String($priceBox.data('fee-label-prefix') || 'Phí giao hàng');

		function getQty() {
			var qty = parseInt($form.find('input.qty').val(), 10);
			return qty > 0 ? qty : 1;
		}

		function getShippingState() {
			var $selected = $shipping.find('option:selected');
			var shippingFee = parseInt($shipping.val(), 10) || 0;
			var locationName = $selected.data('location') || '';
			return {
				fee: shippingFee,
				location: locationName,
				active: shippingFee > 0 && !!locationName,
			};
		}

		function getVoucherDiscount() {
			return parseFloat($priceBox.data('voucher-discount')) || 0;
		}

		function shouldShowBreakdown(qty, shipping) {
			return qty > 1 || shipping.active || getVoucherDiscount() > 0;
		}

		function syncFormShippingFields(shipping) {
			if (shipping.active) {
				$form.find('input[name="dmc_shipping_location"]').val(shipping.location);
				$form.find('input[name="dmc_shipping_fee"]').val(String(shipping.fee));
			} else {
				$form.find('input[name="dmc_shipping_location"]').val('');
				$form.find('input[name="dmc_shipping_fee"]').val('');
			}
		}

		function updateBreakdown() {
			var qty = getQty();
			var unitDiscount = getVoucherDiscount();
			var saleSubtotal = getBasePrice() * qty;
			var totalDiscount = unitDiscount * qty;
			var saleTotal = Math.max(0, saleSubtotal - totalDiscount);
			var shipping = getShippingState();
			var priceFinal = saleTotal + (shipping.active ? shipping.fee : 0);
			var voucherCode = String($priceBox.data('voucher-code') || '');

			$('#pl-breakdown-sale').text(formatVnd(saleSubtotal));

			if (totalDiscount > 0 && voucherCode) {
				$('#pl-voucher-discount-row').removeAttr('hidden');
				var label = 'Voucher (' + voucherCode + ')';
				$('#pl-voucher-discount-label').text(label);
				$('#pl-voucher-discount-value').text('-' + formatVnd(totalDiscount));
			} else if (totalDiscount <= 0) {
				$('#pl-voucher-discount-row').attr('hidden', 'hidden');
			}

			if (shipping.active) {
				$('#pl-shipping-fee-label').text(feeLabelPrefix + ' — ' + shipping.location);
				$('#pl-shipping-fee-value').text(formatVnd(shipping.fee));
				$shippingRow.removeAttr('hidden');
				syncFormShippingFields(shipping);
			} else {
				$shippingRow.attr('hidden', 'hidden');
				syncFormShippingFields(shipping);
			}

			$('#price_final').text(formatVnd(priceFinal));
			$('input[name="price_final"]').val(priceFinal);

			if (shouldShowBreakdown(qty, shipping)) {
				$breakdown.removeAttr('hidden');
			} else {
				$breakdown.attr('hidden', 'hidden');
			}
		}

		$form.on('submit', function () {
			updateBreakdown();
		});

		$shipping.on('input change', function () {
			updateBreakdown();
		});

		$form.on('change input', 'input.qty', function () {
			updateBreakdown();
		});

		$(document).on('dmc:voucher:updated', function () {
			updateBreakdown();
		});

		updateBreakdown();
	}

	function initScrollLinks() {
		document.querySelectorAll('[data-scroll-to], .pl-detail__scroll-more').forEach(function (link) {
			link.addEventListener('click', function (event) {
				var targetSelector = link.getAttribute('data-scroll-to') || link.getAttribute('href');
				if (!targetSelector || targetSelector.charAt(0) !== '#') {
					return;
				}

				var target = document.querySelector(targetSelector);
				if (!target) {
					return;
				}

				event.preventDefault();
				target.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		});
	}

	function initMobileBuyBar() {
		var bar = document.getElementById('pl-mobile-buy');
		var buyBox = document.getElementById('pl-detail-buy');
		if (!bar || !buyBox || window.matchMedia('(min-width: 901px)').matches) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						bar.setAttribute('hidden', 'hidden');
					} else {
						bar.removeAttribute('hidden');
					}
				});
			},
			{ threshold: 0.15, rootMargin: '0px 0px -80px 0px' }
		);

		observer.observe(buyBox);
	}

	function initReviewFilters() {
		document.querySelectorAll('[data-pl-review-filters]').forEach(function (root) {
			var filters = root.querySelectorAll('.pl-reviews__filter');
			var items = document.querySelectorAll('.pl-reviews__items .comment.pl-review-item');

			if (!filters.length || !items.length) {
				return;
			}

			filters.forEach(function (filter) {
				filter.addEventListener('click', function () {
					var value = filter.getAttribute('data-filter');

					filters.forEach(function (btn) {
						btn.classList.toggle('is-active', btn === filter);
					});

					items.forEach(function (item) {
						if (value === 'all') {
							item.style.display = '';
							return;
						}

						item.style.display = item.classList.contains('pl-review-item--' + value + 'star') ? '' : 'none';
					});
				});
			});
		});
	}

	$(function () {
		initPriceSlider();
		initProductGallery();
		initQtyStepper();
		initProductPriceBreakdown();
		initScrollLinks();
		initMobileBuyBar();
		initReviewFilters();
	});
})(jQuery);
