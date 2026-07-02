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

  function syncDeliveryFeeDependentFields() {
    const deliveryToggle = document.getElementById('shipday_enable_delivery_fee');

    if (!deliveryToggle) {
      return;
    }

    const dependentFields = document.querySelectorAll('.shipday-delivery-fee-dependent');
    const dependentWrappers = document.querySelectorAll('.shipday-delivery-fee-dependent-field');
    const isEnabled =
      deliveryToggle.checked && deliveryToggle.dataset.shipdayPlanCheckPending !== 'true';

    dependentFields.forEach(field => {
      field.readOnly = !isEnabled;
      field.setAttribute('aria-readonly', String(!isEnabled));
    });

    dependentWrappers.forEach(wrapper => {
      wrapper.classList.toggle('shipday-general-setting--readonly', !isEnabled);
    });
  }

  function getAjaxErrorMessage(xhr, fallbackMessage) {
    if (
      xhr &&
      xhr.responseJSON &&
      xhr.responseJSON.data &&
      xhr.responseJSON.data.message
    ) {
      return xhr.responseJSON.data.message;
    }

    return fallbackMessage;
  }

  function showSlidingNotice($notice, message) {
    if (!$notice || !$notice.length) {
      return;
    }

    if (message) {
      const $message = $notice.find('.shipday-notice__message');

      if ($message.length) {
        $message.text(message);
      } else {
        $notice.text(message);
      }
    }

    const existingTimer = $notice.data('shipdayHideTimer');
    if (existingTimer) {
      window.clearTimeout(existingTimer);
    }

    $notice.stop(true, true).show('slide', {
      direction: 'right'
    });

    const hideTimer = window.setTimeout(() => {
      $notice.hide('slide', {
        direction: 'right'
      });
    }, 4000);

    $notice.data('shipdayHideTimer', hideTimer);
  }

  function hideDeliveryErrorNotice() {
    const $notice = jQuery('.shipday-delivery-error-notice');

    if (!$notice.length) {
      return;
    }

    $notice.stop(true, true).hide();
  }

  function showDeliveryErrorNotice(message) {
    const $notice = jQuery('.shipday-delivery-error-notice');

    if (!$notice.length) {
      return;
    }

    const $message = $notice.find('.shipday-notice__message');
    const noticeMessage =
      message || shipday_ajax_obj.delivery_fee_plan_required_message;

    if ($message.length) {
      $message.text(noticeMessage);
    } else {
      $notice.text(noticeMessage);
    }

    $notice.stop(true, true).show();
  }

  function showPickupErrorNotice(message) {
    const $notice = jQuery('.shipday-pickup-error-notice');

    if (!$notice.length) {
      return;
    }

    const $message = $notice.find('.shipday-notice__message');
    const noticeMessage = message || 'Unable to save pickup settings.';

    if ($message.length) {
      $message.text(noticeMessage);
    } else {
      $notice.text(noticeMessage);
    }

    $notice.stop(true, true).show();
  }

  function hidePickupErrorNotice() {
    const $notice = jQuery('.shipday-pickup-error-notice');

    if (!$notice.length) {
      return;
    }

    $notice.stop(true, true).hide();
  }

  function requestDeliveryFeeFeatureStatus() {
    return jQuery.ajax({
      url: shipday_ajax_obj.shipday_ajax_url,
      type: 'post',
      data: {
        _ajax_nonce: shipday_ajax_obj.nonce,
        action: 'shipday_delivery_fee_feature_status'
      }
    });
  }

  function handleDeliveryFeeToggleChange(event) {
    const deliveryFeeToggle = event.currentTarget;

    if (!deliveryFeeToggle.checked) {
      delete deliveryFeeToggle.dataset.shipdayPlanCheckPending;
      hideDeliveryErrorNotice();
      syncDeliveryFeeDependentFields();
      return;
    }

    deliveryFeeToggle.dataset.shipdayPlanCheckPending = 'true';
    deliveryFeeToggle.disabled = true;
    syncDeliveryFeeDependentFields();

    requestDeliveryFeeFeatureStatus()
      .done(response => {
        if (response && response.success) {
          delete deliveryFeeToggle.dataset.shipdayPlanCheckPending;
          hideDeliveryErrorNotice();
          syncDeliveryFeeDependentFields();
          return;
        }

        deliveryFeeToggle.checked = false;
        delete deliveryFeeToggle.dataset.shipdayPlanCheckPending;
        syncDeliveryFeeDependentFields();
        showDeliveryErrorNotice(
          response &&
            response.data &&
            response.data.message
            ? response.data.message
            : shipday_ajax_obj.delivery_fee_plan_required_message
        );
      })
      .fail(xhr => {
        deliveryFeeToggle.checked = false;
        delete deliveryFeeToggle.dataset.shipdayPlanCheckPending;
        syncDeliveryFeeDependentFields();
        showDeliveryErrorNotice(
          getAjaxErrorMessage(xhr, shipday_ajax_obj.delivery_fee_plan_required_message)
        );
      })
      .always(() => {
        deliveryFeeToggle.disabled = false;
      });
  }

  function initDeliveryFeeDependentFields() {
    const deliveryFeeToggle = document.getElementById('shipday_enable_delivery_fee');

    if (!deliveryFeeToggle || deliveryFeeToggle.dataset.shipdayReadonlyBound === 'true') {
      return;
    }

    syncDeliveryFeeDependentFields();
    hideDeliveryErrorNotice();
    deliveryFeeToggle.addEventListener('change', handleDeliveryFeeToggleChange);
    deliveryFeeToggle.dataset.shipdayReadonlyBound = 'true';
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initDeliveryFeeDependentFields);
  } else {
    initDeliveryFeeDependentFields();
  }

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
          showDeliveryErrorNotice(
            response.data && response.data.message
              ? response.data.message
              : 'Unable to save delivery settings.'
          );
          return;
        }

        hideDeliveryErrorNotice();
        showSlidingNotice($notice);

      },
      error: function (xhr) {
        showDeliveryErrorNotice(getAjaxErrorMessage(xhr, 'Unable to save delivery settings.'));
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
          showPickupErrorNotice(
            response.data && response.data.message
              ? response.data.message
              : 'Unable to save pickup settings.'
          );
          return;
        }

        hidePickupErrorNotice();
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
        showPickupErrorNotice(getAjaxErrorMessage(xhr, 'Unable to save pickup settings.'));
      }
    });
  }

})();
