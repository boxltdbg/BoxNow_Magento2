define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper,quote) {
    'use strict';

    return function (setBillingAddressAction) {
        return wrapper.wrap(setBillingAddressAction, function (originalAction, messageContainer) {

            let billingAddress = quote.billingAddress();
            const shippingMethod = quote.shippingMethod();

            if(billingAddress != undefined) {

                const boxNowDetails = window.localStorage.getItem('boxnow');

                if ( boxNowDetails && shippingMethod.method_code === 'boxnow') {
                    let boxNowFound = false;
                    if(billingAddress['customAttributes']?.length > 0) {
                        billingAddress['customAttributes'].forEach( ( obj ) => {
                            if( obj['attribute_code'] === 'boxnow_id') {
                                boxNowFound = true;
                                obj['value'] = boxNowDetails
                            }
                        })
                    }
                    if(boxNowFound === false) {
                        if(billingAddress['customAttributes'] === undefined){
                            billingAddress['customAttributes'] = [];
                        }
                        billingAddress['customAttributes']?.push({'attribute_code':'boxnow_id','value': boxNowDetails})
                    }
                }

                if (billingAddress['extension_attributes'] === undefined) {
                    billingAddress['extension_attributes'] = {};
                }

                if (billingAddress.customAttributes != undefined) {
                    $.each(billingAddress.customAttributes, function (key, value) {

                        if ($.isPlainObject(value)) {
                            key = value['attribute_code'];
                        }
                        if($.isPlainObject(value)){
                            value = value['value'];
                        }

                        billingAddress['extension_attributes'][key] = value;
                    });
                }

            }

            return originalAction(messageContainer);
        });
    };
});