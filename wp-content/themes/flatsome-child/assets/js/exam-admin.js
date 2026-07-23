(function () {
	'use strict';

	var config = window.dmcExamAdmin || {};
	var form = document.getElementById('dmc-jf-bulk-form');

	if (!form) {
		return;
	}

	var selectAll = document.getElementById('dmc-jf-select-all');
	var rowChecks = form.querySelectorAll('.dmc-jf__row-check');
	var bulkAction = document.getElementById('dmc-jf-bulk-action');
	var bulkCount = document.getElementById('dmc-jf-bulk-count');

	function getCheckedRows() {
		return form.querySelectorAll('.dmc-jf__row-check:checked');
	}

	function updateBulkCount() {
		if (!bulkCount) {
			return;
		}

		var count = getCheckedRows().length;

		if (count > 0) {
			bulkCount.hidden = false;
			bulkCount.textContent = 'Đã chọn: ' + count;
		} else {
			bulkCount.hidden = true;
			bulkCount.textContent = '';
		}
	}

	function syncSelectAllState() {
		if (!selectAll || !rowChecks.length) {
			return;
		}

		var checked = getCheckedRows().length;

		selectAll.checked = checked > 0 && checked === rowChecks.length;
		selectAll.indeterminate = checked > 0 && checked < rowChecks.length;
	}

	if (selectAll) {
		selectAll.addEventListener('change', function () {
			for (var i = 0; i < rowChecks.length; i++) {
				rowChecks[i].checked = selectAll.checked;
			}

			updateBulkCount();
		});
	}

	for (var j = 0; j < rowChecks.length; j++) {
		rowChecks[j].addEventListener('change', function () {
			syncSelectAllState();
			updateBulkCount();
		});
	}

	form.addEventListener('submit', function (event) {
		var action = bulkAction ? bulkAction.value : '';
		var checked = getCheckedRows();

		if ('delete' !== action) {
			event.preventDefault();
			window.alert(config.messages.selectItems || 'Vui lòng chọn hành động hàng loạt.');
			return;
		}

		if (!checked.length) {
			event.preventDefault();
			window.alert(config.messages.selectItems || 'Vui lòng chọn ít nhất một kết quả để xóa.');
			return;
		}

		if (!window.confirm(config.messages.confirmDelete || 'Bạn có chắc muốn xóa các kết quả đã chọn?')) {
			event.preventDefault();
		}
	});
})();
