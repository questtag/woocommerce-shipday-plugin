(function ($) {
    'use strict';

    var DEBOUNCE_MS = 600;
    var FALLBACK_TARGETS = [
        '.wc-block-components-address-form',
        '.wc-block-checkout__shipping-fields',
        '[data-block-name="woocommerce/checkout-shipping-address-block"]',
        '.wc-block-components-checkout-step--shipping-address',
        '.wc-block-components-checkout-step__content',
        '.woocommerce-shipping-fields',
        '.shipping_address',
        '#ship-to-different-address',
        '.woocommerce-billing-fields',
        '.col2-set .col-2',
        '.col2-set .col-1'
    ];

    var ADDRESS_SELECTORS = [
        '#shipping_address_1', '#shipping_address_2',
        '#shipping_city',      '#shipping_state',
        '#shipping_postcode',  '#shipping_country',
        '#billing_address_1',  '#billing_address_2',
        '#billing_city',       '#billing_state',
        '#billing_postcode',   '#billing_country',
        '[autocomplete="shipping address-line1"]',
        '[autocomplete="shipping address-line2"]',
        '[autocomplete="shipping address-level2"]',
        '[autocomplete="shipping address-level1"]',
        '[autocomplete="shipping postal-code"]',
        '[autocomplete="shipping country"]',
        '[autocomplete="billing address-line1"]',
        '[autocomplete="billing address-line2"]',
        '[autocomplete="billing address-level2"]',
        '[autocomplete="billing address-level1"]',
        '[autocomplete="billing postal-code"]',
        '[autocomplete="billing country"]'
    ].join(', ');

    var DATETIME_SELECTORS = [
        '#shipday_delivery_date_datepicker',
        '[name="shipday_delivery_date_field"]',
        '#shipday_delivery_time_field',
        '[name="shipday_delivery_time_field"]'
    ].join(', ');
    var SHIPPING_UI_SELECTORS = [
        '.woocommerce-checkout-review-order-table tr.shipping',
        '.woocommerce-checkout-review-order-table tr.woocommerce-shipping-totals',
        '.woocommerce-shipping-methods',
        '#shipping_method',
        'fieldset#shipping-option',
        '.wc-block-checkout__shipping-option',
        '.wp-block-woocommerce-checkout-shipping-methods-block',
        '.wp-block-woocommerce-checkout-order-summary-shipping-block',
        '.wc-block-components-totals-shipping',
        '.wc-block-components-shipping-rates-control'
    ].join(', ');

    var debounceTimer;
    var loggedEvents = {};
    var lastObservedDeliveryAddress = '';
    var lastDrawnAddress = '';
    var pendingAddress = '';

    // Deferred that resolves when window.shipdayInitMap fires.
    var googleReady = (function () {
        var resolve;
        var promise = new Promise(function (r) { resolve = r; });
        return { promise: promise, resolve: function () { resolve(); } };
    })();

    var mapReadyPromise = null;       // { map, Route } once initialized
    var routePolylines = [];
    var routeMarkers = [];

    // ── Logging ────────────────────────────────────────────────────────────────

    function logFrontend(level, message, context) {
        $.ajax({
            url: shipdayFeeData.ajaxUrl,
            method: 'POST',
            data: {
                action: 'shipday_log_checkout_map',
                nonce: shipdayFeeData.nonce,
                level: level,
                message: message,
                context: context ? JSON.stringify(context) : ''
            }
        });
    }

    function logOnce(key, level, message, context) {
        if (loggedEvents[key]) {
            return;
        }
        loggedEvents[key] = true;
        logFrontend(level, message, context);
    }

    // ── Address resolution ─────────────────────────────────────────────────────

    function val(selector) {
        return $(selector).val() || '';
    }

    function firstValue(selectors) {
        for (var i = 0; i < selectors.length; i += 1) {
            var current = val(selectors[i]);
            if (current) {
                return current;
            }
        }
        return '';
    }

    function getAddressParts(type) {
        return [
            firstValue(['#' + type + '_address_1', '[autocomplete="' + type + ' address-line1"]']),
            firstValue(['#' + type + '_address_2', '[autocomplete="' + type + ' address-line2"]']),
            firstValue(['#' + type + '_city',      '[autocomplete="' + type + ' address-level2"]']),
            firstValue(['#' + type + '_state',     '[autocomplete="' + type + ' address-level1"]']),
            firstValue(['#' + type + '_postcode',  '[autocomplete="' + type + ' postal-code"]']),
            firstValue(['#' + type + '_country',   '[autocomplete="' + type + ' country"]'])
        ].filter(Boolean);
    }

    function extractAddressPartsFromObject(address) {
        if (!address || typeof address !== 'object') {
            return [];
        }
        return [
            address.address_1 || address.address1 || '',
            address.address_2 || address.address2 || '',
            address.city      || '',
            address.state     || '',
            address.postcode  || address.postalCode || '',
            address.country   || ''
        ].filter(Boolean);
    }

    function getBlockAddressParts(type) {
        if (!window.wp || !wp.data || typeof wp.data.select !== 'function') {
            return [];
        }
        if (!window.wc || !wc.wcBlocksData || !wc.wcBlocksData.CART_STORE_KEY) {
            return [];
        }

        var store;
        try {
            store = wp.data.select(wc.wcBlocksData.CART_STORE_KEY);
        } catch (e) {
            return [];
        }

        if (!store || typeof store.getCustomerData !== 'function') {
            return [];
        }

        var data = store.getCustomerData();
        return extractAddressPartsFromObject(data && data[type + 'Address']);
    }

    function buildDeliveryAddress() {
        // WC uses billing as the delivery address when "Ship to different
        // address" is unchecked, so fall through to billing if shipping is empty.
        var parts = getBlockAddressParts('shipping');
        if (!parts.length) parts = getAddressParts('shipping');
        if (!parts.length) parts = getBlockAddressParts('billing');
        if (!parts.length) parts = getAddressParts('billing');
        return parts.join(', ');
    }

    // ── Map container ──────────────────────────────────────────────────────────

    function getMapWrapper() {
        return document.getElementById('shipday-delivery-map-wrapper');
    }

    function getMapContainer() {
        return document.getElementById('shipday-delivery-map');
    }

    function setMapVisible(visible) {
        var wrapper = getMapWrapper();
        if (!wrapper) return;
        wrapper.style.display = visible ? 'block' : 'none';
        wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    // Server-side renders the wrapper for classic checkout. For block checkout
    // the hook does not fire, so inject it once on document ready.
    function ensureMapContainer() {
        if (getMapContainer()) {
            return;
        }

        var target = null;
        for (var i = 0; i < FALLBACK_TARGETS.length; i += 1) {
            target = document.querySelector(FALLBACK_TARGETS[i]);
            if (target) break;
        }

        if (!target) {
            logOnce('missing-map-target', 'warning', 'Unable to find a checkout DOM target for the delivery map container.', {
                isBlockCheckout: !!document.querySelector('.wc-block-checkout')
            });
            return;
        }

        $(target).after(
            '<div id="shipday-delivery-map-wrapper" aria-live="polite" aria-hidden="true" style="display:none;">' +
                '<div class="shipday-delivery-map__title">Delivery Route</div>' +
                '<div id="shipday-delivery-map"></div>' +
            '</div>'
        );

        logOnce('fallback-map-container', 'info', 'Inserted delivery map container from checkout JavaScript.', {
            targetSelector: target.className || target.id || target.tagName
        });
    }

    // ── Map init / draw ────────────────────────────────────────────────────────

    function clearRenderedRoute() {
        routePolylines.forEach(function (p) { if (p && p.setMap) p.setMap(null); });
        routeMarkers.forEach(function (m) { if (m && m.setMap) m.setMap(null); });
        routePolylines = [];
        routeMarkers = [];
    }

    function fitMapToPath(map, path) {
        if (!map || !path || !path.length) return;
        var bounds = new google.maps.LatLngBounds();
        path.forEach(function (point) { bounds.extend(point); });
        map.fitBounds(bounds);
    }

    // Lazy: only constructs the Map once Google has loaded AND we have a
    // visible container. Resolves with { map, Route }.
    function ensureMapReady() {
        if (mapReadyPromise) {
            return mapReadyPromise;
        }

        mapReadyPromise = googleReady.promise.then(function () {
            var container = getMapContainer();
            if (!container) {
                throw new Error('Map container missing at init time.');
            }

            if (typeof google === 'undefined' || !google.maps || typeof google.maps.importLibrary !== 'function') {
                throw new Error('google.maps.importLibrary is unavailable.');
            }

            return Promise.all([
                google.maps.importLibrary('maps'),
                google.maps.importLibrary('routes')
            ]).then(function (libs) {
                var mapsLib   = libs[0];
                var routesLib = libs[1];
                if (!mapsLib || !mapsLib.Map || !routesLib || !routesLib.Route) {
                    throw new Error('Google Maps libraries did not expose expected constructors.');
                }

                // Make wrapper visible BEFORE constructing the map so it has real
                // dimensions. Google's Map reads offsetWidth/Height on init.
                setMapVisible(true);

                var map = new mapsLib.Map(container, { mapTypeControl: false });
                logOnce('map-initialized', 'info', 'Google delivery map initialized successfully.', {
                    isBlockCheckout: !!document.querySelector('.wc-block-checkout')
                });

                return { map: map, Route: routesLib.Route };
            });
        }).catch(function (error) {
            logFrontend('error', 'Failed to initialize Google Maps for checkout.', {
                message: error && error.message ? error.message : String(error)
            });
            setMapVisible(false);
            mapReadyPromise = null; // allow a future retry
            throw error;
        });

        return mapReadyPromise;
    }

    function drawRoute(ctx, deliveryAddress) {
        if (deliveryAddress === lastDrawnAddress) {
            return Promise.resolve();
        }

        return ctx.Route.computeRoutes({
            origin:      shipdayFeeData.pickupAddress,
            destination: deliveryAddress,
            travelMode:  'DRIVING',
            fields:      ['path']
        }).then(function (result) {
            if (!result || !result.routes || !result.routes.length) {
                logFrontend('warning', 'Route.computeRoutes returned no routes.', {
                    hasPickupAddress: !!shipdayFeeData.pickupAddress,
                    deliveryAddressLength: deliveryAddress.length
                });
                setMapVisible(false);
                return;
            }

            var route = result.routes[0];
            clearRenderedRoute();

            routePolylines = route.createPolylines() || [];
            routePolylines.forEach(function (polyline) { polyline.setMap(ctx.map); });

            fitMapToPath(ctx.map, route.path);

            if (typeof route.createWaypointAdvancedMarkers === 'function') {
                route.createWaypointAdvancedMarkers({ map: ctx.map }).then(function (markers) {
                    routeMarkers = markers || [];
                }).catch(function (markerError) {
                    logFrontend('warning', 'Route markers could not be created.', {
                        message: markerError && markerError.message ? markerError.message : String(markerError)
                    });
                });
            }

            setMapVisible(true);
            lastDrawnAddress = deliveryAddress;
            logOnce('route-rendered', 'info', 'Delivery route rendered on checkout map.', {
                deliveryAddressLength: deliveryAddress.length
            });
        }).catch(function (error) {
            if (window.console && console.error) {
                console.error('[Shipday] Route.computeRoutes failed:', error);
            }
            logFrontend('warning', 'Route.computeRoutes failed.', {
                message: error && error.message ? error.message : String(error),
                hasPickupAddress: !!shipdayFeeData.pickupAddress,
                deliveryAddressLength: deliveryAddress.length
            });
            setMapVisible(false);
        });
    }

    function updateMap(deliveryAddress) {
        if (!deliveryAddress || !shipdayFeeData.pickupAddress) {
            setMapVisible(false);
            return;
        }

        pendingAddress = deliveryAddress;

        ensureMapReady().then(function (ctx) {
            // pendingAddress may have moved on while we were waiting for init.
            drawRoute(ctx, pendingAddress);
        }).catch(function () {
            // ensureMapReady already logged + hid the map.
        });
    }

    // Must be global — Google calls it via &callback=shipdayInitMap.
    window.shipdayInitMap = function () {
        logOnce('google-callback', 'info', 'Google Maps callback fired on checkout.');
        googleReady.resolve();
    };

    // ── Fee ────────────────────────────────────────────────────────────────────

    function setFeeLoading(loading) {
        $('.woocommerce-checkout-review-order-table tr.fee')[loading ? 'addClass' : 'removeClass']('shipday-calculating');
    }

    function hideShippingOptions() {
        $(SHIPPING_UI_SELECTORS).each(function () {
            this.style.setProperty('display', 'none', 'important');
        });
    }

    function refreshOrderSummary() {
        $('body').trigger('update_checkout');

        if (!window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
            return;
        }
        if (!window.wc || !wc.wcBlocksData || !wc.wcBlocksData.CART_STORE_KEY) {
            return;
        }

        try {
            var cartDispatch = wp.data.dispatch(wc.wcBlocksData.CART_STORE_KEY);
            if (cartDispatch && typeof cartDispatch.invalidateResolutionForStore === 'function') {
                cartDispatch.invalidateResolutionForStore();
            }
        } catch (error) {
            logFrontend('warning', 'Unable to invalidate WooCommerce cart store after resolving delivery fee.', {
                message: error && error.message ? error.message : String(error)
            });
        }
    }

    function fetchDeliveryFee(triggerUpdate) {
        var deliveryAddress = buildDeliveryAddress();
        if (!deliveryAddress) {
            logOnce('empty-delivery-address', 'warning', 'Delivery address is empty, clearing the resolved delivery fee.', {
                isBlockCheckout: !!document.querySelector('.wc-block-checkout')
            });
            pendingAddress = '';
            lastDrawnAddress = '';
            clearRenderedRoute();
            setMapVisible(false);
        }

        if (shipdayFeeData.mapsEnabled && deliveryAddress) {
            updateMap(deliveryAddress);
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
                deliveryTime:    deliveryTime
            },
            success: function (response) {
                logOnce('delivery-fee-fetched', 'info', 'Delivery fee AJAX request completed.', {
                    hasResponse: !!response,
                    triggerUpdate: !!triggerUpdate
                });

                if (response.success && triggerUpdate) {
                    refreshOrderSummary();
                }
            }
        });
    }

    function scheduleFetch(triggerUpdate) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { fetchDeliveryFee(triggerUpdate); }, DEBOUNCE_MS);
    }

    function maybeHandleDeliveryAddressChange() {
        var deliveryAddress = buildDeliveryAddress();
        if (deliveryAddress === lastObservedDeliveryAddress) {
            return;
        }
        lastObservedDeliveryAddress = deliveryAddress;
        if (deliveryAddress) {
            logFrontend('info', 'Resolved checkout delivery address.', {
                isBlockCheckout: !!document.querySelector('.wc-block-checkout'),
                partCount: deliveryAddress.split(',').filter(Boolean).length
            });
        }
        scheduleFetch(true);
    }

    // ── Bootstrap ──────────────────────────────────────────────────────────────

    $(document).ready(function () {
        logOnce('checkout-map-ready', 'info', 'Checkout delivery map script started.', {
            mapsEnabled: !!shipdayFeeData.mapsEnabled,
            isBlockCheckout: !!document.querySelector('.wc-block-checkout'),
            hasClassicShippingFields: !!document.querySelector('.woocommerce-shipping-fields')
        });

        if (shipdayFeeData.mapsEnabled) {
            ensureMapContainer();
        }

        hideShippingOptions();
        $('body').on('update_checkout',  function () { setFeeLoading(true); });
        $('body').on('updated_checkout', function () {
            setFeeLoading(false);
            hideShippingOptions();
        });

        $(document).on('input change', ADDRESS_SELECTORS, function () { scheduleFetch(true); });
        $(document).on('change', '#shipping_state, #shipping_country', function () { scheduleFetch(true); });
        $('body').on('country_to_state_changed', function () { scheduleFetch(true); });
        $(document).on('change', DATETIME_SELECTORS, function () { scheduleFetch(true); });

        if (window.wp && wp.data && typeof wp.data.subscribe === 'function' && window.wc && wc.wcBlocksData) {
            wp.data.subscribe(maybeHandleDeliveryAddressChange);
        }

        if (window.MutationObserver) {
            (new MutationObserver(hideShippingOptions)).observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // Fire on page load if address is already populated (returning customer).
        maybeHandleDeliveryAddressChange();
    });

})(jQuery);
