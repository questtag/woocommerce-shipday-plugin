<?php


class Shipday_Woo_DateTime_Util {

    protected static $instance = null;
    const WEEK_DAYS = ["0", "1", "2", "3", "4", "5", "6"];
    const DEFAULT_START_SLOT = ["hh"=>"09:00", "mm" => "00", "amp" => "AM"];
    const DEFAULT_END_SLOT = ["hh"=>"05:00", "mm" => "00", "amp" => "PM"];

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function reset_session() {
        WC()->session->set( 'on_change', false );
        WC()->session->set( 'shipday_order_type', NULL );
        WC()->session->set( 'shipday_delivery_date', NULL );
        WC()->session->set( 'shipday_delivery_time', NULL );
    }

    public static function get_default_settings() {
        $data = [];

        $data['enable_datetime_plugin'] = get_option('shipday_enable_datetime_plugin', "no") === "yes";
        $data['today'] = wp_date('Y-m-d', current_time('timestamp', 1));

        // Other settings
        $data['delivery_heading_checkout'] = get_option('shipday_delivery_pickup_label', __("Delivery/Pickup info", "shipday-woo-delivery"));
        $data['enable_delivery_option'] = get_option('shipday_enable_delivery_option', "no") === "yes";
        $data['delivery_option_field_label'] = __("Order Option", "shipday-woo-delivery");
        $data['delivery_options'] = ["Delivery" => "Delivery", "Pickup" => "Pickup"];

        // Delivery dates
        $data['enable_delivery_date'] = get_option('shipday_enable_delivery_date', "no") === "yes";
        $data['delivery_date_selectable_days'] = get_option('shipday_selectable_delivery_days', 30);
        $data['delivery_disable_week_days'] = self::get_disable_week_days(get_option('shipday_avaialble_delivery_days', self::WEEK_DAYS));
        $data['delivery_date_field_label'] = __("Delivery Date", "shipday-woo-delivery");
        $data['auto_select_first_date'] = true;
        $data['delivery_date_mandatory'] = get_option('shipday_delivery_date_mandatory', "no") === "yes";
        $data['delivery_date_format'] = "F j, Y";
        $data['week_starts_from'] = "0";

        // Delivery times
        $data['enable_delivery_time'] = get_option('shipday_enable_delivery_time', "no") === "yes";
        $data['delivery_time_field_label'] = __("Delivery Time", "shipday-woo-delivery");
        $data['delivery_time_mandatory'] = get_option('shipday_delivery_time_mandatory', "no") === "yes";;
        $data['auto_select_first_time'] = false;
        $data['disabled_current_time_slot'] = false;
        $start_delivery_slot = get_option('shipday_delivery_time_slot_start', self::DEFAULT_START_SLOT);
        $end_delivery_slot = get_option('shipday_delivery_time_slot_end', self::DEFAULT_END_SLOT);
        $delivery_slot_duration = get_option('shipday_delivery_time_slot_duration', "60");
        $data['delivery_time_options'] = self::get_time_slots_from_range($start_delivery_slot, $end_delivery_slot, $delivery_slot_duration);

        // Pickup dates
        $data['enable_pickup_date'] = get_option('shipday_enable_pickup_date', "no") === "yes";
        $data['pickup_date_selectable_days'] = get_option('shipday_selectable_pickup_days', 15);
        $data['pickup_disable_week_days'] = self::get_disable_week_days(get_option('shipday_avaialble_pickup_days', self::WEEK_DAYS));
        $data['pickup_date_field_label'] = __("Pickup Date", "shipday-woo-delivery");
        $data['pickup_auto_select_first_date'] = true;
        $data['pickup_date_mandatory'] = get_option('shipday_pickup_date_mandatory', "no") === "yes";;
        $data['pickup_date_format'] = "F j, Y";
        $data['pickup_week_starts_from'] = "0";
        $data['pickup_selectable_date'] = get_option('shipday_selectable_pickup_days', 30);

        // Pickup times
        $data['enable_pickup_time'] = get_option('shipday_enable_pickup_time', "no") === "yes";
        $data['pickup_time_field_label'] = __("Pickup Time", "shipday-woo-delivery");
        $data['pickup_time_mandatory'] = get_option('shipday_pickup_time_mandatory', "no") === "yes";
        $data['pickup_auto_select_first_time'] = false;
        $data['pickup_disabled_current_time_slot'] = false;
        $start_pickup_slot = get_option('shipday_pickup_time_slot_start', self::DEFAULT_START_SLOT);
        $end_pickup_slot = get_option('shipday_pickup_time_slot_end', self::DEFAULT_END_SLOT);
        $pickup_slot_duration = get_option('shipday_pickup_time_slot_duration', "60");
        $data['pickup_time_options'] =  self::get_time_slots_from_range($start_pickup_slot, $end_pickup_slot, $pickup_slot_duration);

        // Disable dates
        $data['disable_dates'] = [];
        $data['pickup_disable_dates'] = [];

        // Passed dates
        $data['disable_delivery_date_passed_time'] = [];
        $data['disable_pickup_date_passed_time'] = [];

        // Localization
        $data['checkout_delivery_option_notice'] = __("Please select order type", "shipday-woo-delivery");
        $data['checkout_date_notice'] = __("Please enter delivery date", "shipday-woo-delivery");
        $data['checkout_pickup_date_notice'] = __("Please enter pickup date", "shipday-woo-delivery");
        $data['checkout_time_notice'] = __("Please select delivery time", "shipday-woo-delivery");
        $data['checkout_pickup_time_notice'] = __("Please select pickup time", "shipday-woo-delivery");
        $data['select_order_type_text'] = __("Select order type", "shipday-woo-delivery");
        $data['select_delivery_time_text'] = __("Select delivery time", "shipday-woo-delivery");
        $data['select_pickup_time_text'] = __("Select pickup time", "shipday-woo-delivery");

        return $data;
    }


