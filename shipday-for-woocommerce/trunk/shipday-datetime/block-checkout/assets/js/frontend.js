const { __ } = wp.i18n;
const React = window.React || wp.element;

const SHIPDAY_MOUNT_ID = "shipday-woo-delivery-block-mount";
const SHIPDAY_CHECKOUT_SELECTORS = [
  ".wp-block-woocommerce-checkout-contact-information-block",
  ".wc-block-components-checkout-step--contact-information .wc-block-components-checkout-step__container",
  ".wc-block-components-checkout-step--contact-information",
  ".wc-block-checkout__form",
];

const Shipday_Woo_Delivery = () => {
  const {
    CHECKOUT_STORE_KEY: checkoutStoreKey,
    CART_STORE_KEY: cartStoreKey,
    validationStore,
  } = wc.wcBlocksData;

  const { useSelect, dispatch } = wp.data;
  const { clearValidationError, setValidationErrors } = dispatch(validationStore);
  const { getValidationError } = useSelect((store) => store(validationStore));

  const cartExtensions = useSelect((store) => {
    try {
      return store(cartStoreKey).getCartData()?.extensions;
    } catch (error) {
      return null;
    }
  }, []);

  const shipdaySettings = cartExtensions?.shipday_woo_delivery || {};

  const [shipdayOrderType, setShipdayOrderType] = React.useState(
    shipdaySettings.shipday_order_type
  );
  const [shipdayDeliveryDate, setShipdayDeliveryDate] = React.useState(
    shipdaySettings.shipday_delivery_date
  );
  const [shipdayDeliveryTime, setShipdayDeliveryTime] = React.useState(
    shipdaySettings.shipday_delivery_time
  );
  const [shipdayPickupDate, setShipdayPickupDate] = React.useState(
    shipdaySettings.shipday_pickup_date
  );
  const [pickupTime, setPickupTime] = React.useState(shipdaySettings.pickup_time);
  const [isProcessing, setIsProcessing] = React.useState(false);

  const isBeforeProcessing = useSelect((store) =>
    store(checkoutStoreKey).isBeforeProcessing()
  );

  const validateField = (value, fieldType) => {
    let errorKey = `shipday_woo_${fieldType}_error`;
    let errorMessage = __("This field is mandatory", "shipday-for-woocommerce");
    let isRequired = false;

    if (fieldType === "shipday_order_type") {
      errorMessage = __("Order type is required", "shipday-for-woocommerce");
      isRequired = shipdaySettings.enable_delivery_option;
      errorKey = "shipday_woo_order_type_error";
    } else if (fieldType === "shipday_delivery_date") {
      errorMessage = __("Delivery date is required", "shipday-for-woocommerce");
      errorKey = "shipday_woo_delivery_date_error";
      isRequired =
        shipdaySettings.enable_delivery_date &&
        shipdaySettings.delivery_date_mandatory;
    } else if (fieldType === "shipday_delivery_time") {
      errorMessage = __("Delivery time is mandatory", "shipday-for-woocommerce");
      errorKey = "shipday_woo_delivery_time_error";
      isRequired =
        shipdaySettings.enable_delivery_time &&
        shipdaySettings.delivery_time_mandatory;
    } else if (fieldType === "shipday_pickup_date") {
      errorMessage = __("Pickup date is mandatory", "shipday-for-woocommerce");
      errorKey = "shipday_woo_pickup_date_error";
      isRequired =
        shipdaySettings.enable_pickup_date && shipdaySettings.pickup_date_mandatory;
    } else if (fieldType === "pickup_time") {
      errorMessage = __("Pickup time is mandatory", "shipday-for-woocommerce");
      errorKey = "shipday_woo_pickup_time_error";
      isRequired =
        shipdaySettings.enable_pickup_time && shipdaySettings.pickup_time_mandatory;
    }

    if ((!value || value === "") && isRequired) {
      setValidationErrors({
        [errorKey]: {
          message: errorMessage,
          hidden: false,
        },
      });
      return false;
    }

    clearValidationError(errorKey);

    if (fieldType === "shipday_order_type") {
      clearValidationError("shipday_woo_delivery_date_error");
      clearValidationError("shipday_woo_delivery_time_error");
      clearValidationError("shipday_woo_pickup_date_error");
      clearValidationError("shipday_woo_pickup_time_error");
    }

    return true;
  };

  React.useEffect(() => {
    if (!isBeforeProcessing || !shipdaySettings.enable_datetime_plugin) {
      return;
    }

    if (shipdaySettings.enable_delivery_option) {
      validateField(shipdayOrderType, "shipday_order_type");
    }

    if (shipdaySettings.enable_delivery_option) {
      if (shipdayOrderType === "Delivery") {
        validateField(shipdayDeliveryDate, "shipday_delivery_date");
        validateField(shipdayDeliveryTime, "shipday_delivery_time");
      } else if (shipdayOrderType === "Pickup") {
        validateField(shipdayPickupDate, "shipday_pickup_date");
        validateField(pickupTime, "pickup_time");
      }
    } else {
      if (shipdaySettings.enable_delivery_date) {
        validateField(shipdayDeliveryDate, "shipday_delivery_date");
      }
      if (shipdaySettings.enable_delivery_time) {
        validateField(shipdayDeliveryTime, "shipday_delivery_time");
      }
      if (shipdaySettings.enable_pickup_date) {
        validateField(shipdayPickupDate, "shipday_pickup_date");
      }
      if (shipdaySettings.enable_pickup_time) {
        validateField(pickupTime, "pickup_time");
      }
    }
  }, [
    isBeforeProcessing,
    shipdaySettings.enable_datetime_plugin,
    shipdaySettings.enable_delivery_option,
    shipdaySettings.enable_delivery_date,
    shipdaySettings.enable_delivery_time,
    shipdaySettings.enable_pickup_date,
    shipdaySettings.enable_pickup_time,
    shipdaySettings.delivery_date_mandatory,
    shipdaySettings.delivery_time_mandatory,
    shipdaySettings.pickup_date_mandatory,
    shipdaySettings.pickup_time_mandatory,
    shipdayOrderType,
    shipdayDeliveryDate,
    shipdayDeliveryTime,
    shipdayPickupDate,
    pickupTime,
  ]);

  React.useEffect(() => {
    setShipdayOrderType(shipdaySettings.shipday_order_type);
  }, [shipdaySettings.shipday_order_type]);

  React.useEffect(() => {
    setShipdayDeliveryDate(shipdaySettings.shipday_delivery_date);
  }, [shipdaySettings.shipday_delivery_date]);

  React.useEffect(() => {
    setShipdayDeliveryTime(shipdaySettings.shipday_delivery_time);
  }, [shipdaySettings.shipday_delivery_time]);

  React.useEffect(() => {
    setShipdayPickupDate(shipdaySettings.shipday_pickup_date);
  }, [shipdaySettings.shipday_pickup_date]);

  React.useEffect(() => {
    setPickupTime(shipdaySettings.pickup_time);
  }, [shipdaySettings.pickup_time]);

  const updateCheckoutSession = (namespace, data) => {
    if (!window.wc?.blocksCheckout?.extensionCartUpdate) {
      return Promise.resolve();
    }

    setIsProcessing(true);

    return window.wc.blocksCheckout
      .extensionCartUpdate({
        namespace,
        data,
      })
      .finally(() => setIsProcessing(false));
  };

  const handleOrderTypeChange = (event) => {
    const value = event.target.value;
    if (value === "") {
      event.target.classList.add("shipday-select-placeholder");
    } else {
      event.target.classList.remove("shipday-select-placeholder");
    }
    setShipdayOrderType(value);
    validateField(value, "shipday_order_type");
    updateCheckoutSession("shipday_woo_delivery_order_type_change", {
      shipday_order_type: value,
    });
  };

  const handleDeliveryDateChange = (value) => {
    setShipdayDeliveryDate(value);
    validateField(value, "shipday_delivery_date");
    updateCheckoutSession("shipday_woo_delivery_delivery_date_change", {
      shipday_delivery_date: value,
    });
  };

  const handlePickupDateChange = (value) => {
    setShipdayPickupDate(value);
    validateField(value, "shipday_pickup_date");
    updateCheckoutSession("shipday_woo_delivery_pickup_date_change", {
      shipday_pickup_date: value,
    });
  };

  const handleDeliveryTimeChange = (event) => {
    const value = event.target.value;
    if (value === "") {
      event.target.classList.add("shipday-select-placeholder");
    } else {
      event.target.classList.remove("shipday-select-placeholder");
    }
    setShipdayDeliveryTime(value);
    validateField(value, "shipday_delivery_time");
    updateCheckoutSession("shipday_woo_delivery_delivery_time_change", {
      shipday_delivery_time: value,
    });
  };

  const handlePickupTimeChange = (event) => {
    const value = event.target.value;
    if (value === "") {
      event.target.classList.add("shipday-select-placeholder");
    } else {
      event.target.classList.remove("shipday-select-placeholder");
    }
    setPickupTime(value);
    validateField(value, "pickup_time");
    updateCheckoutSession("shipday_woo_delivery_pickup_time_change", {
      pickup_time: value,
    });
  };

  if (!shipdaySettings.enable_datetime_plugin) {
    return null;
  }

  return React.createElement(
    "div",
    {
      className: `shipday-woo-delivery-block-container${
        isProcessing ? " processing" : ""
      }`,
    },
    React.createElement(
      "legend",
      {
        className: "screen-reader-text",
      },
      shipdaySettings.delivery_option_field_label
    ),
    React.createElement(
      "div",
      {
        className: "wc-block-components-checkout-step__heading",
      },
      React.createElement(
        "h2",
        {
          className: "wc-block-components-title wc-block-components-checkout-step__title",
          ariaHidden: true,
        },
        shipdaySettings.delivery_heading_checkout
      )
    ),
    shipdaySettings.enable_delivery_option &&
      React.createElement(Shipday_Woo_Order_Type, {
        shipdaySettings,
        shipdayOrderType,
        handleOrderTypeChange,
        getValidationError,
      }),
    shipdaySettings.enable_delivery_date &&
      (!shipdaySettings.enable_delivery_option ||
        shipdayOrderType === "Delivery") &&
      React.createElement(Shipday_Woo_Delivery_Date, {
        shipdaySettings,
        handleDeliveryDateChange,
        getValidationError,
      }),
    shipdaySettings.enable_delivery_time &&
      (!shipdaySettings.enable_delivery_option ||
        shipdayOrderType === "Delivery") &&
      React.createElement(Shipday_Woo_Delivery_Time, {
        shipdaySettings,
        shipdayDeliveryTime,
        handleDeliveryTimeChange,
        getValidationError,
      }),
    shipdaySettings.enable_pickup_date &&
      (!shipdaySettings.enable_delivery_option ||
        shipdayOrderType === "Pickup") &&
      React.createElement(Shipday_Woo_Pickup_Date, {
        shipdaySettings,
        handlePickupDateChange,
        getValidationError,
      }),
    shipdaySettings.enable_pickup_time &&
      (!shipdaySettings.enable_delivery_option ||
        shipdayOrderType === "Pickup") &&
      React.createElement(Shipday_Woo_Pickup_Time, {
        shipdaySettings,
        handlePickupTimeChange,
        pickupTime,
        getValidationError,
      })
  );
};

