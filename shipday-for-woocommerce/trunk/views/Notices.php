<?php

class Notices {
	public static function init() {
		add_action( 'admin_notices', __CLASS__ . '::shipday_api_key_notice' );
        add_action('admin_notices', __CLASS__. '::rest_api_key_notice');
	}

	public static function shipday_api_key_notice() {
		$api_key         = get_option( 'wc_settings_tab_shipday_api_key' );
		$shipday_tab_url = 'admin.php?page=wc-settings&tab=settings_tab_shipday';
		if ( empty( $api_key ) ) {
			?>
            <div class='notice notice-warning is-dismissible'>
                <p>Your Shipday API Key Field is blank. To set up API Key, <a href="<?php echo $shipday_tab_url; ?>">Click
                        Here</a>.</p>
            </div>";
			<?php
		}
	}

	public static function rest_api_key_notice() {
		$rest_api_section_url = 'admin.php?page=wc-settings&tab=advanced&section=keys';
		$uuid                 = get_option( 'shipday_registered_uuid' );
		if ( empty( $uuid ) ) {
			?>
                <div class='notice notice-warning is-dismissible'>
                    <p>You have not added REST API Key to your site yet. To add REST API Key,
                        <a href="<?php echo $rest_api_section_url; ?>">Click here</a>.</p>
                </div>
			<?php
		}
	}
}