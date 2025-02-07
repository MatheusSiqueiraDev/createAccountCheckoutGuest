/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Customer/js/model/customer',
    'Magento_Ui/js/lib/core/events',
    'mage/validation'
], function($, customer, events) {
    'use strict';

    return function(Component) {
        return Component.extend({
            defaults: {
                links: {
                    isPasswordVisible: 'checkout.steps.shipping-step.shippingAddress.customer-email:isPasswordVisible',
                    passwordValue: ''
                }
            },
            customerFormSelector: 'form[data-role=customer-creat-account]',

            /**
             * @return {Boolean}
             */
            validateShippingInformation() {
                if(!this._super()) {
                    return false
                }
                
                if (!customer.isLoggedIn() && !this.isPasswordVisible) {
                    if(!($(this.customerFormSelector).validation() && 
                        $(`${this.customerFormSelector} input[name=password]`).valid() &&
                        $(`${this.customerFormSelector} input[name=password_confirmation]`).valid() &&
                        $(`${this.customerFormSelector} input[name=dob]`).valid())) {
                        
                        $(this.customerFormSelector).validate().focusInvalid();

                        return false;
                    }
                }

                return true;
            },

            /**
             * @return {void}
             */
            setShippingInformation() {
                this._super();

                if(this.validateShippingInformation() && !customer.isLoggedIn() && !this.isPasswordVisible) {
                    events.trigger('validateShippingInformation');
                }
            }
        });
    }
});