const shipdayFormattedDate = (date) => {
  const year = date.getFullYear();
  const month = `0${date.getMonth() + 1}`.slice(-2);
  const day = `0${date.getDate()}`.slice(-2);
  return `${year}-${month}-${day}`;
};

const shipdayEnableDates = (numberOfDays, startDate, disabledWeekDays, disabledDates) => {
  const totalDays = parseInt(numberOfDays, 10);
  const enabledDates = [];
  const currentDate = new Date(startDate);

  while (enabledDates.length < totalDays) {
    const formattedCurrentDate = shipdayFormattedDate(currentDate);
    if (
      !disabledWeekDays.includes(currentDate.getDay().toString()) &&
      !disabledDates.includes(formattedCurrentDate)
    ) {
      enabledDates.push(formattedCurrentDate);
    }
    currentDate.setDate(currentDate.getDate() + 1);
  }

  return enabledDates;
};

const Shipday_Woo_Order_Type = ({
  shipdaySettings,
  shipdayOrderType,
  handleOrderTypeChange,
  getValidationError,
}) => {
  const deliveryOptions = shipdaySettings.delivery_options || {};
  const validationError = getValidationError("shipday_woo_order_type_error");

  const renderOptions = () =>
    Object.entries(deliveryOptions).map(([value, label]) =>
      React.createElement(
        "option",
        {
          key: value,
          value,
          selected: shipdayOrderType === value,
        },
        label
      )
    );

  return React.createElement(
    "div",
    {
      className: `shipday-woo-order-type-container${
        validationError ? " has-error" : ""
      }`,
    },
    React.createElement(
      "div",
      {
        className: `wc-blocks-components-select shipday-woo-delivery-select${
          shipdayOrderType ? "" : " not-selected"
        }`,
      },
      React.createElement(
        "div",
        {
          className: "wc-blocks-components-select__container",
        },
        React.createElement(
          "label",
          {
            htmlFor: "shipday_woo_order_type",
            className: "wc-blocks-components-select__label",
          },
          shipdaySettings.delivery_option_field_label
        ),
        React.createElement(
          "select",
          {
            size: "1",
            name: "shipday_woo_order_type",
            className: "wc-blocks-components-select__select shipday-select-placeholder",
            id: "shipday_woo_order_type",
            "aria-label": shipdaySettings.delivery_option_field_label,
            "aria-invalid": validationError ? "true" : "false",
            onChange: handleOrderTypeChange,
            required: shipdaySettings.enable_delivery_option,
          },
          React.createElement(
            "option",
            {
              value: "",
              className: "shipday-select-placeholder",
            },
            shipdaySettings.select_order_type_text
          ),
          renderOptions()
        ),
        React.createElement(
          "svg",
          {
            viewBox: "0 0 24 24",
            xmlns: "http://www.w3.org/2000/svg",
            width: "24",
            height: "24",
            className: "wc-blocks-components-select__expand",
            "aria-hidden": "true",
            focusable: "false",
          },
          React.createElement("path", {
            d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z",
          })
        )
      )
    ),
    validationError &&
      React.createElement(
        "div",
        {
          className: "wc-block-components-validation-error",
          role: "alert",
        },
        React.createElement("p", {}, validationError.message)
      )
  );
};

