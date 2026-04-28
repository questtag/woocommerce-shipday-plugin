(function ($) {
    'use strict';

    var debounceTimer;
    var DEBOUNCE_MS = 600;

    // Standard billing/shipping text inputs.
    var ADDRESS_SELECTORS = [
        '#billing_address_1',  '#billing_address_2',
        '#billing_city',       '#billing_postcode',
        '#shipping_address_1', '#shipping_address_2',
        '#shipping_city',      '#shipping_postcode',
    ].join(', ');

    // Shipday-specific date/time fields — WooCommerce does not watch these.
    var DATETIME_SELECTORS = [
        '#shipday_delivery_date_datepicker',
        '[name="shipday_delivery_date_field"]',
        '#shipday_delivery_time_field',
        '[name="shipday_delivery_time_field"]',
    ].join(', ');

    function val(selector) {
        return $(selector).val() || '';
    }

    function buildDeliveryAddress() {
        var parts = [
            val('#shipping_address_1'),
            val('#shipping_address_2'),
            val('#shipping_city'),
            val('#shipping_state'),
            val('#shipping_postcode'),
            val('#shipping_country'),
        ].filter(Boolean);

        if (!parts.length) {
            parts = [
                val('#billing_address_1'),
                val('#billing_address_2'),
                val('#billing_city'),
                val('#billing_state'),
                val('#billing_postcode'),
                val('#billing_country'),
            ].filter(Boolean);
        }

        return parts.join(', ');
    }

    // Adds the WooCommerce-style loading class to the delivery fee row.
    function setFeeLoading(loading) {
        var $feeRows = $('.woocommerce-checkout-review-order-table tr.fee');
        if (loading) {
            $feeRows.addClass('shipday-calculating');
        } else {
            $feeRows.removeClass('shipday-calculating');
        }
    }

    // Sends the address/date/time to PHP for server-side logging and session update.
    // triggerUpdate: only true for Shipday date/time changes — WC does not watch those fields,
    // so we must manually fire update_checkout to refresh the fee display.
    function fetchDeliveryFee(triggerUpdate) {
        var deliveryAddress = buildDeliveryAddress();
        if (!deliveryAddress) {
            return;
        }

        var deliveryDate = val('#shipday_delivery_date_datepicker') || val('[name="shipday_delivery_date_field"]');
        var deliveryTime = val('#shipday_delivery_time_field')      || val('[name="shipday_delivery_time_field"]');

        $.ajax({
            url:    shipdayFeeData.ajaxUrl,
            method: 'POST',
            data: {
                action:          'shipday_get_delivery_fee',
                nonce:           shipdayFeeData.nonce,
                deliveryAddress: deliveryAddress,
                deliveryDate:    deliveryDate,
                deliveryTime:    deliveryTime,
            },
            success: function (response) {
                // For date/time field changes, WC does not auto-refresh — trigger it manually.
                if (response.success && triggerUpdate) {
                    $('body').trigger('update_checkout');
                }
            },
        });
    }

    // Called when a billing/shipping address text input changes.
    // WooCommerce watches .address-field changes and fires update_order_review automatically,
    // which calls our server-side resolve_fee(). We only need to log + update the session.
    function scheduleAddressFetch() {
        setFeeLoading(true);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchDeliveryFee(false); }, DEBOUNCE_MS);
    }

    // Called when a state or country SELECT changes (via WC's country_to_state_changed event
    // or a direct change on the select element).
    function onStateCountryChange() {
        setFeeLoading(true);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchDeliveryFee(false); }, DEBOUNCE_MS);
    }

    // Called when a Shipday date/time field changes.
    // Must trigger update_checkout because WC does not watch these fields.
    function scheduleDateTimeFetch() {
        setFeeLoading(true);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchDeliveryFee(true); }, DEBOUNCE_MS);
    }

    $(document).ready(function () {
        // Text input address fields — 'input' catches typing, 'change' catches paste/autocomplete.
        $(document).on('input change', ADDRESS_SELECTORS, scheduleAddressFetch);

        // State and country: WooCommerce uses selectWoo (select2) which fires 'change' on the
        // underlying <select>. Also listen to WC's own country_to_state_changed event which fires
        // after WC updates the state dropdown for a new country.
        $(document).on('change', '#billing_state, #shipping_state, #billing_country, #shipping_country', onStateCountryChange);
        $('body').on('country_to_state_changed', onStateCountryChange);

        // Shipday date/time fields.
        $(document).on('change', DATETIME_SELECTORS, scheduleDateTimeFetch);

        // WooCommerce fires 'updated_checkout' after it finishes re-rendering the order review.
        // Remove our loading class at that point — WC has already shown the updated values.
        $('body').on('updated_checkout', function () {
            setFeeLoading(false);
        });

        // Fire immediately if address is already populated on page load (returning customer).
        if (buildDeliveryAddress()) {
            fetchDeliveryFee(false);
        }
    });

})(jQuery);
