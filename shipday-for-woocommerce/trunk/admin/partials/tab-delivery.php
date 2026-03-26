<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Included admin partial uses file-scoped template variables.
$enable_delivery_date = get_option('shipday_enable_delivery_date', "no") === "yes";
$delivery_date_mandatory = get_option('shipday_delivery_date_mandatory', "no") === "yes";
$available_delivery_days = get_option('shipday_avaialble_delivery_days', ["0", "1", "2", "3", "4", "5", "6"]);
$selectable_delivery_days = get_option('shipday_selectable_delivery_days', 30);

$enable_delivery_time = get_option('shipday_enable_delivery_time', "no") === "yes";
$delivery_time_mandatory = get_option('shipday_delivery_time_mandatory', "no") === "yes";
$time_format = get_option( 'shipday_time_format', '12-hour' );
$is_24_hour = '24-hour' === $time_format;

$normalize_delivery_slot = static function ( $slot, $default_hour, $default_ampm, $is_24_hour ) {
	$slot = is_array( $slot ) ? $slot : array();
	$hour = isset( $slot['hh'] ) ? absint( $slot['hh'] ) : $default_hour;
	$minute = isset( $slot['mm'] ) ? max( 0, min( 59, absint( $slot['mm'] ) ) ) : 0;
	$ampm = isset( $slot['ampm'] ) && in_array( strtoupper( (string) $slot['ampm'] ), array( 'AM', 'PM' ), true )
		? strtoupper( (string) $slot['ampm'] )
		: $default_ampm;

	$is_stored_24_hour = ! isset( $slot['ampm'] ) || $hour > 12 || 0 === $hour;
	if ( $is_stored_24_hour ) {
		$hour_24 = max( 0, min( 23, $hour ) );
	} else {
		$hour_12 = max( 1, min( 12, $hour ) );
		$hour_24 = $hour_12 % 12;
		if ( 'PM' === $ampm ) {
			$hour_24 += 12;
		}
	}

	if ( $is_24_hour ) {
		return array(
			'hh'   => str_pad( (string) $hour_24, 2, '0', STR_PAD_LEFT ),
			'mm'   => str_pad( (string) $minute, 2, '0', STR_PAD_LEFT ),
			'ampm' => $hour_24 >= 12 ? 'PM' : 'AM',
		);
	}

	$display_ampm = $hour_24 >= 12 ? 'PM' : 'AM';
	$display_hour = $hour_24 % 12;
	if ( 0 === $display_hour ) {
		$display_hour = 12;
	}

	return array(
		'hh'   => str_pad( (string) $display_hour, 2, '0', STR_PAD_LEFT ),
		'mm'   => str_pad( (string) $minute, 2, '0', STR_PAD_LEFT ),
		'ampm' => $display_ampm,
	);
};

$start_delivery_slot = $normalize_delivery_slot(
	get_option( 'shipday_delivery_time_slot_start', array( 'hh' => '09', 'mm' => '00', 'ampm' => 'AM' ) ),
	9,
	'AM',
	$is_24_hour
);
$end_delivery_slot = $normalize_delivery_slot(
	get_option( 'shipday_delivery_time_slot_end', array( 'hh' => '05', 'mm' => '00', 'ampm' => 'PM' ) ),
	5,
	'PM',
	$is_24_hour
);
$delivery_slot_duration = get_option('shipday_delivery_time_slot_duration', "60");

$datetime_enabled = get_option('shipday_enable_datetime_plugin', "no") === "yes";

?>
<div class="sd-panel-header">
  <div class="sd-panel-title-wrap">
    <div class="sd-panel-title">Delivery Settings</div>
    <div class="sd-field-description">
      Configure your store’s delivery date and time options at checkout.
    </div>
  </div>
  <div data-save="delivery" data-state="Disabled" class="sd-save-button sd-save-button--disabled">
    <span>Save changes</span>
  </div>
</div>