const Shipday_Woo_Delivery_Date = ({
  shipdaySettings,
  handleDeliveryDateChange,
  getValidationError,
}) => {
  const datePickerRef = React.useRef(null);
  const [isActive, setIsActive] = React.useState(
    !!shipdaySettings.shipday_delivery_date
  );
  let isInitializing = false;

  const validationError = getValidationError("shipday_woo_delivery_date_error");
  const disabledWeekDays = shipdaySettings.delivery_disable_week_days || [];
  const enabledDates = shipdayEnableDates(
    shipdaySettings.delivery_date_selectable_days,
    shipdaySettings.today,
    disabledWeekDays,
    []
  );

  React.useEffect(() => {
    const flatpickrInstance = flatpickr(datePickerRef.current, {
      defaultDate: shipdaySettings.shipday_delivery_date,
      enable: enabledDates,
      minDate: shipdaySettings.today,
      dateFormat: "Y-m-d",
      altInput: true,
      altFormat: shipdaySettings.delivery_date_format,
      locale: {
        firstDayOfWeek: shipdaySettings.week_starts_from,
      },
      onReady(selectedDates, dateStr, instance) {
        const expectedValue =
          shipdaySettings.shipday_delivery_date !== null
            ? shipdaySettings.shipday_delivery_date
            : "";
        if (dateStr !== expectedValue) {
          setIsActive(false);
          isInitializing = true;
          instance.clear();
          isInitializing = false;
        }
      },
      onChange(selectedDates, dateStr) {
        if (!isInitializing) {
          handleDeliveryDateChange(dateStr);
        }
      },
      onOpen(selectedDates, dateStr) {
        setIsActive(dateStr !== "");
      },
      onClose(selectedDates, dateStr) {
        setIsActive(dateStr !== "");
      },
    });

    return () => {
      if (flatpickrInstance) {
        flatpickrInstance.destroy();
      }
    };
  }, [shipdaySettings]);

  return React.createElement(
    "div",
    {
      className: `shipday-woo-delivery-date-container${
        validationError ? " has-error" : ""
      }`,
    },
    React.createElement(
      "div",
      {
        className: `wc-block-components-text-input shipday-woo-delivery-text-input${
          isActive ? " is-active" : ""
        }`,
      },
      React.createElement("input", {
        ref: datePickerRef,
        type: "text",
        name: "shipday_woo_delivery_date",
        id: "shipday_woo_delivery_date",
        "aria-label": shipdaySettings.delivery_date_field_label,
        "aria-invalid": validationError ? "true" : "false",
        required: shipdaySettings.delivery_date_mandatory,
      }),
      React.createElement(
        "label",
        {
          className: "shipday-woo-delivery-date-label",
          htmlFor: "shipday_woo_delivery_date",
        },
        shipdaySettings.delivery_date_field_label
      ),
      React.createElement(
        "span",
        {
          className: "shipday-woo-delivery-date-icon",
          "aria-hidden": "true",
          onClick: () => {
            if (datePickerRef.current && datePickerRef.current._flatpickr) {
              datePickerRef.current._flatpickr.open();
            }
          },
        },
        React.createElement("span", {
          className: "dashicons dashicons-calendar-alt",
        })
      )
    ),
    validationError &&
      React.createElement(
        "div",
        {
          className: "wc-block-components-validation-error",
          role: "alert",
        },
        React.createElement("p", {}, validationError.message)
      )
  );
};

