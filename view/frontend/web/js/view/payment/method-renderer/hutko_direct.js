define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'Hutko_Hutko/js/action/set-direct-payment-method-action',
        'Magento_Checkout/js/action/place-order'
    ],
    function (Component, $, setPaymentMethodAction, placeOrderAction) {
        'use strict';

        return Component.extend({
            defaults: {
                code: 'hutko_direct',
                template: 'Hutko_Hutko/payment/hutko_direct'
            },

            getCode: function() {
                return this.code;
            },

            isActive: function() {
                return this.getCode() === this.isChecked()
            },

            getDescription: function() {
                return window.checkoutConfig.hutko_direct.description;
            },

            afterPlaceOrder: function () {
                return false;
            },

            placeOrder: function () {
                var self = this;
                placeOrderAction(this.getData(), this.messageContainer).done(function (response) {
                    setPaymentMethodAction(this.messageContainer);
                });
            }
        });
    }
);
