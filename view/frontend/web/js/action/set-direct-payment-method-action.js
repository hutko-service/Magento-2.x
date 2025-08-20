define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'mage/url',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
            $.ajax({
                url: "/hutko/action/getembeddeddata",
                type: "POST",
                dataType: "json",
                cache: false,
                success: function (response) {
                    setTimeout(function() {
                        if(response.token) {
                            $('.checkout-container').hide();
                            $("<div id=\"checkout-container\"></div>").insertBefore('.checkout-container');
                            $.getScript("https://pay.hutko.eu/latest/checkout-vue/checkout.js")
                                .done(function() {
                                    var Options = {
                                        options: {
                                            methods: ['card', 'banklinks_eu', 'wallets', 'local_methods'],
                                            methods_disabled: [],
                                            card_icons: ['mastercard', 'visa', 'maestro'],
                                            active_tab: 'card',
                                            fields: false,
                                            title: $('title').text(),
                                            link: urlBuilder.build(''),
                                            full_screen: true,
                                            button: true,
                                            email: true
                                        },
                                        params: {
                                            token: response.token
                                        }
                                    }
                                    hutko('#checkout-container', Options).$on('success', function(model) {
                                        var order_status = model.attr("order.order_data.order_status");
                                        if (order_status === "approved") {
                                            $.mage.redirect(urlBuilder.build('checkout/onepage/success'));
                                        }
                                    }).$on('error', function(model) {
                                            var response_code = model.attr('error.code');
                                            var response_description = model.attr('error.message');
                                            console.log(
                                                "Order is declined: " +
                                                response_code +
                                                ", description: " +
                                                response_description
                                            );
                                            $.mage.redirect('cart');
                                        });
                                })
                                .fail(function() {
                                    console.error("Failed to load Hutko Checkout script.");
                                });
                        } else {
                            $.mage.redirect(urlBuilder.build('cart'));
                        }
                    }, 100);
                }
            });
        };
    }
);
