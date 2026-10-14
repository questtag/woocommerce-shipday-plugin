(function ($) {
  'use strict';

  function updateSlots() {
    var wrapper = document.getElementById('shipday_woo_delivery_setting_wrapper');
    if (!wrapper) return;

    var storeNow = Date.parse(wrapper.getAttribute('data-store-now') + 'Z');
    var serverTimestamp = Number(wrapper.getAttribute('data-server-timestamp'));
    var prepMinutes = Number(wrapper.getAttribute('data-prep-minutes'));
    var cutoff = new Date(storeNow + Date.now() - serverTimestamp + prepMinutes * 60000);
    var cutoffDate = cutoff.toISOString().slice(0, 10);
    var cutoffMinutes = cutoff.getUTCHours() * 60 + cutoff.getUTCMinutes() + cutoff.getUTCSeconds() / 60;

    [
      ['shipday_delivery_time_field', 'shipday_delivery_date_datepicker'],
      ['shipday_pickup_time_field', 'shipday_pickup_date_datepicker']
    ].forEach(function (fields) {
      var select = document.getElementById(fields[0]);
      if (!select) return;
      var dateInput = document.getElementById(fields[1]);
      var date = dateInput && dateInput.value ? dateInput.value : wrapper.getAttribute('data-today_date');

      Array.prototype.forEach.call(select.options, function (option) {
        if (!option.value) return;
        var match = option.value.match(/-\s*(\d{1,2}):(\d{2})(?:\s*(AM|PM))?\s*$/i);
        if (!match) return;
        var hour = Number(match[1]);
        if (match[3]) hour = hour % 12 + (match[3].toUpperCase() === 'PM' ? 12 : 0);
        var endMinutes = hour * 60 + Number(match[2]);
        option.disabled = date < cutoffDate || (date === cutoffDate && endMinutes <= cutoffMinutes);
      });

      if (select.selectedOptions.length && select.selectedOptions[0].disabled) {
        select.value = '';
        $(select).trigger('change');
      }
    });
  }

  $(document).on('change', '#shipday_delivery_date_datepicker, #shipday_pickup_date_datepicker', updateSlots);
  $(document.body).on('updated_checkout', updateSlots);
  $(updateSlots);
  window.setInterval(updateSlots, 30000);
})(jQuery);
