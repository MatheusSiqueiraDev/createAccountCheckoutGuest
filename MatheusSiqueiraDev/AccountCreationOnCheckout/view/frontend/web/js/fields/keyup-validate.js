/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "jquery/ui",
    'mage/validation'
], function($) {
    "use strict";
    
    $.widget('mage.keyupValidate', {
        _create: function() {
            this._bind();
        },

        /**
         * Event binding, will monitor change, keyup and paste events.
         * @private
         */
        _bind: function () {
            this._on(this.element, {
                'change': this.validateField,
                'keyup': this.validateField  
            });
        },

        validateField: function () {
            $.validator.validateSingleElement(this.element);
        },

    });

    return $.mage.keyupValidate;
});