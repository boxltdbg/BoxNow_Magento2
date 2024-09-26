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
                type: 'boxnow',
                component: 'Elegento_BoxNow/js/view/payment/method-renderer/boxnow-method'
            }
        );
        return Component.extend({});
    }
);
