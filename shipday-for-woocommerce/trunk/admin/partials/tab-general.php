<?php
$datetime_enabled = get_option('shipday_enable_datetime_plugin', "no") === "yes";
$order_type_enabled = get_option('shipday_enable_delivery_option', "no") === "yes";
$datetime_heading_label = get_option('shipday_delivery_pickup_label', "Delivery/Pickup info");
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
      <?php _e(' Settings Changed Successfully', 'shipday-delivery'); ?>
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
              <?php echo ($datetime_enabled) ? "checked" : "" ?>
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
              <?php echo ($order_type_enabled) ? "checked" : "" ?>
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
    <div class="sd-field">
      <div class="rest-api-label-wrapper">
        <div class="rest-api-label">Date & time field heading</div>
        <span class="shipday-tooltip" tabindex="0">
            <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
            <span class="shipday-tooltip__text">
              Set the heading text shown above the date and time selector at checkout.
            </span>
        </span>
      </div>

      <div class="sd-input-wrapper sd-text-input">
        <input type="text" placeholder="" class="sd-text-input" name="shipday_delivery_pickup_label"
               value="<?php echo $datetime_heading_label?>"
        />
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
