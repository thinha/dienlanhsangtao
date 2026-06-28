(function ($) {
	'use strict';

	function getSpecsField() {
		return $('#acf-group_dmc_product_detail .acf-field[data-name="pl_technical_specs"]');
	}

	function getEditorContent($field) {
		var $textarea = $field.find('textarea');
		if (!$textarea.length) {
			return '';
		}

		var id = $textarea.attr('id');
		if (typeof tinymce !== 'undefined' && id && tinymce.get(id) && !tinymce.get(id).isHidden()) {
			return tinymce.get(id).getContent();
		}

		return $textarea.val();
	}

	function setEditorContent($field, html) {
		var $textarea = $field.find('textarea');
		$textarea.val(html);

		var id = $textarea.attr('id');
		if (typeof tinymce !== 'undefined' && id && tinymce.get(id)) {
			tinymce.get(id).setContent(html);
		}

		$textarea.trigger('change');
	}

	function bindFormatButton() {
		$(document)
			.off('click.dmcFormatSpecs', '#dmc-format-specs-btn')
			.on('click.dmcFormatSpecs', '#dmc-format-specs-btn', function (e) {
				e.preventDefault();

				if (typeof dmcProductDetailAdmin === 'undefined') {
					window.alert('Script admin chưa tải. Vui lòng tải lại trang.');
					return;
				}

				var $btn = $(this);
				var $field = getSpecsField();

				if (!$field.length) {
					window.alert('Không tìm thấy ô nhập thông số kỹ thuật.');
					return;
				}

				var content = getEditorContent($field);

				if (!content || !String(content).trim()) {
					window.alert('Chưa có nội dung để format.');
					return;
				}

				$btn.prop('disabled', true).text('Đang format…');

				$.ajax({
					url: dmcProductDetailAdmin.ajaxUrl,
					type: 'POST',
					contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
					data: {
						action: 'dmc_format_technical_specs',
						nonce: dmcProductDetailAdmin.nonce,
						html: content,
					},
				})
					.done(function (res) {
						if (res && res.success && res.data && typeof res.data.html === 'string') {
							setEditorContent($field, res.data.html);
							$field.find('.acf-tab-button[data-key="text"]').trigger('click');
							return;
						}

						var message =
							res && res.data && res.data.message
								? res.data.message
								: 'Format thất bại.';
						window.alert(message);
					})
					.fail(function () {
						window.alert('Không thể format. Vui lòng thử lại.');
					})
					.always(function () {
						$btn.prop('disabled', false).text('Format');
					});
			});
	}

	$(function () {
		bindFormatButton();
	});

	if (typeof acf !== 'undefined') {
		acf.addAction('ready', bindFormatButton);
	}
})(jQuery);