const Shipday_Woo_Delivery_Time = ({
  shipdaySettings,
  shipdayDeliveryTime,
  handleDeliveryTimeChange,
  getValidationError,
}) => {
  const selectRef = React.useRef(null);
  const timeOptions = shipdaySettings.delivery_time_options || {};
  const validationError = getValidationError("shipday_woo_delivery_time_error");

  React.useEffect(() => {
    if (!shipdayDeliveryTime && selectRef.current) {
      selectRef.current.selectedIndex = 0;
    }
  });

  const renderTimeOptions = () =>
    Object.entries(timeOptions).map(([value, option]) =>
      React.createElement(
        "option",
        {
          key: value,
          value,
          selected: shipdayDeliveryTime === value && !option.disabled,
          disabled: option.disabled,
        },
        option.title
      )
    );

  return React.createElement(
    "div",
    {
      className: `shipday-woo-delivery-time-container${
        validationError ? " has-error" : ""
      }`,
    },
    React.createElement(
      "div",
      {
        className: `wc-blocks-components-select shipday-woo-delivery-select${
          shipdayDeliveryTime ? "" : " not-selected"
        }`,
      },
      React.createElement(
        "div",
        {
          className: "wc-blocks-components-select__container",
        },
        React.createElement(
          "label",
          {
            htmlFor: "shipday_woo_delivery_time",
            className: "wc-blocks-components-select__label",
          },
          shipdaySettings.delivery_time_field_label
        ),
        React.createElement(
          "select",
          {
            ref: selectRef,
            size: "1",
            name: "shipday_woo_delivery_time",
            className: "wc-blocks-components-select__select shipday-select-placeholder",
            id: "shipday_woo_delivery_time",
            "aria-label": shipdaySettings.delivery_time_field_label,
            "aria-invalid": validationError ? "true" : "false",
            onChange: handleDeliveryTimeChange,
            required: shipdaySettings.delivery_time_mandatory,
          },
          React.createElement(
            "option",
            {
              value: "",
            },
            shipdaySettings.select_delivery_time_text
          ),
          renderTimeOptions()
        ),
        React.createElement(
          "svg",
          {
            viewBox: "0 0 24 24",
            xmlns: "http://www.w3.org/2000/svg",
            width: "24",
            height: "24",
            className: "wc-blocks-components-select__expand",
            "aria-hidden": "true",
            focusable: "false",
          },
          React.createElement("path", {
            d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z",
          })
        )
      )
    ),
    validationError &&
      React.createElement(
        "div",
        {
          className: "wc-block-components-validation-error",
          role: "alert",
        },
        React.createElement("p", {}, validationError.message)
      )
  );
};

