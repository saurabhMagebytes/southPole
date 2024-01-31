define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/totals',
    'Magento_Catalog/js/price-utils',
    'StripeIntegration_Payments/js/view/checkout/trialing_subscriptions',
    'StripeIntegration_Payments/js/view/checkout/summary/prorations'
], function (
    quote,
    totals,
    priceUtils,
    trialingSubscriptions,
    prorations
) {
    'use strict';
    return function (grandTotal)
    {
        return grandTotal.extend(
        {
            totals: quote.getTotals(),
            getValue: function()
            {
                var price = 0;
                if (totals)
                {
                    price = parseFloat(totals.getSegment('grand_total').value);
                    price += trialingSubscriptions().getPureValue();
                    price += prorations().getPureValue();
                }
                return grandTotal().getFormattedPrice(price);
            },
            /**
             * @return {*|String}
             */
            getBaseValue: function () {
                var price = 0;
                if (this.totals()) {
                    price = this.totals()['base_grand_total'];
                }
                return priceUtils.formatPriceLocale(price, quote.getBasePriceFormat());
            },
            getGrandTotalExclTax: function()
            {
                var price = 0;
                if (totals.getSegment('grand_total') && totals.getSegment('tax_amount'))
                {
                    price = parseFloat(totals.getSegment('grand_total').value);
                    price -= parseFloat(totals.getSegment('tax_amount').value);
                    price += trialingSubscriptions().getTaxAmount();
                    price += trialingSubscriptions().getPureValue();
                    price += prorations().getPureValue();
                }
                return grandTotal().getFormattedPrice(price);
            }
        });
    };
});