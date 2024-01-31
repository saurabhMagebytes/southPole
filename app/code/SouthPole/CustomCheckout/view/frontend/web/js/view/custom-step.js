define([
    'ko',
    'uiComponent',
    'underscore',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/error-processor',
    'mage/url'
], function (ko, Component, _, stepNavigator, errorProcessor, url) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'SouthPole_CustomCheckout/custom-step'
        },
        isVisible: ko.observable(true),

        /**
         * @returns {*}
         */
        initialize: function () {
            this._super();
            stepNavigator.registerStep(
                'step_code',
                // step alias
                null,

                'Certificate',
                this.isVisible,
                _.bind(this.navigate, this),
                5
            );

            return this;
        },


        navigate: function () {
            this.isVisible(true);
        },

        /**
         * @returns void
         */
        // navigateToNextStep: function () {
        //     stepNavigator.next();
        // }

            navigateToNextStep: function () {
            var self = this;
            var form = jQuery('#certificateForm');

            if (form.validation() && form.validation('isValid')) {
                jQuery.ajax({
                    url: url.build('certificate/order/insertquotedata'),
                    data: form.serialize(),
                    type: 'post',
                    dataType: 'json',
                    beforeSend: function() {
                        jQuery('body').trigger('processStart');
                    },
                    success: function (response) {
                        if (response.success) {
                            stepNavigator.next();
                        } else {
                            // Handle errors
                            console.error(response.error);
                        }
                    },

                    error: function () {
                        console.error('An error occurred while submitting the form.');
                    }
                }).done(
                    function (response) {
                        const result = true;
                    }
                ).fail(
                    function (response) {
                        const result = false;
                        errorProcessor.process(response);
                    }
                ).always(
                    function() {
                        jQuery('body').trigger('processStop');
                    }
                );
            }
        }

    });
});