const Shipday_Woo_Pickup_Time = ({
  shipdaySettings,
  handlePickupTimeChange,
  pickupTime,
  getValidationError,
}) => {
  const selectRef = React.useRef(null);
  const timeOptions = shipdaySettings.pickup_time_options || {};
  const validationError = getValidationError("shipday_woo_pickup_time_error");

  React.useEffect(() => {
    if (!pickupTime && selectRef.current) {
      selectRef.current.selectedIndex = 0;
    }
  });

  const renderTimeOptions = () =>
    Object.entries(timeOptions).map(([value, option]) =>
      React.createElement(
        "option",
        {
          key: value,
          value,
          selected: pickupTime === value && !option.disabled,
          disabled: option.disabled,
        },
        option.title
      )
    );

  return React.createElement(
    "div",
    {
      className: `shipday-woo-pickup-time-container${
        validationError ? " has-error" : ""
      }`,
    },
    React.createElement(
      "div",
      {
        className: `wc-blocks-components-select shipday-woo-delivery-select${
          pickupTime ? "" : " not-selected"
        }`,
      },
      React.createElement(
        "div",
        {
          className: "wc-blocks-components-select__container",
        },
        React.createElement(
          "label",
          {
            htmlFor: "shipday_woo_pickup_time",
            className: "wc-blocks-components-select__label",
          },
          __("Pickup time", "shipday-for-woocommerce")
        ),
        React.createElement(
          "select",
          {
            ref: selectRef,
            size: "1",
            name: "shipday_woo_pickup_time",
            className: "wc-blocks-components-select__select shipday-select-placeholder",
            id: "shipday_woo_pickup_time",
            "aria-label": "Shipday-pickup-time",
            "aria-invalid": validationError ? "true" : "false",
            onChange: handlePickupTimeChange,
            required: shipdaySettings.pickup_time_mandatory,
          },
          React.createElement(
            "option",
            {
              value: "",
            },
            __("Select pickup slot", "shipday-for-woocommerce")
          ),
          renderTimeOptions()
        ),
        React.createElement(
          "svg",
          {
            viewBox: "0 0 24 24",
            xmlns: "http://www.w3.org/2000/svg",
            width: "24",
            height: "24",
            className: "wc-blocks-components-select__expand",
            "aria-hidden": "true",
            focusable: "false",
          },
          React.createElement("path", {
            d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z",
          })
        )
      )
    ),
    validationError &&
      React.createElement(
        "div",
        {
          className: "wc-block-components-validation-error",
          role: "alert",
        },
        React.createElement("p", {}, validationError.message)
      )
  );
};