    public static function get_disable_week_days($enabled_week_days) {
        $disabled_week_days = [];
        foreach (self::WEEK_DAYS as $week_day) {
            if(!in_array($week_day, $enabled_week_days)) {
                $disabled_week_days[] = $week_day;
            }
        }
        return $disabled_week_days;
    }

    static function validate_and_set_time( $settings, $type, $selected ) {

        $time_options = $type === 'Delivery' ? $settings["delivery_time_options"] : $settings["pickup_time_options"];
        $enable_time = $type === 'Delivery' ? $settings['enable_delivery_time'] : $settings['enable_pickup_time'];
        $auto_select_first_time = $type === 'Delivery' ? $settings['auto_select_first_time'] : $settings['pickup_auto_select_first_time'];
        $session_name = $type === 'Delivery' ? 'shipday_delivery_time' : 'pickup_time';

        if ( !empty( $selected ) ) {
            if ( isset( $time_options[$selected]['disabled'] ) && !$time_options[$selected]['disabled'] ) {
                return $selected;
            }
        }

        $on_change = WC()->session->get( "on_change", false );

        if ( $enable_time && $auto_select_first_time && !$on_change ) {
            foreach ( $time_options as $key => $data ) {
                if ( !$data['disabled'] ) {
                    WC()->session->set( $session_name, $key );
                    return $key;
                }
            }
        }

        WC()->session->set( $session_name, NULL );
        return NULL;
    }

    public static function get_time_slots_from_range(array $start_slot, array $end_slot, int $duration) {
        $slots = [];

        $toMinutes = function(array $slot): int {
            $hhRaw = isset($slot['hh']) ? (string) $slot['hh'] : '0';
            $h    = (int) $hhRaw;
            $m    = isset($slot['mm']) ? (int) $slot['mm'] : 0;
            $ampm = isset($slot['ampm']) ? strtoupper((string) $slot['ampm']) : 'AM';
            // Normalize to 24h based on AM/PM
            if ($ampm === 'PM') {
                if ($h !== 12) {
                    $h += 12;
                }
            }
            return $h * 60 + $m;
        };

        // Helper: minutes since midnight → "h:i A"
        $formatMinutes = function(int $minutes): string {
            $h24 = intdiv($minutes, 60);
            $m   = $minutes % 60;

            $ampm = ($h24 >= 12) ? 'PM' : 'AM';
            $h12  = $h24 % 12;
            if ($h12 === 0) {
                $h12 = 12;
            }
            return sprintf('%02d:%02d %s', $h12, $m, $ampm);
        };

        if ($duration <= 0) {
            return $slots; // no slots if duration is invalid
        }

        $startMinutes = $toMinutes($start_slot);
        $endMinutes   = $toMinutes($end_slot);

        if ($endMinutes <= $startMinutes) {
            return $slots; // no slots if range is invalid
        }

        $current = $startMinutes;

        while ($current < $endMinutes) {
            $next = $current + $duration;
            if ($next > $endMinutes) {
                $next = $endMinutes; // last, shorter slot
            }

            $fromLabel = $formatMinutes($current);
            $toLabel   = $formatMinutes($next);
            $label     = $fromLabel . ' - ' . $toLabel;

            $slots[$label] = $label;

            $current = $next;
        }
        return $slots;
    }
}
