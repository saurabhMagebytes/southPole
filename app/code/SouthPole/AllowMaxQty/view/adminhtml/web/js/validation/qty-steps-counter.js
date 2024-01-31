/*
 * Copyright © Aitoc. All rights reserved.
 */

define([], function () {
    return {
        ERROR_MESSAGE: 'Too many possible values for product quantities. Maximum is %1.<br/>Either increase values in field "Minimum Qty Allowed in Shopping Cart" or "Qty Increments". Alternatively, decrease value in field "Maximum Qty Allowed in Shopping Cart".',
        //should be synced with \Aitoc\ProductUnitsAndQuantities\Helper\Validator\ProductPuqConfig\StartEndIncQty\StepsCountValidator::ERROR_MESSAGE

        MAX_STEPS_COUNT: 10000000000,
        //should be synced with \Aitoc\ProductUnitsAndQuantities\Helper\Validator\ProductPuqConfig\StartEndIncQty\StepsCountValidator::MAX_STEPS_COUNT

        getStepsCount: function (minQty, maxQty, incQty) {
            return parseInt(1 + ((maxQty - minQty) / incQty));
        }
    };
});
