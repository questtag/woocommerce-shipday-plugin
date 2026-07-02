<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class Shipday_Woo_Delivery_Block_Integration implements IntegrationInterface {

    /**
     * The name of the integration.
     *
     * @return string
     */
    public function get_name() {
        return 'shipday-woo-delivery-block';
    }

    /**
     * When called invokes any initialization/setup for the integration.
     */
    public function initialize() {
        $this->register_block_editor_scripts();
        $this->register_block_frontend_scripts();
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles() {
        return array( Shipday_Woo_Delivery_Block::$FRONTEND_SCRIPT_HANDLE, 'flatpickr_js' );
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles() {
        return array( Shipday_Woo_Delivery_Block::$EDITOR_SCRIPT_HANDLE );
    }

    /**
     * An array of key, value pairs of data made available to the block on the client side.
     *
     * @return array
     */
    public function get_script_data() {
        return [];
    }

    /**
     * Register scripts for delivery date block editor.
     *
     * @return void
     */
    public function register_block_editor_scripts() {
        if ( wp_script_is( Shipday_Woo_Delivery_Block::$EDITOR_SCRIPT_HANDLE, 'registered' ) ) {
            wp_localize_script(
                Shipday_Woo_Delivery_Block::$EDITOR_SCRIPT_HANDLE,
                'shipdayWooDeliveryBlockData',
                array(
                    'blockFieldPosition' => Shipday_Woo_Delivery_Block::get_block_field_position(),
                )
            );
        }
    }

    /**
     * Register scripts for frontend block.
     *
     * @return void
     */
    public function register_block_frontend_scripts() {
        wp_register_script(
            Shipday_Woo_Delivery_Block::$FRONTEND_SCRIPT_HANDLE,
            plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js',
            array( 'wp-api-fetch', 'wp-data', 'wp-plugins', 'wp-element', 'wp-components', 'wp-hooks', 'wp-i18n', 'wc-blocks-checkout', 'flatpickr_js' ),
            '2.3.1',
            true
        );

        wp_localize_script(
            Shipday_Woo_Delivery_Block::$FRONTEND_SCRIPT_HANDLE,
            'shipdayWooDeliveryBlockData',
            array(
                'blockFieldPosition' => Shipday_Woo_Delivery_Block::get_block_field_position(),
            )
        );

        wp_enqueue_style( Shipday_Woo_Delivery_Block::$FRONTEND_STYLE_HANDLE );
    }

    /**
     * Get the file modified time as a cache buster if we're in dev mode.
     *
     * @param string $file Local path to the file.
     * @return string The cache buster value to use for the given file.
     */
    protected function get_file_version( $file ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && file_exists( $file ) ) {
            return filemtime( $file );
        }
        return "2.0.0";
    }

}
