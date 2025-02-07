/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor',
    'Magento_Ui/js/lib/core/events',
    'Magento_Checkout/js/model/url-builder',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'inputMask',
    'mage/calendar',
    'mage/validation'
], function (
    $,
    Component, 
    ko, 
    customer, 
    storage, 
    errorProcessor, 
    events, 
    urlBuilder, 
    stepNavigator, 
    quote,
    mask
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MatheusSiqueiraDev_AccountCreationOnCheckout/form/element/customer-information',
            links: {
                isPasswordVisible: 'checkout.steps.shipping-step.shippingAddress.customer-email:isPasswordVisible',
            }
        },
        isCustomerLoggedIn: customer.isLoggedIn,
        minimumPasswordLength: window.checkoutConfig.minimumPasswordLength || 0,
        requiredCharacterClassesNumber: window.checkoutConfig.requiredCharacterClassesNumber || 0,
        passwordValue: ko.observable(),
        calendarId: '',

        /**
         * @return {void}
        */
        initialize() {
            this._super();

            events.on('validateShippingInformation', () => {
                this.setCustomerInformationInQuote()
            });
        },

        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
        */
        initObservable() {
            this._super()
                .observe(['isPasswordVisible']);

            return this;
        },
        
        /**
         * @return {void}
         */
        setCustomerInformationInQuote() {
            storage.post(
                this.getUrlSaveCustomerInformationCheckoutApi(),
                JSON.stringify({
                    cartId: quote.getQuoteId(), 
                    password: this.passwordValue(),
                    dob: $(`#${this.calendarId}`).val()
                })
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                    stepNavigator.navigateTo('shipping');
                }
            );
        },

        /**
         * @return {string}
         */
        getUrlSaveCustomerInformationCheckoutApi() {
            const param = { cartId: quote.getQuoteId() };
            const url = '/guest-carts/:cartId/save-information-customer-checkout';

            return urlBuilder.createUrl(url, param)
        },

        /**
         * @return {void}
         */
        initFieldCalendare(element) {
            this.calendarId = $(element).attr('id');
            
            $(element).calendar({
                showsTime: false,
                dateFormat: "dd/MM/Y",
                showButtonPanel: false,
                yearRange: "-120y:c+nn",
                maxDate: "0d",
                changeMonth: true,
                changeYear: true,
                showOn: "both",
                showButtonPanel: false,
                showWeek: false
            }).mask('00/00/0000');
        }
    });
});
