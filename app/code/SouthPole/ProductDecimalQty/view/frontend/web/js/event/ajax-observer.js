define([
    'jquery',
    'mage/url'
], function ($, urlBuilder) {
    'use strict';

    /**
     * @typedef {Object} ObservedEvent
     * @property {string} gaEvent - ga4 event name
     * @property {string} pathname - url path which should be observed
     * @property {'ajaxComplete'|'ajaxSend'} observerType - type of observer
     */

    const AJAX_COMPLETE_TYPE = 'ajaxComplete';
    const AJAX_SEND_TYPE = 'ajaxSend';

    $.widget('amGa4.ajaxObserver', {
        options: {
            eventDataEndpoint: 'amga4/event/track',
            observedEvents: [
                {
                    gaEvent: 'add_to_cart',
                    pathname: 'checkout/cart/add',
                    observerType: AJAX_COMPLETE_TYPE
                },
                {
                    gaEvent: 'remove_from_cart',
                    pathname: 'checkout/sidebar/removeItem',
                    observerType: AJAX_SEND_TYPE
                },
                {
                    gaEvent: 'add_payment_info',
                    pathname: '/set-payment-information',
                    observerType: AJAX_COMPLETE_TYPE
                },
                {
                    gaEvent: 'add_shipping_info',
                    pathname: '/shipping-information',
                    observerType: AJAX_COMPLETE_TYPE
                }
            ],
        },
        requestPromiseMap: new Map(),

        /**
         * @private
         * @return {void}
         */
        _create: function () {
            this.registerAjaxCompleteEvent();
            this.registerAjaxSendEvent();
        },

        /**
         * Events whose data can be tracked after an ajax request
         *
         * @return {void}
         */
        registerAjaxCompleteEvent: function () {
            $(document).on('ajaxComplete', (event, xhr, settings) => {
                try {
                    const observedEvent = this.getObservedEvent(settings, AJAX_COMPLETE_TYPE);
                    observedEvent && this.performAjax(settings, observedEvent);
                } catch (e) {
                    // do not affect ajax request processing
                    console.error(e);
                }
            });
        },

        /**
         * Events whose data can only be tracked up to an ajax request
         *
         * @return {void}
         */
        registerAjaxSendEvent: function () {
            $(document).on('ajaxSend', (event, xhr, settings) => {
                try {
                    const observedEvent = this.getObservedEvent(settings, AJAX_SEND_TYPE);
                    if (!observedEvent) {
                        return;
                    }

                    const requestHashKey = this.hashString(this.convertSettingsData(settings).toString());

                    // Store tracking data inside hashmap till ajax request completes
                    this.requestPromiseMap.set(requestHashKey, this.performAjax(settings, observedEvent));
                } catch (e) {
                    // do not affect ajax request processing
                    console.error(e);
                }
            });

            $(document).on('ajaxComplete', (event, xhr, settings) => {
                try {
                    const observedEvent = this.getObservedEvent(settings, AJAX_SEND_TYPE);
                    const requestHashKey = this.hashString(this.convertSettingsData(settings).toString());
                    if (!observedEvent || !this.requestPromiseMap.has(requestHashKey)) {
                        return;
                    }

                    // Push to dataLayer from hashmap
                    this.requestPromiseMap.get(requestHashKey).done((observedData) => {
                        observedData.forEach(data => this.pushToDataLayer(data));
                    }).always(() => {
                        this.requestPromiseMap.delete(requestHashKey);
                    });
                } catch (e) {
                    // do not affect ajax request processing
                    console.error(e);
                }
            });
        },

        /**
         * @param {Object} settings
         * @param {ObservedEvent} observedEvent
         * @return {jQuery}
         */
        performAjax: function (settings, observedEvent) {
            const dataToObserve = this.convertSettingsData(settings);
            const successCallback = observedEvent.observerType === AJAX_COMPLETE_TYPE
                ? (observedData) => { observedData.forEach(data => this.pushToDataLayer(data)); }
                : () => {};
            dataToObserve.append('event', observedEvent.gaEvent);

            return $.ajax({
                method: 'POST',
                url: urlBuilder.build(this.options.eventDataEndpoint),
                contentType: false,
                processData: false,
                global:false,
                async: observedEvent.observerType === AJAX_COMPLETE_TYPE,
                data: dataToObserve,
                success: successCallback,
            });
        },

        /**
         * @param data
         * @return {void}
         */
        pushToDataLayer: function (data) {
            window.dataLayer.push(data);
        },

        /**
         * @param {Object} settings
         * @param {'ajaxComplete'|'ajaxSend'} observerType
         * @return {ObservedEvent|undefined}
         */
        getObservedEvent: function (settings, observerType) {
            const url = new URL(settings.url);

            return this.options.observedEvents
                .find(observedEvent => {
                    return observedEvent.observerType === observerType && url.pathname.includes(observedEvent.pathname);
                });
        },

        /**
         * @param {Object} settings
         * @return {URLSearchParams}
         */
        convertSettingsData: function (settings) {
            let settingsData = new URLSearchParams();

            switch (settings.contentType) {
                case 'application/json':
                    try {
                        const parsedData = JSON.parse(settings.data);
                        settingsData = Object.keys(parsedData).reduce((output, key) => {
                            const value = parsedData[key];
                            const valueString = JSON.stringify(value);
                            output.append(key, valueString);
                            return output;
                        }, new URLSearchParams());
                    } catch (e) {
                        settingsData = ''
                    }
                    break;
                default:
                    settingsData = new URLSearchParams(settings.data);
                    break;
            }

            return settingsData;
        },

        /**
         * Simple hashing: sums the ASCII code of the characters in the string using the charCodeAt() method
         *
         * @param {string} string
         * @return {number}
         */
        hashString: function (string) {
            return Array.from(string)
                .reduce((accumulator, currentValue) => accumulator + currentValue.charCodeAt(0), 0);
        }
    });

    return $.amGa4.ajaxObserver;
});
