/**
 * Donk Toss — WooCommerce Variation Gallery Synchronizer
 *
 * Automatically syncs variable product dropdown selects when users click
 * gallery thumbnails or slides that match specific variation images.
 *
 * @package Donk Toss
 * @since 4.9.3
 */

(function($) {
  'use strict';

  /**
   * Helper: Normalize image URL to a comparable base filename token.
   * Strips scheme, host, query string, extension, and standard WP thumbnail dimensions (-NxN).
   *
   * Example: "https://donktoss.com/wp-content/uploads/DONKpro-Photo-Purple-300x300.png?v=1"
   *       => "donkpro-photo-purple"
   */
  function normalizeImageToken(url) {
    if (!url || typeof url !== 'string') return '';
    var clean = url.split('?')[0].split('#')[0];
    var filename = clean.substring(clean.lastIndexOf('/') + 1);
    // Strip file extension
    filename = filename.replace(/\.[a-zA-Z0-9]+$/, '');
    // Strip WordPress dimension suffix (e.g. -600x600, -800x800, -1536x1536)
    filename = filename.replace(/-\d+x\d+$/, '');
    return decodeURIComponent(filename).toLowerCase().trim();
  }

  /**
   * Collect all normalized image tokens from a DOM element (and its children/attributes).
   */
  function collectElementImageTokens(element) {
    var tokens = [];
    if (!element) return tokens;

    var $el = $(element);
    var attributesToCheck = [
      'src',
      'data-src',
      'data-large_image',
      'data-thumb',
      'href',
      'data-o_src',
      'data-o_href',
      'data-o_data-thumb',
      'data-o_data-src',
      'data-o_data-large_image'
    ];

    $el.find('img, a').addBack().each(function() {
      var domNode = this;
      attributesToCheck.forEach(function(attr) {
        var val = domNode.getAttribute ? domNode.getAttribute(attr) : null;
        if (val) {
          var token = normalizeImageToken(val);
          if (token && tokens.indexOf(token) === -1) {
            tokens.push(token);
          }
        }
      });
    });

    return tokens;
  }

  /**
   * Main handler when a gallery thumbnail or slide is clicked.
   */
  function onGalleryThumbnailClick(e) {
    var $target = $(e.target).closest(
      '.ast-woocommerce-product-gallery__image, .flex-control-thumbs li, .flex-control-nav li, .woocommerce-product-gallery__image'
    );
    if (!$target.length) return;

    var $gallery = $target.closest('.woocommerce-product-gallery');
    var $form = $('form.variations_form');
    if (!$form.length) return;

    var variations = $form.data('product_variations');
    if (!variations || !variations.length) return;

    // Collect candidate tokens from clicked element
    var candidateTokens = collectElementImageTokens($target[0]);

    // Also look up corresponding main slide by index if available
    var targetIndex = $target.index();
    if (targetIndex >= 0 && $gallery.length) {
      var $mainSlide = $gallery.find('.woocommerce-product-gallery__wrapper .woocommerce-product-gallery__image').eq(targetIndex);
      if ($mainSlide.length) {
        var slideTokens = collectElementImageTokens($mainSlide[0]);
        slideTokens.forEach(function(tok) {
          if (tok && candidateTokens.indexOf(tok) === -1) {
            candidateTokens.push(tok);
          }
        });
      }
    }

    if (!candidateTokens.length) return;

    // Search variations for a matching image token
    var matchedVariation = null;
    for (var i = 0; i < variations.length; i++) {
      var v = variations[i];
      if (!v.image) continue;

      var varTokens = [
        normalizeImageToken(v.image.src),
        normalizeImageToken(v.image.full_src),
        normalizeImageToken(v.image.url),
        normalizeImageToken(v.image.thumb_src),
        normalizeImageToken(v.image.gallery_thumbnail_src)
      ];

      var isMatch = varTokens.some(function(vTok) {
        return vTok && candidateTokens.indexOf(vTok) !== -1;
      });

      if (isMatch) {
        matchedVariation = v;
        break;
      }
    }

    // If a matching variation was found, update the form selects
    if (matchedVariation && matchedVariation.attributes) {
      var hasChanged = false;
      $.each(matchedVariation.attributes, function(attrName, attrVal) {
        var $select = $form.find('select[name="' + attrName + '"]');
        if (!$select.length) {
          $select = $form.find('select').filter(function() {
            return this.name === attrName || this.name.toLowerCase() === attrName.toLowerCase();
          });
        }

        if ($select.length && attrVal !== '' && $select.val() !== attrVal) {
          $select.val(attrVal);
          hasChanged = true;
        }
      });

      if (hasChanged) {
        // Trigger change on the first variation select so WooCommerce updates state
        $form.find('select').first().trigger('change');
      }
    }
  }

  // Initialize event delegation on document ready
  $(function() {
    $(document).on(
      'click',
      '.woocommerce-product-gallery .ast-woocommerce-product-gallery__image, ' +
      '.woocommerce-product-gallery .flex-control-thumbs li, ' +
      '.woocommerce-product-gallery .flex-control-nav li, ' +
      '.woocommerce-product-gallery .woocommerce-product-gallery__image',
      onGalleryThumbnailClick
    );
  });

})(jQuery);