<div class="sd-panel-body">

  <fieldset class="sd-fieldset" <?php disabled( ! $datetime_enabled ); ?> aria-disabled="<?php echo esc_attr( $datetime_enabled ? 'false' : 'true' ); ?>">
  <p class="shipday-delivery-notice"><span
        class="dashicons dashicons-yes"></span><?php esc_html_e( ' Settings Changed Successfully', 'shipday-for-woocommerce' ); ?>
  </p>

  <form action="" method="post" id="shipday-delivery-settings-form">
      <?php wp_nonce_field('shipday_nonce'); ?>

    <div class="shipday-delivery-card">
      <div class="shipday-delivery-card__header">
        <div class="shipday-delivery-card__title">
          Delivery Date
        </div>
      </div>
      <div class="shipday-delivery-card__content">

        <!-- Enable delivery date -->
        <div class="shipday-toggle-row">
          <label class="shipday-switch">
            <input
                type="checkbox"
                id="shipday_enable_delivery_date"
                name="shipday_enable_delivery_date"
                class="shipday-switch__input"
                <?php checked( $enable_delivery_date ); ?>
            />
            <span class="shipday-switch__track">
              <span class="shipday-switch__thumb"></span>
            </span>
          </label>

          <div class="shipday-toggle-row__text">
            <div class="shipday-toggle-row__title">
              Enable delivery date
            </div>
          </div>
        </div>


        <!-- make delivery date mandatory -->
        <div class="shipday-toggle-row">
          <label class="shipday-switch">
            <input
                type="checkbox" id="shipday_delivery_date_mandatory" name="shipday_delivery_date_mandatory" class="shipday-switch__input"
                <?php checked( $delivery_date_mandatory ); ?>
            />
            <span class="shipday-switch__track">
              <span class="shipday-switch__thumb"></span>
            </span>
          </label>

          <div class="shipday-toggle-row__text">
            <div class="shipday-toggle-row__title">
              Make delivery date field mandatory
            </div>
          </div>
        </div>



      <div class="shipday-select-days" style="padding-top: 23px;">

        <div class="shipday-select-days__header">
          <span class="shipday-select-days__label">Select delivery days</span>

          <span class="shipday-tooltip" tabindex="0">
            <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
            <span class="shipday-tooltip__text">
              Customer can choose only these week days for delivery in the calendar.
            </span>
          </span>
        </div>


        <div class="shipday-select-days__chips">

          <label class="shipday-day-chip">
            <input type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="0"
                <?php checked( in_array( '0', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Sunday</span>
          </label>

          <label class="shipday-day-chip">
            <input
                type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="1"
                <?php checked( in_array( '1', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Monday</span>
          </label>

          <label class="shipday-day-chip">
            <input
                type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="2"
                <?php checked( in_array( '2', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Tuesday</span>
          </label>

          <label class="shipday-day-chip">
            <input
                type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="3"
                <?php checked( in_array( '3', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Wednesday</span>
          </label>

          <label class="shipday-day-chip">
            <input
                type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="4"
                <?php checked( in_array( '4', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Thursday</span>
          </label>

          <label class="shipday-day-chip">
            <input
                type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="5"
                <?php checked( in_array( '5', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Friday</span>
          </label>

          <label class="shipday-day-chip">
            <input
                type="checkbox" class="shipday-day-chip__input" name="shipday_avaialble_delivery_days[]" value="6"
                <?php checked( in_array( '6', $available_delivery_days, true ) ); ?>
            />
            <span class="shipday-day-chip__pill">Saturday</span>
          </label>

        </div>
      </div>


      <div class="shipday-next-available" style="padding-top: 30px;">
        <div class="shipday-next-available__header">
          <span class="shipday-next-available__label">
            Allow delivery in next available days
          </span>

          <span class="shipday-tooltip" tabindex="0">
            <span class="shipday-tooltip__icon" aria-hidden="true">i</span>
            <span class="shipday-tooltip__text">
              Number of next days customers can select for delivery in the checkout. Calendar will show only these available days.
            </span>
          </span>
        </div>

        <div class="sd-input-wrapper">
          <input type="text" placeholder="" class="sd-text-input" name="shipday_selectable_delivery_days"
                 value="<?php echo esc_attr( $selectable_delivery_days ); ?>"
          />
        </div>
      </div>
    </div>
    </div>


<!-- Delivery Time -->
    <div class="shipday-delivery-card" style="margin-top: 30px;">
      <div class="shipday-delivery-card__header">
        <div class="shipday-delivery-card__title">
          Delivery Time
        </div>
      </div>
      <div class="shipday-delivery-card__content">


        <!-- Enable delivery time -->
        <div class="shipday-toggle-row">
          <label class="shipday-switch">
            <input
                type="checkbox" id="shipday_enable_delivery_time" name="shipday_enable_delivery_time" class="shipday-switch__input"
                <?php checked( $enable_delivery_time ); ?>
            />
            <span class="shipday-switch__track">
              <span class="shipday-switch__thumb"></span>
            </span>
          </label>

          <div class="shipday-toggle-row__text">
            <div class="shipday-toggle-row__title">
              Enable delivery time
            </div>
          </div>
        </div>


        <!-- make delivery time mandatory -->
        <div class="shipday-toggle-row">
          <label class="shipday-switch">
            <input
                type="checkbox" id="shipday_delivery_time_mandatory" name="shipday_delivery_time_mandatory" class="shipday-switch__input"
                <?php checked( $delivery_time_mandatory ); ?>
            />
            <span class="shipday-switch__track">
              <span class="shipday-switch__thumb"></span>
            </span>
          </label>

          <div class="shipday-toggle-row__text">
            <div class="shipday-toggle-row__title">
              Make delivery time field mandatory
            </div>
          </div>
        </div>

        <div class="shipday-delivery-slots">

          <!-- Starts from -->
          <div class="shipday-time-row">
            <div class="shipday-time-row__label">
              Delivery slot starts from
            </div>

            <div class="shipday-time-row__inputs">
              <!-- Hour -->
              <div class="shipday-time-input sd-text-input ">
                <input data-time-type="hour"
                    type="number" min="<?php echo esc_attr( $is_24_hour ? '0' : '1' ); ?>" max="<?php echo esc_attr( $is_24_hour ? '23' : '12' ); ?>" id="shipday_delivery_time_slot_start_hh" name="shipday_delivery_time_slot_start_hh"
                    class="shipday-time-input__field"
                    value="<?php echo esc_attr( $start_delivery_slot['hh'] ); ?>"
                />
              </div>

              <div class="shipday-time-separator">:</div>

              <!-- Minute -->
              <div class="shipday-time-input sd-text-input ">
                <input data-time-type="minute"
                    type="number" min="0" max="59" step="5" id="shipday_delivery_time_slot_start_mm" name="shipday_delivery_time_slot_start_mm"
                    class="shipday-time-input__field"
                    value="<?php echo esc_attr( $start_delivery_slot['mm'] ); ?>"
                />
              </div>

              <?php if ( ! $is_24_hour ) { ?>
                <!-- AM/PM -->
                <div class="shipday-ampm-select">
                  <select
                      id="shipday_delivery_time_slot_start_ampm"
                      name="shipday_delivery_time_slot_start_ampm"
                      class="shipday-ampm-select__field sd-text-input"
                  >
                    <option value="AM" <?php selected( $start_delivery_slot['ampm'], 'AM' ); ?>>AM</option>
                    <option value="PM" <?php selected( $start_delivery_slot['ampm'], 'PM' ); ?>>PM</option>
                  </select>
                </div>
              <?php } ?>
            </div>
          </div>

          <!-- Ends at -->
          <div class="shipday-time-row">
            <div class="shipday-time-row__label">
              Delivery slot ends at
            </div>

            <div class="shipday-time-row__inputs">
              <!-- Hour -->
              <div class="shipday-time-input sd-text-input ">
                <input data-time-type="hour"
                    type="number"  min="<?php echo esc_attr( $is_24_hour ? '0' : '1' ); ?>"  max="<?php echo esc_attr( $is_24_hour ? '23' : '12' ); ?>" step="1"  id="shipday_delivery_time_slot_end_hh" name="shipday_delivery_time_slot_end_hh"
                    class="shipday-time-input__field"
                    value="<?php echo esc_attr( $end_delivery_slot['hh'] ); ?>"
                />
              </div>

              <div class="shipday-time-separator">:</div>

              <!-- Minute -->
              <div class="shipday-time-input sd-text-input">
                <input data-time-type="minute"
                    type="number" min="0" max="59" step="5" id="shipday_delivery_time_slot_end_mm" name="shipday_delivery_time_slot_end_mm"
                    class="shipday-time-input__field"
                    value="<?php echo esc_attr( $end_delivery_slot['mm'] ); ?>"
                />
              </div>

              <?php if ( ! $is_24_hour ) { ?>
                <!-- AM/PM -->
                <div class="shipday-ampm-select">
                  <select
                      id="shipday_delivery_time_slot_end_ampm"
                      name="shipday_delivery_time_slot_end_ampm"
                      class="shipday-ampm-select__field sd-text-input"
                  >
                    <option value="AM" <?php selected( $end_delivery_slot['ampm'], 'AM' ); ?>>AM</option>
                    <option value="PM" <?php selected( $end_delivery_slot['ampm'], 'PM' ); ?>>PM</option>
                  </select>
                </div>
              <?php } ?>
            </div>
          </div>

          <!-- Slot duration -->
          <div class="shipday-slot-duration-row">
            <div class="shipday-slot-duration-row__label">
              Slot duration in minutes
            </div>

            <div class="shipday-slot-duration-field">
              <select
                  id="shipday_delivery_time_slot_duration"
                  name="shipday_delivery_time_slot_duration"
                  class="shipday-slot-duration-field__select sd-text-input"
              >
                <option value="10" <?php selected( $delivery_slot_duration, '10' ); ?>>10</option>
                <option value="15" <?php selected( $delivery_slot_duration, '15' ); ?>>15</option>
                <option value="30" <?php selected( $delivery_slot_duration, '30' ); ?>>30</option>
                <option value="45" <?php selected( $delivery_slot_duration, '45' ); ?>>45</option>
                <option value="60" <?php selected( $delivery_slot_duration, '60' ); ?>>60</option>
                <option value="90" <?php selected( $delivery_slot_duration, '90' ); ?>>90</option>
                <option value="120" <?php selected( $delivery_slot_duration, '120' ); ?>>120</option>
                <option value="150" <?php selected( $delivery_slot_duration, '150' ); ?>>150</option>
                <option value="180" <?php selected( $delivery_slot_duration, '180' ); ?>>180</option>
                <option value="240" <?php selected( $delivery_slot_duration, '240' ); ?>>240</option>
                <option value="300" <?php selected( $delivery_slot_duration, '300' ); ?>>300</option>
                <option value="360" <?php selected( $delivery_slot_duration, '360' ); ?>>360</option>
              </select>
            </div>
          </div>

        </div>



      </div>
    </div>
  </form>
  </fieldset>
</div>
