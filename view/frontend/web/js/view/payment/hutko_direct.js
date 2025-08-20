define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'hutko_direct',
                component: 'Hutko_Hutko/js/view/payment/method-renderer/hutko_direct'
            }
        );

        return Component.extend({});
    }
);
