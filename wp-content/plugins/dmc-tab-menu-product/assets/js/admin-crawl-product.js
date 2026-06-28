(function ($) {
  'use strict';

  const cfg = window.dmcCrawlProduct || {};
  const fields = cfg.fields || {};
  const i18n = cfg.i18n || {};

  const $url = $('#dmc-crawl-url');
  const $run = $('#dmc-crawl-run');
  const $spinner = $('#dmc-crawl-spinner');
  const $error = $('#dmc-crawl-error');
  const $result = $('#dmc-crawl-result');
  const $summary = $('#dmc-crawl-summary');
  const $fields = $('#dmc-crawl-fields');
  const $form = $('#dmc-crawl-form');
  const $createSpinner = $('#dmc-crawl-create-spinner');
  const $success = $('#dmc-crawl-success');

  function groupLabel(group) {
    if (group === 'acf') return i18n.groupAcf || 'ACF';
    if (group === 'meta') return i18n.groupMeta || 'Meta';
    return i18n.groupWoo || 'WooCommerce';
  }

  function hasValue(value) {
    return String(value || '').trim() !== '';
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function renderSummary(summary) {
    const filled = (summary.filled || []).length;
    const missing = (summary.missing || []).length;
    const reqMissing = (summary.required_missing || []).length;

    $summary.html(
      [
        '<span class="dmc-crawl-product__badge dmc-crawl-product__badge--filled">' +
          (i18n.summaryFilled || 'Đã crawl: %d trường').replace('%d', filled) +
          '</span>',
        '<span class="dmc-crawl-product__badge dmc-crawl-product__badge--missing">' +
          (i18n.summaryMissing || 'Cần điền: %d trường').replace('%d', missing) +
          '</span>',
        reqMissing
          ? '<span class="dmc-crawl-product__badge dmc-crawl-product__badge--required">' +
            (i18n.summaryRequired || 'Bắt buộc còn thiếu: %d').replace('%d', reqMissing) +
            '</span>'
          : '',
      ].join('')
    );
  }

  function fieldInput(key, def, value) {
    const val = escapeHtml(value || '');
    const type = def.type || 'text';

    if (type === 'textarea') {
      return (
        '<textarea name="product[' +
        key +
        ']" data-field="' +
        key +
        '" rows="4">' +
        val +
        '</textarea>'
      );
    }

    if (type === 'select') {
      const choices = def.choices || {};
      let html =
        '<select name="product[' + key + ']" data-field="' + key + '">';
      Object.keys(choices).forEach(function (choiceKey) {
        const selected = String(choiceKey) === String(value) ? ' selected' : '';
        html +=
          '<option value="' +
          escapeHtml(choiceKey) +
          '"' +
          selected +
          '>' +
          escapeHtml(choices[choiceKey]) +
          '</option>';
      });
      html += '</select>';
      return html;
    }

    const inputType = type === 'number' ? 'number' : type === 'url' ? 'url' : 'text';
    const step = type === 'number' ? ' step="1000" min="0"' : '';
    return (
      '<input type="' +
      inputType +
      '" name="product[' +
      key +
      ']" data-field="' +
      key +
      '" value="' +
      val +
      '"' +
      step +
      ' />'
    );
  }

  function renderFields(values, summary) {
    const filledSet = new Set(summary.filled || []);
    const groups = { woocommerce: [], acf: [], meta: [] };

    Object.keys(fields).forEach(function (key) {
      const def = fields[key];
      const group = def.group || 'woocommerce';
      if (!groups[group]) groups[group] = [];
      groups[group].push({ key: key, def: def });
    });

    let html = '';

    ['woocommerce', 'acf', 'meta'].forEach(function (group) {
      const items = groups[group];
      if (!items || !items.length) return;

      html += '<div class="dmc-crawl-product__group">';
      html += '<h3 class="dmc-crawl-product__group-title">' + escapeHtml(groupLabel(group)) + '</h3>';
      html += '<table class="dmc-crawl-product__fields"><tbody>';

      items.forEach(function (item) {
        const key = item.key;
        const def = item.def;
        const value = values[key] || '';
        const isFilled = filledSet.has(key) && hasValue(value);
        const statusClass = isFilled ? 'filled' : 'missing';
        const statusText = isFilled ? i18n.filled || 'Đã có' : i18n.missing || 'Chưa có';
        const rowClass =
          def.required && !hasValue(value) ? ' dmc-crawl-product__field is-required-missing' : ' dmc-crawl-product__field';

        html += '<tr class="' + rowClass.trim() + '">';
        html += '<th>' + escapeHtml(def.label) + (def.required ? ' *' : '') + '</th>';
        html += '<td>';
        html += fieldInput(key, def, value);
        if (key === 'featured_image' && hasValue(value)) {
          html +=
            '<img src="' +
            escapeHtml(value) +
            '" alt="" class="dmc-crawl-product__preview-img" loading="lazy" />';
        }
        if (key === 'gallery_images' && hasValue(value)) {
          const count = value.split(/\r?\n/).filter(function (line) {
            return line.trim() !== '';
          }).length;
          html +=
            '<span class="dmc-crawl-product__hint">' +
            escapeHtml(String(count)) +
            ' ảnh → Thư viện sản phẩm WooCommerce</span>';
        }
        if (def.required) {
          html += '<span class="dmc-crawl-product__hint">' + escapeHtml(i18n.required || 'Bắt buộc') + '</span>';
        }
        html += '</td>';
        html += '<td><span class="dmc-crawl-product__status dmc-crawl-product__status--' + statusClass + '">' + escapeHtml(statusText) + '</span></td>';
        html += '</tr>';
      });

      html += '</tbody></table></div>';
    });

    $fields.html(html);
  }

  function showError(message) {
    $error.show().find('p').text(message || i18n.error);
    $success.hide();
  }

  function hideError() {
    $error.hide();
  }

  function updateRowStatus($input) {
    const $row = $input.closest('tr');
    const $status = $row.find('.dmc-crawl-product__status');
    const filled = hasValue($input.val());

    $status
      .removeClass('dmc-crawl-product__status--filled dmc-crawl-product__status--missing')
      .addClass(filled ? 'dmc-crawl-product__status--filled' : 'dmc-crawl-product__status--missing')
      .text(filled ? i18n.filled || 'Đã có' : i18n.missing || 'Chưa có');

    const isRequired = $row.find('.dmc-crawl-product__hint').length > 0;
    $row.toggleClass('is-required-missing', isRequired && !filled);
  }

  $run.on('click', function () {
    const url = ($url.val() || '').trim();
    if (!url) {
      showError(i18n.invalidUrl);
      return;
    }

    hideError();
    $success.hide();
    $run.prop('disabled', true);
    $spinner.addClass('is-active');

    $.post(cfg.ajaxUrl, {
      action: 'dmc_tmp_crawl_product',
      nonce: cfg.nonce,
      url: url,
    })
      .done(function (res) {
        if (!res || !res.success) {
          showError((res && res.data && res.data.message) || i18n.error);
          return;
        }

        renderSummary(res.data.summary || {});
        renderFields(res.data.values || {}, res.data.summary || {});
        $result.show();
        $('html, body').animate({ scrollTop: $result.offset().top - 40 }, 300);
      })
      .fail(function () {
        showError(i18n.error);
      })
      .always(function () {
        $run.prop('disabled', false);
        $spinner.removeClass('is-active');
      });
  });

  $fields.on('input change', 'input, textarea, select', function () {
    updateRowStatus($(this));
  });

  $form.on('submit', function (e) {
    e.preventDefault();
    hideError();
    $success.hide();

    const product = {};
    $form.find('[name^="product["]').each(function () {
      const match = this.name.match(/^product\[([^\]]+)\]$/);
      if (match) product[match[1]] = $(this).val();
    });

    $('#dmc-crawl-create').prop('disabled', true);
    $createSpinner.addClass('is-active');

    $.post(cfg.ajaxUrl, {
      action: 'dmc_tmp_create_crawled_product',
      nonce: cfg.nonce,
      product: product,
    })
      .done(function (res) {
        if (!res || !res.success) {
          showError((res && res.data && res.data.message) || i18n.error);
          return;
        }

        const editUrl = res.data.edit_url || '#';
        $success
          .show()
          .find('p')
          .html(
            escapeHtml(res.data.message || '') +
              ' <a href="' +
              escapeHtml(editUrl) +
              '">#' +
              escapeHtml(String(res.data.product_id || '')) +
              '</a>'
          );
        $('html, body').animate({ scrollTop: $success.offset().top - 40 }, 300);
      })
      .fail(function () {
        showError(i18n.error);
      })
      .always(function () {
        $('#dmc-crawl-create').prop('disabled', false);
        $createSpinner.removeClass('is-active');
      });
  });
})(jQuery);
