define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/sidebar'
], function ($, Component, quote, stepNavigator, sidebarModel) {
        'use strict';
        return function (Component) {
            return Component.extend({
                getShippingMethodTitle: function () {
                    let shippingMethod = quote.shippingMethod(),
                        shippingMethodTitle = '';

                    if (!shippingMethod) {
                        return '';
                    }

                    shippingMethodTitle = shippingMethod['carrier_title'];

                    if (typeof shippingMethod['method_title'] !== 'undefined') {
                        shippingMethodTitle += ' - ' + shippingMethod['method_title'];
                    }

                    if(shippingMethod['carrier_code'] === 'boxnow'){
                        let boxnow = JSON.parse(window.localStorage.getItem('boxnow'))
                        if(boxnow){
                            let boxnowAddress = 'BoxNow: ' + boxnow.boxnowLockerAddressLine1 + ' ' + 'TK: ' + boxnow.boxnowLockerPostalCode
                            if( $("span [id='#addressVia']").length > 0 ) {
                                $('#addressVia').remove();
                            }
                            $('.ship-via .shipping-information-content').append(`<span id="addressVia">`+ boxnowAddress +  `</span>`)
                        }
                    }
                },
            });
        };
    });
