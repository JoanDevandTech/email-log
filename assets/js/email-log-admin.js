(function ($) {
  // pro dialog
  $('a.nav-tab-pro').on('click', function (e) {
    e.preventDefault();

    open_upsell('tab');

    return false;
  });

  $('#wpwrap').on('change', 'select', function(e) {
    option_class = $('#' + $(this).attr('id') + ' :selected').attr('class');
    if(option_class == 'pro-option'){
        option_text = $('#' + $(this).attr('id') + ' :selected').text();
        $(this).val('-1');
        $(this).trigger('change');
        open_upsell($(this).attr('id'));
        $('.show_if_' + $(this).attr('id')).hide();
    }
  });

  $('#wpwrap').on('click', '.open-upsell', function(e) {
    e.preventDefault();
    feature = $(this).data('feature');
    $(this).blur();
    open_upsell(feature);

    return false;
  });

  $('#wpwrap').on('click', '.open-pro-dialog', function (e) {
    e.preventDefault();
    $(this).blur();

    pro_feature = $(this).data('pro-feature');
    if (!pro_feature) {
      pro_feature = $(this).parent('label').attr('for');
    }
    open_upsell(pro_feature);

    return false;
  });

  $('#emaillog-pro-dialog').dialog({
    dialogClass: 'wp-dialog emaillog-pro-dialog',
    modal: true,
    resizable: false,
    width: 850,
    height: 'auto',
    show: 'fade',
    hide: 'fade',
    close: function (event, ui) {},
    open: function (event, ui) {
      $(this).siblings().find('span.ui-dialog-title').html('Email Log PRO is here!');
      emaillog_fix_dialog_close(event, ui);
    },
    autoOpen: false,
    closeOnEscape: true,
  });

  function clean_feature(feature) {
    feature = feature || 'free-plugin-unknown';
    feature = feature.toLowerCase();
    feature = feature.replace(' ', '-');

    return feature;
  }

  function open_upsell(feature) {
    feature = clean_feature(feature);

    $('#emaillog-pro-dialog').dialog('open');

    $('#emaillog-pro-table .button-buy').each(function (ind, el) {
      tmp = $(el).data('href-org');
      tmp = tmp.replace('pricing-table', feature);
      $(el).attr('href', tmp);
    });
  } // open_upsell

  if (window.localStorage.getItem('emaillog_upsell_shown') != 'true') {
    open_upsell('welcome');

    window.localStorage.setItem('emaillog_upsell_shown', 'true');
    window.localStorage.setItem('emaillog_upsell_shown_timestamp', new Date().getTime());
  }

  if (window.location.hash == '#open-pro-dialog') {
    open_upsell('url-hash');
    window.location.hash = '';
  }

  $('.install-wp301').on('click',function(e){
    e.preventDefault();

    if (!confirm('The free WP 301 Redirects plugin will be installed & activated from the official WordPress repository.')) {
      return false;
    }

    jQuery('body').append('<div style="width:550px;height:450px; position:fixed;top:10%;left:50%;margin-left:-275px; color:#444; background-color: #fbfbfb;border:1px solid #DDD; border-radius:4px;box-shadow: 0px 0px 0px 4000px rgba(0, 0, 0, 0.85);z-index: 9999999;"><iframe src="' + wp_email_log.wp301_install_url + '" style="width:100%;height:100%;border:none;" /></div>');
    jQuery('#wpwrap').css('pointer-events', 'none');

    e.preventDefault();
    return false;
  });

  function emaillog_fix_dialog_close(event, ui) {
    jQuery('.ui-widget-overlay').bind('click', function () {
      jQuery('#' + event.target.id).dialog('close');
    });
  } // emaillog_fix_dialog_close

  $('#bulk-action-selector-top option').each(function() {
    const value = $(this).val();

    // Add your condition(s) here — example:
    if (value === 'el-log-export' || value === 'el-log-export-all' || value === 'el-log-resend' || value === 'el-log-resend-all') {
      $(this).addClass('pro-option');
    }
  });

})(jQuery);
