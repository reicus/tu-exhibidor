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
        category: $card.data('category'),
        static: $card.data('static'),
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
      item_type: $card.data('type'),
      index: $card.data('index'),
      category: $card.data('category'),
      static: $card.data('static'),
      alt: $card.find('.tuex-sm-alt').val()
    }).done(function (res) {
      toast(res.success ? (res.data.message || 'Guardado') : (res.data.message || 'Error'), !res.success);
    });
  });

  /* —— Más pedidos —— */
  var $featuredList = $('#tuex-sm-featured-list');
  if ($featuredList.length) {
    var catalog = [];
    try {
      catalog = JSON.parse($('#tuex-sm-catalog-json').text() || '[]');
    } catch (e) { catalog = []; }
    var catalogByCode = {};
    catalog.forEach(function (p) { catalogByCode[p.code] = p; });
    var cacheVer = TuexSiteManager.cacheVer || Date.now();

    function featuredCodes() {
      return $featuredList.find('.tuex-sm-featured-item').map(function () {
        return $(this).data('code');
      }).get();
    }

    function renumberFeatured() {
      $featuredList.find('.tuex-sm-featured-item').each(function (i) {
        $(this).find('.tuex-sm-featured-order').text(i + 1);
      });
      $('#tuex-sm-featured-count').text(featuredCodes().length + ' productos');
    }

    function addFeaturedItem(p) {
      if (!p || !p.code) return;
      if ($featuredList.find('[data-code="' + p.code + '"]').length) {
        toast('Ese producto ya está en la lista', true);
        return;
      }
      var img = p.image ? p.image + '?v=' + cacheVer : '';
      var thumb = img
        ? '<img src="' + img + '" alt="">'
        : '<span class="tuex-sm-no-img">Sin imagen</span>';
      var name = (p.name || '').substring(0, 80);
      var $li = $(
        '<li class="tuex-sm-featured-item" data-code="' + p.code + '">' +
          '<span class="tuex-sm-featured-order"></span>' +
          '<div class="tuex-sm-featured-thumb">' + thumb + '</div>' +
          '<span class="tuex-sm-featured-meta"><strong>' + p.code + '</strong> ' + name + '</span>' +
          '<span class="tuex-sm-featured-actions">' +
            '<button type="button" class="button tuex-sm-featured-up" title="Subir">↑</button>' +
            '<button type="button" class="button tuex-sm-featured-down" title="Bajar">↓</button>' +
            '<button type="button" class="button tuex-sm-featured-remove" title="Quitar">✕</button>' +
          '</span>' +
        '</li>'
      );
      $featuredList.append($li);
      renumberFeatured();
    }

    var $search = $('#tuex-sm-featured-search');
    var $suggestions = $('#tuex-sm-featured-suggestions');

    $search.on('input', function () {
      var q = ($search.val() || '').trim().toLowerCase();
      if (q.length < 2) {
        $suggestions.empty().prop('hidden', true);
        return;
      }
      var selected = featuredCodes();
      var hits = catalog.filter(function (p) {
        if (selected.indexOf(p.code) >= 0) return false;
        var hay = (p.code + ' ' + p.name).toLowerCase();
        return hay.indexOf(q) >= 0;
      }).slice(0, 12);
      if (!hits.length) {
        $suggestions.html('<p class="tuex-sm-suggest-empty">Sin resultados</p>').prop('hidden', false);
        return;
      }
      $suggestions.html(hits.map(function (p) {
        return '<button type="button" class="tuex-sm-suggest-item" data-code="' + p.code + '">' +
          '<strong>' + p.code + '</strong> ' + (p.name || '').substring(0, 60) +
        '</button>';
      }).join('')).prop('hidden', false);
    });

    $suggestions.on('click', '.tuex-sm-suggest-item', function () {
      var code = $(this).data('code');
      addFeaturedItem(catalogByCode[code]);
      $search.val('');
      $suggestions.empty().prop('hidden', true);
    });

    $(document).on('click', function (e) {
      if (!$(e.target).closest('.tuex-sm-featured-add').length) {
        $suggestions.empty().prop('hidden', true);
      }
    });

    $featuredList.on('click', '.tuex-sm-featured-up', function () {
      var $item = $(this).closest('.tuex-sm-featured-item');
      var $prev = $item.prev();
      if ($prev.length) $item.insertBefore($prev);
      renumberFeatured();
    });

    $featuredList.on('click', '.tuex-sm-featured-down', function () {
      var $item = $(this).closest('.tuex-sm-featured-item');
      var $next = $item.next();
      if ($next.length) $item.insertAfter($next);
      renumberFeatured();
    });

    $featuredList.on('click', '.tuex-sm-featured-remove', function () {
      $(this).closest('.tuex-sm-featured-item').remove();
      renumberFeatured();
    });

    $('#tuex-sm-featured-save').on('click', function () {
      var skus = featuredCodes();
      if (!skus.length) {
        toast('Añade al menos un producto', true);
        return;
      }
      var $btn = $(this).prop('disabled', true).addClass('is-loading');
      $.post(TuexSiteManager.ajaxUrl, {
        action: 'tuex_sm_save_featured',
        nonce: TuexSiteManager.nonce,
        skus: JSON.stringify(skus)
      }).done(function (res) {
        if (res.success) {
          cacheVer = res.data.cacheVer || Date.now();
          toast(res.data.message || 'Guardado');
        } else {
          toast((res.data && res.data.message) || 'Error al guardar', true);
        }
      }).fail(function () {
        toast('Error de conexión', true);
      }).always(function () {
        $btn.prop('disabled', false).removeClass('is-loading');
      });
    });
  }
})(jQuery);
