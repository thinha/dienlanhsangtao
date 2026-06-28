(function ($) {
	'use strict';

	function togglePercentRow() {
		var isPercent = $('#dmc_voucher_type').val() === 'percent';
		$('.dmc-voucher-row-percent').toggle(isPercent);
	}

	$(function () {
		togglePercentRow();
		$('#dmc_voucher_type').on('change', togglePercentRow);
	});
})(jQuery);
