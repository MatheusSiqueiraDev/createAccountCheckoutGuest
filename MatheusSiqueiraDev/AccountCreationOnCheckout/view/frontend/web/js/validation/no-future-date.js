/**
 * Copyright © MatheusSiqueiraDev, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
], function ($) {
    'use strict';

    const isValidDate = (value) => {
        if (!value) return true; 

        const parts = value.split('/');
        if (parts.length !== 3) return false; 

        const day = parseInt(parts[0], 10);
        const month = parseInt(parts[1], 10) - 1; 
        const year = parseInt(parts[2], 10);

        const inputDate = new Date(year, month, day);
        const today = new Date();
        today.setHours(0, 0, 0, 0); 

        return inputDate <= today;
    };

    return (targetWidget) => {
        $.validator.addMethod(
            'validate-no-future-date',
            (value) => isValidDate(value),
            $.mage.__("The date cannot be greater than today's date.")
        );

        return targetWidget;
    };
});
