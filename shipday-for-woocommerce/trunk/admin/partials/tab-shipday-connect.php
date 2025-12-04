<?php
$api_key = get_option('wc_settings_tab_shipday_api_key');
$pickup_order_enabled = get_option('wc_settings_tab_shipday_enable_pickup', "no") === "yes";
$delivery_order_enabled = get_option('wc_settings_tab_shipday_enable_delivery', "yes") === "yes";
$order_sync_enabled = get_option('wc_settings_tab_shipday_sync', "yes") === "yes";

$manage_order = get_option('wc_settings_tab_shipday_order_manage', 'admin_manage');
?>
<div class="sd-panel-header">
    <div class="sd-panel-title-wrap">
        <div class="sd-panel-title">Connect your Shipday Account</div>
        <div class="sd-field-description">
          We can connect your WooCommerce store to your Shipday account so you can manage all pickup and delivery orders directly from your Shipday dashboard.
        </div>
    </div>
    <div data-save="shipday-connect" data-state="Disabled" class="sd-save-button sd-save-button--disabled">
        <span>Save changes</span>
    </div>
</div>

<div class="sd-panel-body">
  <p class="shipday-connect-notice"><span class="dashicons dashicons-yes"></span><?php _e(' Settings Changed Successfully', 'shipday-delivery'); ?></p>
  <form action="" method="post" id ="shipday-connect-settings-form">
      <?php wp_nonce_field('shipday_nonce'); ?>
    <div class="sd-field">
        <div class="sd-field-text">
            <div class="sd-field-label">Shipday API Key</div>
            <div class="sd-field-description">
                To get API Key, Login to your Shipday account and go to My Account &gt; Profile &gt; API Key
            </div>
        </div>
        <div class="sd-input-wrapper">
            <input type="text" placeholder="Enter API Key" class="sd-text-input" name="shipday_api_key"
                   value="<?php echo (isset($api_key) && !empty($api_key)) ? stripslashes($api_key) : '' ?>"
            />
        </div>

    </div>

    <div class="shipday-toggle-group" style="margin-top: 30px;">

      <!-- Enable pickup orders -->
      <div class="shipday-toggle-row">
        <label class="shipday-switch">
          <input
              type="checkbox"
              id="wc_settings_tab_shipday_enable_pickup"
              name="wc_settings_tab_shipday_enable_pickup"
              class="shipday-switch__input"
              <?php echo ($pickup_order_enabled) ? "checked" : "" ?>
          />
          <span class="shipday-switch__track">
              <span class="shipday-switch__thumb"></span>
            </span>
        </label>

        <div class="shipday-toggle-row__text">
          <div class="shipday-toggle-row__title">
            Send pickup orders
          </div>
        </div>
      </div>


      <!-- Sync previous orders -->
      <div class="shipday-divider"></div>

      <div class="shipday-toggle-row">
        <label class="shipday-switch">
          <input
              type="checkbox"
              id="wc_settings_tab_shipday_sync"
              name="wc_settings_tab_shipday_sync"
              class="shipday-switch__input"
              <?php echo ($order_sync_enabled) ? "checked" : "" ?>
          />
          <span class="shipday-switch__track">
                <span class="shipday-switch__thumb"></span>
              </span>
        </label>

        <div class="shipday-toggle-row__text">
          <div class="shipday-toggle-row__title">
            Sync previous orders
            <span class="shipday-tooltip" tabindex="0" aria-label="Each store/location manages its own orders in Shipday.">
                <span class="shipday-tooltip__icon">i</span>
                <span class="shipday-tooltip__text">Enabling this will send your missing orders which are in 'Processing' state.</span>
            </span>
          </div>
        </div>
      </div>

      <!-- Manage order (radio) -->
        <?php if ( is_plugin_active( 'dokan-lite/dokan.php' ) || is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' )) { ?>

          <div class="shipday-divider"></div>

          <div class="shipday-toggle-row">
            <div class="shipday-toggle-row__text">
              <div class="shipday-toggle-row__title">
                Manage order
                <span class="shipday-tooltip" tabindex="0" aria-label="Each store/location manages its own orders in Shipday.">
                  <span class="shipday-tooltip__icon">i</span>
                  <span class="shipday-tooltip__text">Two options-<br>Admin: manages all orders in Shipday, <br>Vendor: manges individual store orders in Shipday.</span>
                </span>
              </div>
              <div class="shipday-toggle-row__description">Choose who manages orders in Shipday</div>
            </div>

            <fieldset class="sd-radio-group" role="radiogroup" aria-label="Manage order">
              <label class="sd-radio">
                <input
                    type="radio"
                    class="sd-radio__input"
                    name="wc_settings_tab_shipday_order_manage"
                    value="admin_manage"
                    <?php echo ($manage_order === 'admin_manage') ? 'checked' : ''; ?>
                />
                <span class="sd-radio__mark" aria-hidden="true"></span>
                <span class="sd-radio__label">Admin manage</span>
              </label>

              <label class="sd-radio">
                <input
                    type="radio"
                    class="sd-radio__input"
                    name="wc_settings_tab_shipday_order_manage"
                    value="vendor_manage"
                    <?php echo ($manage_order === 'vendor_manage') ? 'checked' : ''; ?>
                />
                <span class="sd-radio__mark" aria-hidden="true"></span>
                <span class="sd-radio__label">Vendor manage</span>
              </label>
            </fieldset>
          </div>

        <?php } ?>





    </div>


  </form>
</div>
