(function ($) {
  const tabButtons = document.querySelectorAll('button[data-tab]');
  const tabPanels = document.querySelectorAll('[data-tab-panel]');

  function setActiveTab(tabName) {
    // Toggle panels via class
    tabPanels.forEach(panel => {
      const isActive = panel.getAttribute('data-tab-panel') === tabName;
      panel.classList.toggle('sd-tab-panel--active', isActive);
    });

    // Toggle buttons via class
    tabButtons.forEach(btn => {
      const isActive = btn.getAttribute('data-tab') === tabName;
      btn.classList.toggle('sd-tab-button--active', isActive);
    });

    let url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url.toString());
  }

  tabButtons.forEach(btn => {
    btn.addEventListener('click', function () {
      const tabName = this.getAttribute('data-tab');
      setActiveTab(tabName);
    });
  });

  // ---- Save button enable/disable logic ----
  function setSaveEnabled(saveEl, enabled) {
    if (!saveEl) return;
    if (enabled) {
      saveEl.setAttribute('data-state', 'Enabled');
      saveEl.classList.remove('sd-save-button--disabled');
      saveEl.classList.add('sd-save-button--enabled');
    } else {
      saveEl.setAttribute('data-state', 'Disabled');
      saveEl.classList.remove('sd-save-button--enabled');
      saveEl.classList.add('sd-save-button--disabled');
    }
  }

  // Attach listeners per panel
  tabPanels.forEach(panel => {
    const tabName = panel.getAttribute('data-tab-panel');
    const saveEl = panel.querySelector('[data-save]');
    const formControls = panel.querySelectorAll('input, select, textarea');

    setSaveEnabled(saveEl, false);

    formControls.forEach(control => {
      const enableSave = () => {
        setSaveEnabled(saveEl, true);
      };

      control.addEventListener('input', enableSave);
      control.addEventListener('change', enableSave);
    });

    if (saveEl) {
      saveEl.addEventListener('click', () => {
        if (saveEl.getAttribute('data-state') !== 'Enabled') return;

        if(tabName === 'general') {
          saveGeneralSettings();
        }
        else if(tabName === 'shipday-connect') {
          saveShipdayConnectSettings();
        } else if(tabName === 'rest-api') {
          saveRestApiSettings();
        }else if (tabName === 'delivery') {
          saveDeliverySettings();
        }else if (tabName === 'pickup') {
          savePickupSettings();
        }
        setSaveEnabled(saveEl, false);
      });
    }
  });
  // Initial tab
  //setActiveTab('general');

  function saveGeneralSettings() {
    const $form = jQuery('#shipday-general-settings-form');
    let formData = $form.serialize();
    jQuery.ajax({
      url: shipday_ajax_obj.shipday_ajax_url,
      type: 'post',
      data: {
        _ajax_nonce: shipday_ajax_obj.nonce,
        action: 'shipday_general_settings_save',
        formData: formData
      },
      success: function (response) {

        let $notice = jQuery('.shipday-general-notice');

        $notice.show('slide', {
          direction: 'right'
        });
        setTimeout(function() {
          $notice.hide('slide', {
            direction: 'right'
          });
          window.location.reload();
        }, 2000);

      }
    });
  }


  function saveShipdayConnectSettings() {
    const $form = jQuery('#shipday-connect-settings-form');
    let formData = $form.serialize();
    jQuery.ajax({
      url: shipday_ajax_obj.shipday_ajax_url,
      type: 'post',
      data: {
        _ajax_nonce: shipday_ajax_obj.nonce,
        action: 'shipday_connect_settings_save',
        formData: formData
      },
      success: function (response) {
        let $notice = jQuery('.shipday-connect-notice');

        $notice.show('slide', {
          direction: 'right'
        });
        setTimeout(function() {
          $notice.hide('slide', {
            direction: 'right'
          });
          window.location.reload();
        }, 4000);

      }
    });
  }

  function saveRestApiSettings() {
    const $form = jQuery('#shipday-rest-api-settings-form');
    let formData = $form.serialize();
    jQuery.ajax({
      url: shipday_ajax_obj.shipday_ajax_url,
      type: 'post',
      data: {
        _ajax_nonce: shipday_ajax_obj.nonce,
        action: 'shipday_rest_api_settings_save',
        formData: formData
      },
      success: function (response) {
        console.log('General settings saved:', response);
        let $notice = jQuery('.shipday-rest-api-notice');

        $notice.show('slide', {
          direction: 'right'
        });
        setTimeout(function() {
          $notice.hide('slide', {
            direction: 'right'
          });
          window.location.reload();
        }, 4000);

      }
    });
  }


  function saveDeliverySettings() {
    const $form = jQuery('#shipday-delivery-settings-form');
    let formData = $form.serialize();
    let $notice = jQuery('.shipday-delivery-notice');
    jQuery.ajax({
      url: shipday_ajax_obj.shipday_ajax_url,
      type: 'post',
      data: {
        _ajax_nonce: shipday_ajax_obj.nonce,
        action: 'shipday_delivery_settings_save',
        formData: formData
      },
      success: function (response) {
        if (response && response.success === false) {
          window.alert(response.data && response.data.message ? response.data.message : 'Unable to save delivery settings.');
          return;
        }

        $notice.show('slide', {
          direction: 'right'
        });
        setTimeout(function() {
          $notice.hide('slide', {
            direction: 'right'
          });
        }, 4000);

      },
      error: function (xhr) {
        const message =
          xhr &&
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
            ? xhr.responseJSON.data.message
            : 'Unable to save delivery settings.';

        window.alert(message);
      }
    });
  }

  function savePickupSettings() {
    const $form = jQuery('#shipday-pickup-settings-form');
    let formData = $form.serialize();
    let $notice = jQuery('.shipday-pickup-notice');
    jQuery.ajax({
      url: shipday_ajax_obj.shipday_ajax_url,
      type: 'post',
      data: {
        _ajax_nonce: shipday_ajax_obj.nonce,
        action: 'shipday_pickup_settings_save',
        formData: formData
      },
      success: function (response) {
        if (response && response.success === false) {
          window.alert(response.data && response.data.message ? response.data.message : 'Unable to save pickup settings.');
          return;
        }

        $notice.show('slide', {
          direction: 'right'
        });
        setTimeout(function() {
          $notice.hide('slide', {
            direction: 'right'
          });
        }, 4000);

      },
      error: function (xhr) {
        const message =
          xhr &&
          xhr.responseJSON &&
          xhr.responseJSON.data &&
          xhr.responseJSON.data.message
            ? xhr.responseJSON.data.message
            : 'Unable to save pickup settings.';

        window.alert(message);
      }
    });
  }

})();
