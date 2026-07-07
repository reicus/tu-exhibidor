(function ($) {
  'use strict';

  function toast(msg, isError) {
    var $t = $('<div class="tuex-sm-toast"/>').text(msg);
    if (isError) $t.css('background', '#b32d2e');
    $('body').append($t);
    setTimeout(function () { $t.fadeOut(300, function () { $t.remove(); }); }, 3200);
  }

  function openMedia(callback) {
    var frame = wp.media({
      title: 'Seleccionar imagen',
      button: { text: 'Usar esta imagen' },
      library: { type: 'image' },
      multiple: false
    });
    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      callback(attachment);
    });
    frame.open();
  }

  $(document).on('click', '.tuex-sm-replace', function () {
    var $card = $(this).closest('.tuex-sm-card');
    var type = $card.data('type');

    openMedia(function (attachment) {
      $card.addClass('is-loading');
      $.post(TuexSiteManager.ajaxUrl, {
        action: 'tuex_sm_replace_image',
        nonce: TuexSiteManager.nonce,
        item_type: type,
        attachment_id: attachment.id,
        slug: $card.data('slug'),
        index: $card.data('index'),
        brand: $card.data('brand'),
        sync_wc: $('#tuex-sm-sync-wc').is(':checked') ? 1 : 0
      }).done(function (res) {
        if (res.success) {
          var url = res.data.preview + '?v=' + (res.data.cacheVer || Date.now());
          $card.find('.tuex-sm-thumb img').attr('src', url);
          toast(res.data.message || 'Listo');
        } else {
          toast((res.data && res.data.message) || 'Error al guardar', true);
        }
      }).fail(function () {
        toast('Error de conexión', true);
      }).always(function () {
        $card.removeClass('is-loading');
      });
    });
  });

  $(document).on('click', '.tuex-sm-save-alt', function () {
    var $card = $(this).closest('.tuex-sm-card');
    $.post(TuexSiteManager.ajaxUrl, {
      action: 'tuex_sm_save_alt',
      nonce: TuexSiteManager.nonce,
      item_type: 'hero',
      index: $card.data('index'),
      alt: $card.find('.tuex-sm-alt').val()
    }).done(function (res) {
      toast(res.success ? (res.data.message || 'Guardado') : (res.data.message || 'Error'), !res.success);
    });
  });
})(jQuery);
