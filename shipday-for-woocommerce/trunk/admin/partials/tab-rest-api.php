<?php
  $consumer_key = get_option('wc_settings_tab_shipday_rest_api_consumer_key');
  $consumer_secret = get_option('wc_settings_tab_shipday_rest_api_consumer_secret');
?>
<div class="sd-panel-header">
  <div class="sd-panel-title-wrap">
    <div class="sd-panel-title">Rest API Settings</div>
    <div class="sd-field-description">
      Configure your REST API keys so WooCommerce orders are automatically marked as “Completed” when they’re delivered in Shipday.
    </div>
  </div>
  <div data-save="rest-api" data-state="Disabled" class="sd-save-button sd-save-button--disabled">
    <span>Save changes</span>
  </div>
</div>

<div class="sd-panel-body">
  <p class="shipday-rest-api-notice"><span class="dashicons dashicons-yes"></span><?php _e(' Settings Changed Successfully', 'shipday-delivery'); ?></p>
  <form action="" method="post" id ="shipday-rest-api-settings-form">
      <?php wp_nonce_field('shipday_nonce'); ?>
    <div class="sd-field">
      <div class="sd-field-text">
        <div class="sd-field-description">
          To get REST API Keys, go to WooCommerce > Settings > Advanced > API Key. Then generate a new API key with any description, give Read/Write permissions and copy consumer key and consumer secret and take note of the keys as you will not see it after leaving the page.
        </div>
      </div>

      <div class="rest-api-label-wrapper">
        <div class="rest-api-label">Consumer Key</div>
      </div>
      <div class="sd-input-wrapper">
        <input type="text" placeholder="Enter consumer Key" class="sd-text-input" name="shipday_consumer_key"
               value="<?php echo (isset($consumer_key) && !empty($consumer_key)) ? stripslashes($consumer_key) : '' ?>"
        />
      </div>


      <div class="rest-api-label-wrapper">
        <div class="rest-api-label">Consumer Secret</div>
      </div>
      <div class="sd-input-wrapper">
        <input type="text" placeholder="Enter consumer secret" class="sd-text-input" name="shipday_consumer_secret"
               value="<?php echo (isset($consumer_secret) && !empty($consumer_secret)) ? stripslashes($consumer_secret) : '' ?>"
        />
      </div>


    </div>
  </form>
</div>