const Shipday_Woo_Pickup_Date = ({
  shipdaySettings,
  handlePickupDateChange,
  getValidationError,
}) => {
  const pickupDatePickerRef = React.useRef(null);
  const [isActive, setIsActive] = React.useState(
    !!shipdaySettings.shipday_pickup_date
  );
  let isInitializing = false;

  const validationError = getValidationError("shipday_woo_pickup_date_error");
  const disabledWeekDays = shipdaySettings.pickup_disable_week_days || [];
  const enabledDates = shipdayEnableDates(
    shipdaySettings.pickup_date_selectable_days,
    shipdaySettings.today,
    disabledWeekDays,
    []
  );

  React.useEffect(() => {
    const flatpickrInstance = flatpickr(pickupDatePickerRef.current, {
      defaultDate: shipdaySettings.shipday_pickup_date,
      enable: enabledDates,
      dateFormat: "Y-m-d",
      altInput: true,
      locale: {
        firstDayOfWeek: shipdaySettings.week_starts_from,
      },
      onReady(selectedDates, dateStr, instance) {
        const expectedValue =
          shipdaySettings.shipday_pickup_date !== null
            ? shipdaySettings.shipday_pickup_date
            : "";
        if (dateStr !== expectedValue) {
          setIsActive(false);
          isInitializing = true;
          instance.clear();
          isInitializing = false;
        }
      },
      onChange(selectedDates, dateStr) {
        if (!isInitializing) {
          handlePickupDateChange(dateStr);
        }
      },
      onOpen(selectedDates, dateStr) {
        setIsActive(dateStr !== "");
      },
      onClose(selectedDates, dateStr) {
        setIsActive(dateStr !== "");
      },
    });

    return () => {
      if (flatpickrInstance) {
        flatpickrInstance.destroy();
      }
    };
  }, [shipdaySettings]);

  return React.createElement(
    "div",
    {
      className: `shipday-woo-delivery-date-container${
        validationError ? " has-error" : ""
      }`,
    },
    React.createElement(
      "div",
      {
        className: `wc-block-components-text-input shipday-woo-delivery-text-input${
          isActive ? " is-active" : ""
        }`,
      },
      React.createElement("input", {
        ref: pickupDatePickerRef,
        type: "text",
        name: "shipday_woo_pickup_date",
        id: "shipday_woo_pickup_date",
        "aria-label": shipdaySettings.pickup_date_field_label,
        "aria-invalid": validationError ? "true" : "false",
        required: shipdaySettings.pickup_date_mandatory,
      }),
      React.createElement(
        "label",
        {
          className: "shipday-woo-delivery-date-label",
          htmlFor: "shipday_woo_pickup_date",
        },
        shipdaySettings.pickup_date_field_label
      ),
      React.createElement(
        "span",
        {
          className: "shipday-woo-delivery-date-icon",
          "aria-hidden": "true",
          onClick: () => {
            if (
              pickupDatePickerRef.current &&
              pickupDatePickerRef.current._flatpickr
            ) {
              pickupDatePickerRef.current._flatpickr.open();
            }
          },
        },
        React.createElement("span", {
          className: "dashicons dashicons-calendar-alt",
        })
      )
    ),
    validationError &&
      React.createElement(
        "div",
        {
          className: "wc-block-components-validation-error",
          role: "alert",
        },
        React.createElement("p", {}, validationError.message)
      )
  );
};

