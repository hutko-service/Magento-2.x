define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Hutko_Hutko/js/action/set-payment-method-action'
        ],
    function (ko, $, Component, setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Hutko_Hutko/payment/hutko'
            },

            isEnabled: function() {
                return window.checkoutConfig.hutko.is_active;
            },

            getDescription: function(){
                return window.checkoutConfig.hutko.description;
            },

            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            }
        });
    }
);
