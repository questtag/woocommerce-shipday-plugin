(function(a) {
  a(function() {
    // Function to initialize delivery datepicker
    function u() {
      // Initialize flatpickr if date picker exists
      if (a('#shipday_delivery_datepicker').length) {
        // Get data attributes from date picker
        var y = a('#shipday_delivery_datepicker').data('date_format');
        var z = a('#shipday_delivery_datepicker').data('week_starts_from');

        a('#shipday_delivery_datepicker').flatpickr({
          minDate: today_date,
          dateFormat: y,
          locale: {
            firstDayOfWeek: z
          },
          onChange: function(selectedDates, dateStr, instance) {
            // Just update the hidden field with the selected date
            a("input:hidden[name='shipday_woo_delivery_date_field']").val(a('#shipday_delivery_datepicker').val());
          }
        });
      }
    }

    // Function to initialize pickup datepicker
    function v() {
      // Initialize flatpickr if pickup date picker exists
      if (a('#shipday_woo_delivery_pickup_date_datepicker').length) {
        // Get data attributes from pickup date picker
        var C = a('#shipday_woo_delivery_pickup_date_datepicker').data('pickup_date_format');
        var D = a('#shipday_woo_delivery_pickup_date_datepicker').data('pickup_week_starts_from');

        a('#shipday_woo_delivery_pickup_date_datepicker').flatpickr({
          minDate: today_date,
          dateFormat: C,
          locale: {
            firstDayOfWeek: D
          },
          onChange: function(selectedDates, dateStr, instance) {
            // Just update the hidden field with the selected date
            a("input:hidden[name='shipday_woo_delivery_pickup_date_field']").val(a('#shipday_woo_delivery_pickup_date_datepicker').val());
          }
        });
      }
    }

    // Reset form fields
    a('#shipday_woo_delivery_delivery_selection_box').wrap('<form autocomplete="off" class="shipday_woo_delivery_chrome_off_autocomplete"></form>');
    a('#shipday_woo_delivery_delivery_selection_box').val('');
    a('#shipday_delivery_datepicker').val('');
    a('#shipday_woo_delivery_time_field').val('');
    a('#shipday_woo_delivery_pickup_date_datepicker').val('');
    a('#shipday_woo_delivery_pickup_time_field').val('');

    // Add loading indicator
    var b = '';
    b += '<div class="shipday-woo-delivery-loading-image">';
    b += '<div class="shipday-woo-delivery-loading-gif">';
    b += '<img src="' + a('#shipday_woo_delivery_setting_wrapper').data('plugin-url') + 'public/images/loading.gif" alt="" />';
    b += '</div>';
    b += '</div>';
    a('#shipday_woo_delivery_setting_wrapper').append(b);

    // Get today's date
    today_date = a('#shipday_woo_delivery_setting_wrapper').data('today_date');

    // Initialize select2 for delivery selection box
    a('#shipday_woo_delivery_delivery_selection_box').select2({
      dropdownCssClass: 'shipday-delivery-selection-no-search'
    });

    // Initialize select2 for time fields
    a('#shipday_woo_delivery_time_field').select2({
      allowClear: !0
    });
    a('#shipday_woo_delivery_pickup_time_field').select2({
      allowClear: !0
    });

    // Handle delivery selection box change
    if (a('#shipday_woo_delivery_delivery_selection_box').length) {
      a('#shipday_woo_delivery_delivery_selection_field').css('display', 'block');

      a(document).on('change', '#shipday_woo_delivery_delivery_selection_box', function(b) {
        b.preventDefault();

        if (a(this).parent().is('form')) {
          a(this).unwrap();
        }

        deliveryOptionSelection = a(this).val();

        a("select[name='shipday_woo_delivery_delivery_selection_box']").val(a('#shipday_woo_delivery_delivery_selection_box').val());
        a("select[name='shipday_woo_delivery_delivery_selection_box'] option[value='" + a('#shipday_woo_delivery_delivery_selection_box').val() + "']").attr('selected', 'selected');
        a("select[name='shipday_woo_delivery_delivery_selection_box'] option:not([value='" + a('#shipday_woo_delivery_delivery_selection_box').val() + "'])").removeAttr('selected');

        if (deliveryOptionSelection === 'Delivery') {
          a('#shipday_woo_delivery_pickup_date_section').hide();
          a('#shipday_woo_delivery_pickup_time_section').hide();
          a('#shipday_woo_delivery_delivery_date_section').show();
          a('#shipday_woo_delivery_delivery_time_section').show();

          // Initialize delivery datepicker directly
          u();
        } else if (deliveryOptionSelection === 'Pickup') {
          a('#shipday_woo_delivery_delivery_date_section').hide();
          a('#shipday_woo_delivery_delivery_time_section').hide();
          a('#shipday_woo_delivery_pickup_date_section').show();
          a('#shipday_woo_delivery_pickup_time_section').show();

          // Initialize pickup datepicker directly
          v();
        }
      });
    } else {
      // Show all sections and initialize datepickers directly
      a('#shipday_woo_delivery_delivery_date_section').css('display', 'block');
      a('#shipday_woo_delivery_delivery_time_section').css('display', 'block');
      a('#shipday_woo_delivery_pickup_date_section').css('display', 'block');
      a('#shipday_woo_delivery_pickup_time_section').css('display', 'block');

      if (a('#shipday_delivery_datepicker').length) {
        u();
      }

      if (a('#shipday_woo_delivery_pickup_date_datepicker').length) {
        v();
      }
    }
  });
}(jQuery));
