(function($) {
    $(function() {
        // Function to initialize flatpickr for delivery date
        function initDeliveryDatePicker() {
            if ($('#shipday_delivery_date_datepicker').length) {
                // Get data attributes from date picker
                let weekStartsFrom = $('#shipday_delivery_date_datepicker').data('week_starts_from');
                let dateFormat = $('#shipday_delivery_date_datepicker').data('date_format');
                let todayDate = $('#shipday_delivery_date_datepicker').data('today_date');
                let selectable_days = $('#shipday_delivery_date_datepicker').data('selectable_days');
                let disable_week_days = $('#shipday_delivery_date_datepicker').data('disable_week_days');
                const enabledDates = shipdayEnableDates(
                  selectable_days,
                  todayDate,
                  disable_week_days,
                  []
                );

                $('#shipday_delivery_date_datepicker').flatpickr({
                    minDate: todayDate,
                    dateFormat: 'Y-m-d',
                    enable: enabledDates,
                    locale: {
                        firstDayOfWeek: weekStartsFrom
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        $("input:hidden[name='shipday_delivery_date_field']").val($('#shipday_delivery_date_datepicker').val());
                    }
                });
            }
        }

        function shipdayEnableDates(numberOfDays, startDate, disabledWeekDays, disabledDates){
            // Ensure numberOfDays is a number
            numberOfDays = parseInt(numberOfDays);

            let enabledDates = [],
              currentDate = new Date(startDate);

            // Loop until we have enough enabled dates
            while (enabledDates.length < numberOfDays) {
                let formattedCurrentDate = shipdayFormattedDate(currentDate);
                // Add date if it's not in disabled weekdays or disabled dates
                if (!disabledWeekDays.includes(currentDate.getDay().toString()) &&
                  !disabledDates.includes(formattedCurrentDate)) {
                    enabledDates.push(formattedCurrentDate);
                }
                // Move to next day
                currentDate.setDate(currentDate.getDate() + 1);
            }

            return enabledDates
        }

        function shipdayFormattedDate (date) {
            let year = date.getFullYear(),
              month = ("0" + (date.getMonth() + 1)).slice(-2),
              day = ("0" + date.getDate()).slice(-2);
            return `${year}-${month}-${day}`
        }

        // Function to initialize flatpickr for pickup date
        function initPickupDatePicker() {
            if ($('#shipday_pickup_date_datepicker').length) {
                // Get data attributes from date picker

                let weekStartsFrom = $('#shipday_pickup_date_datepicker').data('week_starts_from');
                let dateFormat = $('#shipday_pickup_date_datepicker').data('date_format');
                let todayDate = $('#shipday_pickup_date_datepicker').data('today_date');
                let selectable_days = $('#shipday_pickup_date_datepicker').data('selectable_days');
                let disable_week_days = $('#shipday_pickup_date_datepicker').data('disable_week_days');
                const enabledDates = shipdayEnableDates(
                  selectable_days,
                  todayDate,
                  disable_week_days,
                  []
                );

                $('#shipday_pickup_date_datepicker').flatpickr({
                    minDate: todayDate,
                    dateFormat: 'Y-m-d',
                    enable: enabledDates,
                    locale: {
                        firstDayOfWeek: weekStartsFrom
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        $("input:hidden[name='shipday_pickup_date_field']").val($('#shipday_pickup_date_datepicker').val());
                    }
                });

            }
        }

        function hideDelivery() {
            $('#shipday_delivery_date_div').hide();
            $('#shipday_delivery_time_div').hide();
        }

        function showDelivery() {
            $('#shipday_delivery_date_div').show();
            $('#shipday_delivery_time_div').show();
        }

        function hidePickup() {
            $('#shipday_pickup_date_div').hide();
            $('#shipday_pickup_time_div').hide();
        }

        function showPickup() {
            $('#shipday_pickup_date_div').show();
            $('#shipday_pickup_time_div').show();
        }

        // Reset form fields
        $('#shipday_order_type_field').wrap('<form autocomplete="off" class="shipday_woo_delivery_chrome_off_autocomplete"></form>');
        $('#shipday_order_type_field').val('');
        $('#shipday_delivery_datepicker').val('');

        // Initialize select2 for delivery selection box
        if (typeof $.fn.select2 !== 'undefined') {
            $('#shipday_order_type_field').select2({
                dropdownCssClass: 'shipday-delivery-selection-no-search'
            });
        }

        // Handle order type box change
        if ($('#shipday_order_type_field').length) {
            // $('#shipday_order_type_div').css('display', 'block');

            $(document).on('change', '#shipday_order_type_field', function(e) {
                e.preventDefault();
                if ($(this).parent().is('form')) {
                    $(this).unwrap();
                }
                let orderType = $(this).val();

                $("select[name='shipday_order_type_field']").val($('#shipday_order_type_field').val());
                $("select[name='shipday_order_type_field'] option[value='" + $('#shipday_order_type_field').val() + "']").attr('selected', 'selected');
                $("select[name='shipday_order_type_field'] option:not([value='" + $('#shipday_order_type_field').val() + "'])").removeAttr('selected');

                if (orderType === 'Delivery') {
                    hidePickup();
                    showDelivery();
                    initDeliveryDatePicker();
                } else if (orderType === 'Pickup') {
                    hideDelivery();
                    showPickup();
                    initPickupDatePicker();
                }
            });
        } else  if ($('#shipday_delivery_date_datepicker').length || $('#shipday_delivery_date_field').length) {
            showDelivery();
            initDeliveryDatePicker();
        }
        else  if ($('#shipday_pickup_date_datepicker').length || $('#shipday_pickup_date_field').length) {
            showPickup();
            initPickupDatePicker();
        }

        if($('#shipday_delivery_time_field').length) {
            $(document).on('change', '#shipday_delivery_time_field', function (e) {
                $("select[name='shipday_delivery_time_field']").val($('#shipday_delivery_time_field').val());
                $("select[name='shipday_delivery_time_field'] option[value='" + $('#shipday_delivery_time_field').val() + "']").attr('selected', 'selected');
            });
        }
        if($('#shipday_pickup_time_field').length) {
            $(document).on('change', '#shipday_pickup_time_field', function (e) {
                $("select[name='shipday_pickup_time_field']").val($('#shipday_pickup_time_field').val());
                $("select[name='shipday_pickup_time_field'] option[value='" + $('#shipday_pickup_time_field').val() + "']").attr('selected', 'selected');
            });
        }
    });
})(jQuery);
