<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included admin partial uses file-scoped template variables.
$datetime_enabled = get_option('shipday_enable_datetime_plugin', "no") === "yes";
$order_type_enabled = get_option('shipday_enable_delivery_option', "no") === "yes";
$datetime_heading_label = get_option('shipday_delivery_pickup_label', "Delivery/Pickup info");
$time_format = get_option( 'shipday_time_format', '12-hour' );
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
