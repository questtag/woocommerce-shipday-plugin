<?php
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
?>
<div class="sd-root">

  <div class="sd-header">
    <div class="sd-header-text">
      <div class="sd-header-title">Shipday for WooCommerce</div>
      <div class="sd-header-subtitle">
        Configure how your WooCommerce store sends delivery and pickup orders to Shipday.
      </div>
    </div>
  </div>

  <div class="sd-layout">
    <div class="sd-sidebar">
      <div class="sd-tab-list">
        <!-- GENERAL TAB BUTTON (active by default) -->
        <button type="button" data-tab="general" class="sd-tab-button <?php echo $active_tab === 'general' ? 'sd-tab-button--active' : ''; ?>">
          <span>General</span>
        </button>

        <!-- REST API TAB BUTTON -->
        <?php if ( !is_plugin_active( 'dokan-lite/dokan.php' ) && !is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' )) {?>
          <button type="button" data-tab="rest-api" class="sd-tab-button <?php echo $active_tab === 'rest-api' ? 'sd-tab-button--active' : ''; ?>">
            <span>Rest API</span>
          </button>
        <?php } ?>

        <!-- DATETIME AND ORDER TAB BUTTON (ready for future use) -->
        <button type="button" data-tab="order-datetime" class="sd-tab-button">
          <span>Datetime and Order</span>
        </button>

        <!-- Delivery -->
        <button type="button" data-tab="delivery" class="sd-tab-button">
          <span>Delivery</span>
        </button>

        <!-- Pickup -->
        <button type="button" data-tab="pickup" class="sd-tab-button">
          <span>Pickup</span>
        </button>


      </div>
    </div>

    <!-- GENERAL TAB PANEL -->
    <div data-tab-panel="general" class="sd-tab-panel <?php echo $active_tab === 'general' ? 'sd-tab-panel--active' : ''; ?>">
        <?php
          include plugin_dir_path(__FILE__) . 'tab-shipday-connect.php';
        ?>
    </div>

    <!-- REST API TAB PANEL -->
    <div data-tab-panel="rest-api" class="sd-tab-panel <?php echo $active_tab === 'rest-api' ? 'sd-tab-panel--active' : ''; ?>">
        <?php
        include plugin_dir_path( __FILE__ ) . 'tab-rest-api.php';
        ?>
    </div>

    <div data-tab-panel="order-datetime" class="sd-tab-panel">
        <?php
        include plugin_dir_path(__FILE__) . 'tab-general.php';
        ?>
    </div>

    <div data-tab-panel="delivery" class="sd-tab-panel">
        <?php
        include plugin_dir_path( __FILE__ ) . 'tab-delivery.php';
        ?>
    </div>

    <div data-tab-panel="pickup" class="sd-tab-panel">
        <?php
        include plugin_dir_path( __FILE__ ) . 'tab-pickup.php';
        ?>
    </div>

  </div>
</div>

<script>
  (function () {
    const inputs = document.querySelectorAll('.shipday-time-input__field');

    inputs.forEach(function (input) {
      // Keep only digits and max 2 chars while typing
      input.addEventListener('input', function () {
        let v = input.value.replace(/\D/g, '').slice(0, 2);
        input.value = v;
      });

      // On blur: pad to 2 digits and clamp to allowed range
      input.addEventListener('blur', function () {
        let v = input.value.replace(/\D/g, '');

        if (v === '') {
          v = '0';
        }

        let num = parseInt(v, 10);
        if (isNaN(num)) {
          num = 0;
        }
        console.log(num);

        const type = input.getAttribute('data-time-type');
        if (type === 'hour') {
          // 00–12
          if (num < 0) num = 0;
          if (num > 12) num = 12;
        } else if (type === 'minute') {
          // 00–59
          if (num < 0) num = 0;
          if (num > 59) num = 59;
        }

        input.value = String(num).padStart(2, '0');
      });
    });
  })();
</script>

