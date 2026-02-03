<?php
// Default to the new Overview tab if none is provided
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
?>
<div class="sd-root">

  <!-- Header (unchanged) -->
  <div class="sd-header">
    <div class="sd-header-text">
      <div class="sd-header-title">Shipday for WooCommerce</div>
      <div class="sd-header-subtitle">
        Configure how your WooCommerce store sends delivery and pickup orders to Shipday.
      </div>
    </div>
  </div>

  <div class="sd-layout">
    <!-- Sidebar -->
    <div class="sd-sidebar">
      <div class="sd-tab-list" role="tablist" aria-orientation="vertical">

        <!-- Overview -->
        <button type="button" data-tab="overview"
                class="sd-tab-button <?php echo $active_tab === 'overview' ? 'sd-tab-button--active' : ''; ?>"
                id="tab-overview" role="tab" aria-selected="<?php echo $active_tab === 'overview' ? 'true' : 'false'; ?>">
          <span>Overview</span>
        </button>

        <!-- Heading: Checkout Configurations (not a link) -->
        <div class="sd-tab-heading">Checkout Configurations</div>

        <!-- General-->
        <button type="button" data-tab="general"
                class="sd-tab-button <?php echo $active_tab === 'general' ? 'sd-tab-button--active' : ''; ?>"
                id="tab-general" role="tab" aria-selected="<?php echo $active_tab === 'general' ? 'true' : 'false'; ?>">
          <span>General</span>
        </button>

        <!-- Delivery -->
        <button type="button" data-tab="delivery"
                class="sd-tab-button <?php echo $active_tab === 'delivery' ? 'sd-tab-button--active' : ''; ?>"
                id="tab-delivery" role="tab" aria-selected="<?php echo $active_tab === 'delivery' ? 'true' : 'false'; ?>">
          <span>Delivery</span>
        </button>

        <!-- Pickup -->
        <button type="button" data-tab="pickup"
                class="sd-tab-button <?php echo $active_tab === 'pickup' ? 'sd-tab-button--active' : ''; ?>"
                id="tab-pickup" role="tab" aria-selected="<?php echo $active_tab === 'pickup' ? 'true' : 'false'; ?>">
          <span>Pickup</span>
        </button>

        <!-- Heading: Order Fulfillment (not a link) -->
        <div class="sd-tab-heading">Order Fulfillment</div>

        <!-- Shipday Connect -->
        <button type="button" data-tab="shipday-connect"
                class="sd-tab-button <?php echo $active_tab === 'shipday-connect' ? 'sd-tab-button--active' : ''; ?>"
                id="tab-shipday-connect" role="tab" aria-selected="<?php echo $active_tab === 'shipday-connect' ? 'true' : 'false'; ?>">
          <span>Connect Shipday Account</span>
        </button>

        <!-- Rest API (keep your existing multi-vendor check) -->
          <?php if ( !is_plugin_active( 'dokan-lite/dokan.php' ) && !is_plugin_active( 'wc-multivendor-marketplace/wc-multivendor-marketplace.php' )) { ?>
            <button type="button" data-tab="rest-api"
                    class="sd-tab-button <?php echo $active_tab === 'rest-api' ? 'sd-tab-button--active' : ''; ?>"
                    id="tab-rest-api" role="tab" aria-selected="<?php echo $active_tab === 'rest-api' ? 'true' : 'false'; ?>">
              <span>Rest API</span>
            </button>
          <?php } ?>



      </div>
    </div>

    <!-- PANELS -->

    <!-- OVERVIEW -->
    <div data-tab-panel="overview" class="sd-tab-panel <?php echo $active_tab === 'overview' ? 'sd-tab-panel--active' : ''; ?>">
        <?php
        include plugin_dir_path( __FILE__ ) . 'tab-overview.php';
        ?>
    </div>

    <!-- SHIPDAY CONNECT (old general) -->
    <div data-tab-panel="shipday-connect" class="sd-tab-panel <?php echo $active_tab === 'shipday-connect' ? 'sd-tab-panel--active' : ''; ?>">
        <?php include plugin_dir_path( __FILE__ ) . 'tab-shipday-connect.php'; ?>
    </div>

    <!-- REST API (unchanged) -->
    <div data-tab-panel="rest-api" class="sd-tab-panel <?php echo $active_tab === 'rest-api' ? 'sd-tab-panel--active' : ''; ?>">
        <?php include plugin_dir_path( __FILE__ ) . 'tab-rest-api.php'; ?>
    </div>

    <!-- DELIVERY (unchanged) -->
    <div data-tab-panel="delivery" class="sd-tab-panel <?php echo $active_tab === 'delivery' ? 'sd-tab-panel--active' : ''; ?>">
        <?php include plugin_dir_path( __FILE__ ) . 'tab-delivery.php'; ?>
    </div>

    <!-- PICKUP (unchanged) -->
    <div data-tab-panel="pickup" class="sd-tab-panel <?php echo $active_tab === 'pickup' ? 'sd-tab-panel--active' : ''; ?>">
        <?php include plugin_dir_path( __FILE__ ) . 'tab-pickup.php'; ?>
    </div>

    <!-- General Settings (new) -->
    <div data-tab-panel="general" class="sd-tab-panel <?php echo $active_tab === 'general' ? 'sd-tab-panel--active' : ''; ?>">
        <?php include plugin_dir_path( __FILE__ ) . 'tab-general.php'; ?>
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

