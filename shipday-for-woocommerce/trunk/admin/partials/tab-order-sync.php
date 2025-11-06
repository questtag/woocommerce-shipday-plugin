<?php
$pickup_order_enabled = get_option('wc_settings_tab_shipday_enable_pickup', "no") === "yes";
$delivery_order_enabled = get_option('wc_settings_tab_shipday_enable_delivery', "yes") === "yes";
$order_sync_enabled = get_option('wc_settings_tab_shipday_sync', "yes") === "yes";

$manage_order = get_option('wc_settings_tab_shipday_order_manage', 'admin_manage');
?>
<div class="sd-panel-header">
  <div class="sd-panel-title-wrap">
    <div class="sd-panel-title">Order Sync Settings</div>
    <div class="sd-field-description">
      Select delivery methods you would like to sync with Shipday.
    </div>
  </div>
  <div data-save="order-sync" data-state="Disabled" class="sd-save-button sd-save-button--disabled">
    <span>Save changes</span>
  </div>
</div>


<div class="sd-panel-body">
  <p class="shipday-order-sync-notice">
    <span class="dashicons dashicons-yes"></span>
      <?php _e(' Settings Changed Successfully', 'shipday-delivery'); ?>
  </p>
  <form action="" method="post" id="shipday-order-sync-settings-form">
      <?php wp_nonce_field('shipday_nonce'); ?>







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
              <div class="shipday-toggle-row__description">Choose who manages orders in Shipday.</div>
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
