<?php

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
        $this->register_block_frontend_scripts();
    }

    /**
     * Returns an array of script handles to enqueue in the frontend context.
     *
     * @return string[]
     */
    public function get_script_handles() {
        return [$this->get_name(), 'flatpickr_js'];
    }

    /**
     * Returns an array of script handles to enqueue in the editor context.
     *
     * @return string[]
     */
    public function get_editor_script_handles() {
        return [];
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

    }

    /**
     * Register scripts for frontend block.
     *
     * @return void
     */
    public function register_block_frontend_scripts() {
        wp_register_script( $this->get_name(), plugin_dir_url( __FILE__ ) . 'assets/js/frontend.js', array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-hooks', 'wp-i18n', 'wc-blocks-checkout', 'flatpickr_js' ), "2.2.37", true );

        wp_enqueue_style( $this->get_name(), plugin_dir_url( __FILE__ ) . 'assets/css/frontend.css', "2.1.4" );
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
