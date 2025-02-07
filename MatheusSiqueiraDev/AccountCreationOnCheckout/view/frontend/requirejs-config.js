/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
var config = {
    map: {
        '*': {
            'keyup-validate': 'MatheusSiqueiraDev_AccountCreationOnCheckout/js/fields/keyup-validate',
            inputMask: 'MatheusSiqueiraDev_AccountCreationOnCheckout/js/lib/inputMask'
        }
    },
    config: {
        mixins: {
            'Magento_Checkout/js/view/shipping': {
                'MatheusSiqueiraDev_AccountCreationOnCheckout/js/shipping-mixin': true
            },
            'mage/validation': {
                'MatheusSiqueiraDev_AccountCreationOnCheckout/js/validation/no-future-date': true
            }
        }
    }
};