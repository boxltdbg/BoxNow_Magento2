define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper,quote) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction, messageContainer) {

            const shippingAddress = quote.shippingAddress();
            const shippingMethod = quote.shippingMethod();

            //obtain boxnow data from localstorage
            const boxNowDetails = window.localStorage.getItem('boxnow');

            if ( boxNowDetails && shippingMethod.method_code === 'boxnow') {
                let boxNowFound = false;
                if(shippingAddress['customAttributes'].length > 0) {
                    shippingAddress['customAttributes'].forEach( ( obj ) => {
                        if( obj['attribute_code'] === 'boxnow_id') {
                            boxNowFound = true;
                            obj['value'] = boxNowDetails
                        }
                    })
                }
                if(boxNowFound === false) {
                    shippingAddress['customAttributes'].push({'attribute_code':'boxnow_id','value': boxNowDetails})
                }
            }

            if (shippingAddress['extension_attributes'] === undefined) {
                shippingAddress['extension_attributes'] = {};
            }


            if (shippingAddress.customAttributes !== undefined) {
                $.each(shippingAddress.customAttributes , function( key, value ) {

                    if($.isPlainObject(value)) {
                        key = value['attribute_code'];
                    }
                    if($.isPlainObject(value)){
                        value = value['value'];
                    }

                    shippingAddress['customAttributes'][key] = value;
                    shippingAddress['extension_attributes'][key] = value;

                });
            }

            return originalAction(messageContainer);
        });
    };
});
