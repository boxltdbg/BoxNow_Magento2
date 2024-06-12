const config = {
    config:
        {
            mixins: {
                'Magento_Checkout/js/action/select-shipping-method': {
                    'Elegento_BoxNow/js/action/select-shipping-method-mixin': true
                },
                'Magento_Checkout/js/action/set-billing-address': {
                    'Elegento_BoxNow/js/action/set-billing-address-mixin': true
                },
                'Magento_Checkout/js/action/set-shipping-information': {
                    'Elegento_BoxNow/js/action/set-shipping-information-mixin': true
                },
                'Magento_Checkout/js/action/create-shipping-address': {
                    'Elegento_BoxNow/js/action/create-shipping-address-mixin': true
                },
                'Magento_Checkout/js/action/place-order': {
                    'Elegento_BoxNow/js/action/set-billing-address-mixin': true
                },
                'Magento_Checkout/js/action/create-billing-address': {
                    'Elegento_BoxNow/js/action/set-billing-address-mixin': true
                },
                'Magento_Checkout/js/view/billing-address': {
                    'Elegento_BoxNow/js/view/billing-address-mixin': true
                },
                'Magento_Checkout/js/view/shipping-information/address-renderer/default': {
                    'Elegento_BoxNow/js/view/shipping-address-mixin': true
                },
                'Magento_Checkout/js/view/shipping-information':{
                    'Elegento_BoxNow/js/view/shipping-information-mixin': true
                },
                'Magento_Checkout/js/view/shipping':{
                    'Elegento_BoxNow/js/view/shipping-mixin': true
                }

            }

        }
};
