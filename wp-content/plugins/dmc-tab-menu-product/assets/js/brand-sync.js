(function ($) {
	'use strict';

	var cumulative = {
		processed: 0,
		updated: 0,
		already: 0,
		no_match: 0,
		total: 0,
	};

	function resetCumulative() {
		cumulative.processed = 0;
		cumulative.updated = 0;
		cumulative.already = 0;
		cumulative.no_match = 0;
		cumulative.total = 0;
	}

	function formatProgress() {
		var total = cumulative.total || 1;

		return dmcBrandSync.i18n.progress
			.replace('%1$d', cumulative.processed)
			.replace('%2$d', total)
			.replace('%3$d', cumulative.updated)
			.replace('%4$d', cumulative.already)
			.replace('%5$d', cumulative.no_match);
	}

	function runBatch(offset) {
		return $.post(dmcBrandSync.ajaxUrl, {
			action: 'dmc_tmp_sync_product_brands',
			nonce: dmcBrandSync.nonce,
			offset: offset,
		});
	}

	function syncAll(offset) {
		return runBatch(offset).then(function (response) {
			if (!response || !response.success) {
				var message =
					response && response.data && response.data.message
						? response.data.message
						: dmcBrandSync.i18n.error;
				throw new Error(message);
			}

			var data = response.data;
			cumulative.total = data.total || cumulative.total;

			var processedNow = data.processed || 0;
			cumulative.processed += processedNow;
			cumulative.updated += data.updated || 0;
			cumulative.already += data.already || 0;
			cumulative.no_match += data.no_match || 0;

			var percent =
				cumulative.total > 0
					? Math.min(100, Math.round((cumulative.processed / cumulative.total) * 100))
					: 100;

			$('#dmc-brand-sync-bar').val(percent);
			$('#dmc-brand-sync-status').text(formatProgress());

			if (!data.done && processedNow > 0) {
				return syncAll(offset + processedNow);
			}

			return data;
		});
	}

	$(function () {
		var $btn = $('#dmc-brand-sync-run');
		if (!$btn.length) {
			return;
		}

		$btn.on('click', function () {
			if ($btn.prop('disabled')) {
				return;
			}

			resetCumulative();

			$btn.prop('disabled', true);
			$('#dmc-brand-sync-spinner').addClass('is-active');
			$('#dmc-brand-sync-progress').show();
			$('#dmc-brand-sync-result').hide();
			$('#dmc-brand-sync-error').hide();
			$('#dmc-brand-sync-bar').val(0);
			$('#dmc-brand-sync-status').text(dmcBrandSync.i18n.running);

			syncAll(0)
				.then(function () {
					var summary = dmcBrandSync.i18n.summary
						.replace('%1$d', cumulative.updated)
						.replace('%2$d', cumulative.already)
						.replace('%3$d', cumulative.no_match);

					$('#dmc-brand-sync-result p').text(summary);
					$('#dmc-brand-sync-result').show();
					$('#dmc-brand-sync-status').text(dmcBrandSync.i18n.done);
				})
				.catch(function (err) {
					$('#dmc-brand-sync-error p').text(err.message || dmcBrandSync.i18n.error);
					$('#dmc-brand-sync-error').show();
				})
				.always(function () {
					$btn.prop('disabled', false);
					$('#dmc-brand-sync-spinner').removeClass('is-active');
				});
		});
	});
})(jQuery);
