define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote'
], function ($, wrapper, quote) {
    'use strict';

    return function (setShippingInformationAction) {
        return wrapper.wrap(setShippingInformationAction, function (originalAction, messageContainer) {

            const shippingAddress = quote.shippingAddress();
            const shippingMethod = quote.shippingMethod();

            // Obtain boxnow data from localStorage
            const boxNowDetails = window.localStorage.getItem('boxnow');

            // Check if the shipping method is 'boxnow'
            if (shippingMethod.method_code === 'boxnow') {

                // If BoxNow details are missing, prevent checkout and show a popup
                if (!boxNowDetails) {
                    alert('Моля изберете BOX NOW автомат, за да продължите!');
                    return false; // Stop further processing
                }

                // Proceed if BoxNow details exist
                let boxNowFound = false;
                if (shippingAddress['customAttributes'].length > 0) {
                    shippingAddress['customAttributes'].forEach((obj) => {
                        if (obj['attribute_code'] === 'boxnow_id') {
                            boxNowFound = true;
                            obj['value'] = boxNowDetails;
                        }
                    });
                }
                if (!boxNowFound) {
                    shippingAddress['customAttributes'].push({'attribute_code': 'boxnow_id', 'value': boxNowDetails});
                }
            }

            // Ensure extension_attributes are populated
            if (shippingAddress['extension_attributes'] === undefined) {
                shippingAddress['extension_attributes'] = {};
            }

            if (shippingAddress.customAttributes !== undefined) {
                $.each(shippingAddress.customAttributes, function (key, value) {
                    if ($.isPlainObject(value)) {
                        key = value['attribute_code'];
                    }
                    if ($.isPlainObject(value)) {
                        value = value['value'];
                    }
                    shippingAddress['customAttributes'][key] = value;
                    shippingAddress['extension_attributes'][key] = value;
                });
            }

            // Call the original action if validation passed
            return originalAction(messageContainer);
        });
    };
});
