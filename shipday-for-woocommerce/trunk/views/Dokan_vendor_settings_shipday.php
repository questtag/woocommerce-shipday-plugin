<?php
require_once dirname(__DIR__). '/functions/logger.php';

class Dokan_vendor_settings_shipday
{
    public static function init() {
        add_action('dokan_store_profile_saved', __CLASS__.'::add_api_key', 10, 3);
        add_action("dokan_settings_after_banner", __CLASS__.'::settings', 10, 3);
    }

    public static function add_api_key($store_id) {
        $post_data = wp_unslash($_POST);
        if (!is_null($post_data) && !is_null($post_data['shipday_api_key']) && !empty(trim($post_data['shipday_api_key'])))
        update_user_meta($store_id, 'shipday_api_key', trim($post_data['shipday_api_key']));
    }

    public static function settings($current_user, $profile_info) {
        $api_key = get_user_meta($current_user, 'shipday_api_key', true);
        $value = empty($api_key) ? "" : "value = ".$api_key;
        echo '<div class="dokan-form-group">
            <label class="dokan-w3 dokan-control-label" for="shipday_api_key">Shipday API Key</label>
            <div class="dokan-w5 dokan-text-left">
                <input id="shipday_api_key"  name="shipday_api_key"'. $value. ' class="dokan-form-control" type="text">
            </div>
        </div> ';
    }
}