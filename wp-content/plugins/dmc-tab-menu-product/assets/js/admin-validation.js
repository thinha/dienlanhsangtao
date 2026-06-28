(function ($) {
  'use strict';

  if (typeof acf === 'undefined') {
    return;
  }

  function findTabKeyForInvalidField($field) {
    var $group = $field.closest('.acf-field[data-key="group_dmc_tab_menu_product"] .acf-fields').first();
    if (!$group.length) {
      $group = $field.closest('.acf-fields').first();
    }

    var tabKey = null;
    var matched = false;

    $group.children('.acf-field').each(function () {
      var $section = $(this);

      if (matched) {
        return false;
      }

      if ($section.data('type') === 'tab') {
        tabKey = $section.data('key');
      }

      if ($section.is($field) || $section.has($field).length) {
        matched = true;
        return false;
      }
    });

    return tabKey;
  }

  function activateTab(tabKey) {
    if (!tabKey) {
      return;
    }

    var field = acf.getField(tabKey);
    if (field && typeof field.open === 'function') {
      field.open();
      return;
    }

    var $button = $('.acf-tab-button[data-key="' + tabKey + '"]');
    if ($button.length) {
      $button.trigger('click');
    }
  }

  function focusFirstInvalidField() {
    var $invalid = $('.acf-field.-invalid').first();
    if (!$invalid.length) {
      return;
    }

    activateTab(findTabKeyForInvalidField($invalid));

    window.setTimeout(function () {
      var top = $invalid.offset().top - 120;
      $('html, body').animate({ scrollTop: Math.max(0, top) }, 250);
      $invalid.addClass('dmc-tmp-field-invalid-flash');
      window.setTimeout(function () {
        $invalid.removeClass('dmc-tmp-field-invalid-flash');
      }, 1800);
    }, 80);
  }

  function enhanceValidationNotice(form) {
    if (!form || !form.hasErrors || !form.hasErrors()) {
      return;
    }

    var fieldErrors = form.getFieldErrors();
    if (!fieldErrors.length) {
      return;
    }

    var messages = [];
    fieldErrors.forEach(function (error) {
      if (error.message) {
        messages.push(error.message);
      }
    });

    if (!messages.length) {
      return;
    }

    var text;
    if (messages.length === 1) {
      text = 'Lưu thất bại: ' + messages[0];
    } else {
      text =
        'Lưu thất bại — ' +
        messages.length +
        ' trường cần kiểm tra: ' +
        messages.join('; ');
    }

    if (form.has('notice')) {
      form.get('notice').update({
        type: 'error',
        text: text,
      });
    }
  }

  acf.addAction('validation_failure', function ($el, form) {
    form.setTimeout(function () {
      enhanceValidationNotice(form);
      focusFirstInvalidField();
    }, 0);
  });
})(jQuery);
