(function ($) {
  'use strict';

  if (typeof acf === 'undefined') {
    return;
  }

  var FIELD_KEY = 'field_tmp_shipping_locations';

  function getRows($table) {
    return $table.find('> tbody > tr.acf-row').not('.acf-clone');
  }

  function addRowCheckbox($row) {
    if ($row.hasClass('acf-clone') || $row.find('.dmc-shipping-bulk-checkbox').length) {
      return;
    }

    $row.prepend(
      '<td class="dmc-shipping-bulk-col acf-row-handle">' +
        '<input type="checkbox" class="dmc-shipping-bulk-checkbox" aria-label="Chọn khu vực" />' +
      '</td>'
    );
  }

  function enhanceShippingRepeater(field) {
    if (field.get('key') !== FIELD_KEY) {
      return;
    }

    var $field = field.$el;
    if ($field.data('dmc-bulk-init')) {
      return;
    }
    $field.data('dmc-bulk-init', true);

    var $table = $field.find('table.acf-table').first();
    if (!$table.length) {
      return;
    }

    var $toolbar = $(
      '<div class="dmc-shipping-bulk-toolbar">' +
        '<label class="dmc-shipping-bulk-select-all">' +
          '<input type="checkbox" class="dmc-shipping-bulk-select-all-input" />' +
          ' <span>Chọn tất cả</span>' +
        '</label>' +
        '<button type="button" class="button dmc-shipping-bulk-delete" disabled>Xóa đã chọn</button>' +
        '<span class="dmc-shipping-bulk-count" aria-live="polite"></span>' +
      '</div>'
    );

    $field.find('.acf-input > .acf-repeater').first().before($toolbar);

    var $theadRow = $table.find('> thead > tr').first();
    $theadRow.prepend(
      '<th class="dmc-shipping-bulk-col acf-row-handle" scope="col">' +
        '<span class="screen-reader-text">Chọn</span>' +
      '</th>'
    );

    getRows($table).each(function () {
      addRowCheckbox($(this));
    });

    function getSelectedRows() {
      return getRows($table).filter(function () {
        return $(this).find('.dmc-shipping-bulk-checkbox').prop('checked');
      });
    }

    function updateUI() {
      var $rows = getRows($table);
      var $selected = getSelectedRows();
      var count = $selected.length;
      var total = $rows.length;

      $toolbar.find('.dmc-shipping-bulk-delete').prop('disabled', count === 0);

      var $count = $toolbar.find('.dmc-shipping-bulk-count');
      if (count > 0) {
        $count.text(count + ' / ' + total + ' khu vực đã chọn');
      } else {
        $count.text('');
      }

      var $selectAll = $toolbar.find('.dmc-shipping-bulk-select-all-input');
      $selectAll.prop('checked', total > 0 && count === total);
      $selectAll.prop('indeterminate', count > 0 && count < total);
    }

    $toolbar.on('change', '.dmc-shipping-bulk-select-all-input', function () {
      var checked = $(this).prop('checked');
      getRows($table).find('.dmc-shipping-bulk-checkbox').prop('checked', checked);
      updateUI();
    });

    $table.on('change', '.dmc-shipping-bulk-checkbox', updateUI);

    $toolbar.on('click', '.dmc-shipping-bulk-delete', function () {
      var $selected = getSelectedRows();
      if (!$selected.length) {
        return;
      }

      var message =
        'Xóa ' +
        $selected.length +
        ' khu vực đã chọn?\n\nThay đổi chỉ được lưu sau khi bạn bấm Update.';

      if (!window.confirm(message)) {
        return;
      }

      $selected.get().reverse().forEach(function (row) {
        $(row).find('[data-event="remove-row"]').trigger('click');
      });

      $toolbar.find('.dmc-shipping-bulk-select-all-input').prop('checked', false);
      updateUI();
    });

    field.on('append', function (row) {
      addRowCheckbox(row.$el);
      updateUI();
    });

    field.on('remove', updateUI);

    updateUI();
  }

  acf.addAction('ready_field/key=' + FIELD_KEY, enhanceShippingRepeater);
  acf.addAction('ready_field/name=tmp_shipping_locations', enhanceShippingRepeater);

  acf.addAction('ready', function () {
    var field = acf.getField(FIELD_KEY);
    if (field) {
      enhanceShippingRepeater(field);
    }
  });
})(jQuery);
