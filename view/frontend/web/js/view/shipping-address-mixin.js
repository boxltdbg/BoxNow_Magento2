define([],
    function (
    ) {
        'use strict';
        return function (Component) {
            return Component.extend({

                /**
                 * Get customer attribute label
                 *
                 * @param {*} attribute
                 * @returns {*}
                 */
                getCustomAttributeLabel: function (attribute) {
                    if(attribute['attribute_code'] === 'boxnow_id'){
                        return;
                    }

                    let label;

                    if (typeof attribute === 'string') {
                        return attribute;
                    }

                    if (attribute.label) {
                        return attribute.label;
                    }

                    if (_.isArray(attribute.value)) {
                        label = _.map(attribute.value, function (value) {
                            return this.getCustomAttributeOptionLabel(attribute['attribute_code'], value) || value;
                        }, this).join(', ');
                    } else {
                        label = this.getCustomAttributeOptionLabel(attribute['attribute_code'], attribute.value);
                    }

                    return label || attribute.value;
                }
            });
        };
    });
