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
                type: 'hutko',
                component: 'Hutko_Hutko/js/view/payment/method-renderer/hutko'
            }
        );
        return Component.extend({});
    }
);
