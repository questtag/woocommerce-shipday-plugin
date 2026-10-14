<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included admin partial uses file-scoped template variables.
$datetime_enabled     = get_option('shipday_enable_datetime_plugin', "no") === "yes";
$order_type_enabled   = get_option('shipday_enable_delivery_option', "no") === "yes";
$datetime_heading_label = get_option('shipday_delivery_pickup_label', "Delivery/Pickup info");
$time_format          = get_option( 'shipday_time_format', '12-hour' );
$order_prep_time      = get_option( 'shipday_order_prep_time', 30 );
$today                = wp_date( 'Y-m-d' );
$off_days             = get_option( 'shipday_off_days', array() );
$off_days             = is_array( $off_days ) ? array_values( array_filter( $off_days, static function ( $date ) use ( $today ) {
    return is_string( $date ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) && $date >= $today;
} ) ) : array();
?>
<div class="sd-panel-header">
  <div class="sd-panel-title-wrap">
    <div class="sd-panel-title">General Settings</div>
    <div class="sd-field-description">
      Enable or disable delivery/pickup date & time at checkout.
    </div>
  </div>
  <div data-save="general" data-state="Disabled" class="sd-save-button sd-save-button--disabled">
    <span>Save changes</span>
  </div>
</div>


<div class="sd-panel-body">
  <p class="shipday-general-notice">
    <span class="dashicons dashicons-yes"></span>
      <?php esc_html_e( ' Settings Changed Successfully', 'shipday-for-woocommerce' ); ?>
  </p>
  <form action="" method="post" id="shipday-general-settings-form">
      <?php wp_nonce_field('shipday_nonce'); ?>
    <div class="shipday-toggle-group">

      <div class="shipday-divider"></div>

      <!-- Enable datetime plugin -->
      <div class="shipday-toggle-row">
        <label class="shipday-switch">
          <input
              type="checkbox"
              id="shipday_enable_datetime_plugin"
              name="shipday_enable_datetime_plugin"
              class="shipday-switch__input"
              <?php checked( $datetime_enabled ); ?>
          />
          <span class="shipday-switch__track">
            <span class="shipday-switch__thumb"></span>
          </span>
        </label>

        <div class="shipday-toggle-row__text">
          <div class="shipday-toggle-row__title">
            Enable datetime
          </div>
          <div class="shipday-toggle-row__description">
            Disabling this will hide it on the checkout page.
          </div>
        </div>
      </div>

      <div class="shipday-divider"></div>

      <fieldset class="sd-fieldset datetime-dependent">
      <!-- Enable order type -->
      <div class="shipday-toggle-row">
        <label class="shipday-switch">
          <input
              type="checkbox"
              id="shipday_enable_delivery_option"
              name="shipday_enable_delivery_option"
              class="shipday-switch__input"
              <?php checked( $order_type_enabled ); ?>
          />
          <span class="shipday-switch__track">
            <span class="shipday-switch__thumb"></span>
          </span>
        </label>

        <div class="shipday-toggle-row__text">
          <div class="shipday-toggle-row__title">
            Enable order type (Delivery / Pickup)
            <span class="shipday-tooltip" tabindex="0">
              <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
              <span class="shipday-tooltip__text">
               Enable this if you offer both delivery and pickup and want customers to choose their order type at checkout.
              </span>
            </span>
          </div>
          <div class="shipday-toggle-row__description">
            Enable your customers to choose order type at checkout
          </div>

        </div>
      </div>
      </fieldset>

      <div class="shipday-divider"></div>

    </div>

    <fieldset class="sd-fieldset datetime-dependent">
    <div class="sd-field shipday-general-setting">
      <div class="rest-api-label-wrapper">
        <div class="rest-api-label">Date-time section heading</div>
        <span class="shipday-tooltip" tabindex="0">
            <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
            <span class="shipday-tooltip__text">
              Set the heading text shown above the date and time selector at checkout.
            </span>
        </span>
      </div>

      <div class="sd-input-wrapper shipday-general-setting__control">
        <input type="text" placeholder="" class="sd-text-input" name="shipday_delivery_pickup_label"
               value="<?php echo esc_attr( $datetime_heading_label ); ?>"
        />
      </div>
    </div>
    </fieldset>

    <fieldset class="sd-fieldset datetime-dependent">
      <div class="sd-field shipday-general-setting">
        <div class="rest-api-label-wrapper">
          <label class="rest-api-label" for="shipday-off-day-picker"><?php esc_html_e( 'Off days', 'shipday-for-woocommerce' ); ?></label>
          <span class="shipday-tooltip" tabindex="0">
            <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
            <span class="shipday-tooltip__text"><?php esc_html_e( 'Customers cannot choose these dates for delivery or pickup.', 'shipday-for-woocommerce' ); ?></span>
          </span>
        </div>
        <p class="shipday-off-days__hint"><?php esc_html_e( "Customer can't order in these days", 'shipday-for-woocommerce' ); ?></p>
        <div class="shipday-off-days" data-today="<?php echo esc_attr( $today ); ?>" data-selected="<?php echo esc_attr( wp_json_encode( $off_days ) ); ?>">
          <div class="shipday-off-days__entry">
            <div class="shipday-off-days__range-field">
              <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
              <input type="text" id="shipday-off-day-picker" class="shipday-off-days__input" readonly placeholder="<?php esc_attr_e( 'Select date range', 'shipday-for-woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Choose off-day date range', 'shipday-for-woocommerce' ); ?>" />
            </div>
            <button type="button" class="shipday-off-days__add" disabled><?php esc_html_e( 'Add range', 'shipday-for-woocommerce' ); ?></button>
          </div>
          <div class="shipday-off-days__summary">
            <div class="shipday-off-days__summary-title"><?php esc_html_e( 'Off days selected', 'shipday-for-woocommerce' ); ?> <span class="shipday-off-days__count">0</span></div>
            <div class="shipday-off-days__chips" aria-live="polite"></div>
            <p class="shipday-off-days__empty"><?php esc_html_e( 'No off days selected', 'shipday-for-woocommerce' ); ?></p>
          </div>
          <div class="shipday-off-days__values">
            <input type="hidden" name="shipday_off_days_json" value="<?php echo esc_attr( wp_json_encode( $off_days ) ); ?>" />
          </div>
        </div>
      </div>
    </fieldset>

    <fieldset class="sd-fieldset datetime-dependent">
    <div class="sd-field shipday-general-setting">
      <div class="rest-api-label-wrapper">
        <div class="rest-api-label"><?php esc_html_e( 'Time slot format', 'shipday-for-woocommerce' ); ?></div>
      </div>

      <div class="sd-input-wrapper shipday-general-setting__control shipday-general-setting__control--select">
        <select
          class="shipday-slot-duration-field__select sd-text-input"
          name="shipday_time_format"
        >
          <option value="12-hour" <?php selected( $time_format, '12-hour' ); ?>>
            <?php esc_html_e( '12-hour', 'shipday-for-woocommerce' ); ?>
          </option>
          <option value="24-hour" <?php selected( $time_format, '24-hour' ); ?>>
            <?php esc_html_e( '24-hour', 'shipday-for-woocommerce' ); ?>
          </option>
        </select>
      </div>
    </div>
    </fieldset>

    <fieldset class="sd-fieldset datetime-dependent">
    <div class="sd-field shipday-general-setting">
      <div class="rest-api-label-wrapper">
        <label class="rest-api-label" for="shipday_order_prep_time"><?php esc_html_e( 'Order prep time', 'shipday-for-woocommerce' ); ?></label>
        <span class="shipday-tooltip" tabindex="0">
          <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
          <span class="shipday-tooltip__text"><?php esc_html_e( 'Minutes needed to prepare an order. Customers can select a time slot only if it ends after the current store time plus this period.', 'shipday-for-woocommerce' ); ?></span>
        </span>
      </div>
      <div class="sd-input-wrapper shipday-general-setting__control shipday-general-setting__control--unit">
        <input type="number" min="0" step="1" id="shipday_order_prep_time" name="shipday_order_prep_time" class="sd-text-input" value="<?php echo esc_attr( $order_prep_time ); ?>" />
        <span class="shipday-general-setting__unit"><?php esc_html_e( 'Minute', 'shipday-for-woocommerce' ); ?></span>
      </div>
    </div>
    </fieldset>



  </form>

  <script type="text/javascript">
    (function($) {
      $(document).ready(function() {
        // Function to toggle fieldset disabled state based on checkbox
        function toggleDatetimeDependentFields() {
          var isChecked = $('#shipday_enable_datetime_plugin').is(':checked');
          $('.datetime-dependent').prop('disabled', !isChecked);
          $('.datetime-dependent').attr('aria-disabled', !isChecked);

          // If datetime plugin is unchecked, also uncheck delivery option
          if (!isChecked) {
            $('#shipday_enable_delivery_option').prop('checked', false);
          }
        }

        // Initial state
        toggleDatetimeDependentFields();

        // Listen for changes on the checkbox
        $('#shipday_enable_datetime_plugin').on('change', function() {
          toggleDatetimeDependentFields();
        });
      });
    })(jQuery);
  </script>
</div>