const shipdayFindCheckoutTarget = () => {
  for (const selector of SHIPDAY_CHECKOUT_SELECTORS) {
    const match = document.querySelector(selector);
    if (match) {
      return match;
    }
  }

  return null;
};

const shipdayEnsureMountNode = (target) => {
  const existing = document.getElementById(SHIPDAY_MOUNT_ID);
  if (existing && target.contains(existing)) {
    return existing;
  }

  if (existing) {
    existing.remove();
  }

  const mountNode = document.createElement("div");
  mountNode.id = SHIPDAY_MOUNT_ID;
  target.appendChild(mountNode);
  return mountNode;
};

const shipdayRenderIntoMount = (mountNode) => {
  const element = React.createElement(Shipday_Woo_Delivery);

  if (typeof wp.element.createRoot === "function") {
    if (!mountNode.__shipdayRoot) {
      mountNode.__shipdayRoot = wp.element.createRoot(mountNode);
    }
    mountNode.__shipdayRoot.render(element);
    return;
  }

  if (typeof wp.element.render === "function") {
    wp.element.render(element, mountNode);
  }
};

const shipdayMountCheckoutFields = () => {
  if (
    !document.querySelector(".wc-block-checkout") &&
    !document.querySelector(".wp-block-woocommerce-checkout")
  ) {
    return false;
  }

  const target = shipdayFindCheckoutTarget();
  if (!target) {
    return false;
  }

  const mountNode = shipdayEnsureMountNode(target);
  shipdayRenderIntoMount(mountNode);
  return true;
};

const shipdayStartMountObserver = () => {
  const attemptMount = () => {
    shipdayMountCheckoutFields();
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", attemptMount, { once: true });
  } else {
    attemptMount();
  }

  const observer = new MutationObserver(() => {
    attemptMount();
  });

  if (document.body) {
    observer.observe(document.body, {
      childList: true,
      subtree: true,
    });
  }
};

shipdayStartMountObserver();
