/**
 * Promo Hint JavaScript Component
 */
define([
    'jquery',
    'mage/translate',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('mage.spalenza_promohint', {
        options: {
            ajaxUrl: '',
            productIds: []
        },

        /**
         * Widget initialization
         * @private
         */
        _create: function () {
            this._loadPromoHints();

            // Reload when cart is updated
            $(document).on('ajaxComplete', $.proxy(function (event, xhr, settings) {
                if (settings.url && settings.url.indexOf('checkout/sidebar') !== -1) {
                    this._loadPromoHints();
                }
            }, this));
        },

        /**
         * Load promo hints via AJAX
         * @private
         */
        _loadPromoHints: function () {
            var self = this;
            var $loader = this.element.find('.spalenza-promo-hints-loader');
            var $content = this.element.find('.spalenza-promo-hints-content');

            // Show loader
            $loader.show();
            $content.empty();

            $.ajax({
                url: this.options.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: window.FORM_KEY
                },
                success: function (response) {
                    $loader.hide();

                    if (response.success && response.hints && response.hints.length > 0) {
                        self._renderHints(response.hints);
                    } else {
                        $content.hide();
                    }
                },
                error: function () {
                    $loader.hide();
                    $content.hide();
                }
            });
        },

        /**
         * Render promo hints
         * @param {Array} hints
         * @private
         */
        _renderHints: function (hints) {
            var $content = this.element.find('.spalenza-promo-hints-content');
            var $container = $('<div>').addClass('spalenza-promo-hints');

            // Add title
            var $title = $('<h3>').text($.mage.__('Available Promotions'));
            $container.append($title);

            // Create list
            var $list = $('<ul>').addClass('promo-hints-list');

            // Group by product
            var groupedHints = {};
            $.each(hints, function (index, hint) {
                if (!groupedHints[hint.product_id]) {
                    groupedHints[hint.product_id] = {
                        product_name: hint.product_name,
                        hints: []
                    };
                }
                groupedHints[hint.product_id].hints.push(hint);
            });

            // Render grouped hints
            $.each(groupedHints, function (productId, data) {
                var $item = $('<li>').addClass('promo-hint-item').attr('data-product-id', productId);
                var $productInfo = $('<strong>').text(data.product_name + ':');
                $item.append($productInfo);

                var $hintList = $('<ul>').addClass('product-promo-list');
                $.each(data.hints, function (index, hint) {
                    var $hintItem = $('<li>').addClass('promo-hint-text').text(hint.display_text);
                    $hintList.append($hintItem);
                });

                $item.append($hintList);
                $list.append($item);
            });

            $container.append($list);
            $content.html($container).show();
        }
    });

    return $.mage.spalenza_promohint;
});
