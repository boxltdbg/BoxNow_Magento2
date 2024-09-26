define([
    'jquery',
    'underscore',

], function (
    $,
    _,
)  {
        'use strict';
        return function (Component) {
            return Component.extend({
                initialize: function () {
                    this._super();
                    if (  $('input[value="boxnow_boxnow"]') &&
                        $('input[checked="boxnow_boxnow"]'))  {
                        setTimeout(() => {
                            $('input[checked="boxnow_boxnow"]').click();
                        }, 3000);
                    }
                }
            });
        };
    });
