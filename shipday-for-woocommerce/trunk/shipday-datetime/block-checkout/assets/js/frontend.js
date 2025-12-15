const Shipday_Woo_Delivery = ({
  extensions,
  checkoutExtensionData
}) => {
  const {
    CHECKOUT_STORE_KEY: checkoutStoreKey,
    CART_STORE_KEY: cartStoreKey,
    validationStore
  } = wc.wcBlocksData;

  const {
    useSelect,
    dispatch
  } = wp.data;

  const {
    clearValidationError,
    setValidationErrors
  } = dispatch(validationStore);

  const {
    getValidationError,
    hasValidationErrors
  } = useSelect(store => store(validationStore));

  const cartExtensions = useSelect(store => {
    try {
      return store(cartStoreKey).getCartData()?.extensions;
    } catch (error) {
      return null;
    }
  }, []);

  const shipdaySettings = extensions?.shipday_woo_delivery || cartExtensions?.shipday_woo_delivery || {};


  // State hooks
  const [shipdayOrderType, setShipdayOrderType] = React.useState(shipdaySettings.shipday_order_type);
  const [shipdayDeliveryDate, setShipdayDeliveryDate] = React.useState(shipdaySettings.shipday_delivery_date);
  const [shipdayDeliveryTime, setShipdayDeliveryTime] = React.useState(shipdaySettings.shipday_delivery_time);
  const [shipdayPickupDate, setShipdayPickupDate] = React.useState(shipdaySettings.shipday_pickup_date);
  const [pickupTime, setPickupTime] = React.useState(shipdaySettings.pickup_time);
  const [isProcessing, setIsProcessing] = React.useState(false);

  const isBeforeProcessing = useSelect(store => store(checkoutStoreKey).isBeforeProcessing());

  // Validation function
  const validateField = (value, fieldType) => {
    let errorKey = "shipday_woo_" + fieldType + "_error";
    let errorMessage = "This field is mandatory";
    let isRequired = false;

    // Determine error message and required status based on field type
    if (fieldType === "shipday_order_type") {
      errorMessage = "Order type is required";
      isRequired = shipdaySettings.enable_delivery_option;
      errorKey = "shipday_woo_order_type_error";
    } else if (fieldType === "shipday_delivery_date") {
      errorMessage = "Delivery date is required";
      errorKey = "shipday_woo_delivery_date_error";
      isRequired = shipdaySettings.enable_delivery_date && shipdaySettings.delivery_date_mandatory;
    } else if (fieldType === "shipday_delivery_time") {
      errorMessage = "Delivery time is mandatory";
      errorKey = "shipday_woo_delivery_time_error";
      isRequired = shipdaySettings.enable_delivery_time && shipdaySettings.delivery_time_mandatory;
    } else if (fieldType === "shipday_pickup_date") {
      errorMessage = "Pickup date is mandatory";
      isRequired = shipdaySettings.enable_pickup_date && shipdaySettings.pickup_date_mandatory;
    }else if (fieldType === "pickup_time") {
      errorMessage = "Pickup time is mandatory";
      isRequired = shipdaySettings.enable_pickup_time && shipdaySettings.pickup_time_mandatory;
    }

    // If value is empty and field is required, show error
    if ((!value || value==='') && isRequired) {
      setValidationErrors({
        [errorKey]: {
          message: errorMessage,
          hidden: false
        }
      });
      return false;
    }

    // Clear error
    clearValidationError(errorKey);

    // Special handling for order type changes
    if (fieldType === "shipday_order_type") {
      clearValidationError("shipday_woo_delivery_date_error");
      clearValidationError("shipday_woo_delivery_time_error");
      clearValidationError("shipday_woo_pickup_date_error");
      clearValidationError("shipday_woo_pickup_time_error");
    }

    return true;
  };

  // Validate fields before processing checkout
  React.useEffect(() => {
    if (isBeforeProcessing && shipdaySettings.enable_datetime_plugin) {
      if (shipdaySettings.enable_delivery_option) {
        validateField(shipdayOrderType, "shipday_order_type");
      }

      if (shipdaySettings.enable_delivery_option) {
        if (shipdaySettings.shipday_order_type === "Delivery") {
          validateField(shipdayDeliveryDate, "shipday_delivery_date");
          validateField(shipdayDeliveryTime, "shipday_delivery_time");
        } else if (shipdaySettings.shipday_order_type === "Pickup") {
          validateField(shipdayPickupDate, "shipday_pickup_date");
          validateField(pickupTime, "pickup_time");
        }
      } else {
        if(shipdaySettings.enable_delivery_date)
          validateField(shipdayDeliveryDate, "shipday_delivery_date");
        if(shipdaySettings.enable_delivery_time)
          validateField(shipdayDeliveryTime, "shipday_delivery_time");
        if(shipdaySettings.enable_pickup_date)
        validateField(shipdayPickupDate, "shipday_pickup_date");
        if(shipdaySettings.enable_dpickup_time)
          validateField(pickupTime, "pickup_time");
      }
    }
  }, [isBeforeProcessing]);

  // Set extension data when values change
  React.useEffect(() => {
    checkoutExtensionData.setExtensionData("shipday-woo-delivery", "shipday_order_type", shipdayOrderType);
  }, [shipdayOrderType]);

  React.useEffect(() => {
    checkoutExtensionData.setExtensionData("shipday-woo-delivery", "shipday_delivery_date", shipdayDeliveryDate);
  }, [shipdayDeliveryDate]);

  React.useEffect(() => {
    checkoutExtensionData.setExtensionData("shipday-woo-delivery", "shipday_delivery_time", shipdayDeliveryTime);
  }, [shipdayDeliveryTime]);

  React.useEffect(() => {
    checkoutExtensionData.setExtensionData("shipday-woo-delivery", "shipday_pickup_date", shipdayPickupDate);
  }, [shipdayPickupDate]);

  React.useEffect(() => {
    checkoutExtensionData.setExtensionData("shipday-woo-delivery", "pickup_time", pickupTime);
  }, [pickupTime]);

  // Update state when shipdaySettings change
  React.useEffect(() => { setShipdayOrderType(shipdaySettings.shipday_order_type); }, [shipdaySettings.shipday_order_type]);
  React.useEffect(() => { setShipdayDeliveryDate(shipdaySettings.shipday_delivery_date); }, [shipdaySettings.shipday_delivery_date]);
  React.useEffect(() => { setShipdayDeliveryTime(shipdaySettings.shipday_delivery_time); }, [shipdaySettings.shipday_delivery_time]);
  React.useEffect(() => { setShipdayPickupDate(shipdaySettings.shipday_pickup_date); }, [shipdaySettings.shipday_pickup_date]);
  React.useEffect(() => { setPickupTime(shipdaySettings.pickup_time); }, [shipdaySettings.pickup_time]);

  // Handle checkout errors
  const hasCheckoutError = useSelect(store => store(checkoutStoreKey).hasError());

  // Event handlers
  const handleOrderTypeChange = (event) => {
    const value = event.target.value;
    if (value === '') {
      event.target.classList.add('shipday-select-placeholder');
    } else {
      event.target.classList.remove('shipday-select-placeholder');
    }
    setShipdayOrderType(value);
    validateField(value, "shipday_order_type");
    setIsProcessing(true);
    wc.blocksCheckout.extensionCartUpdate({
      namespace: "shipday_woo_delivery_order_type_change",
      data: {
        shipday_order_type: value
      }
    }).finally(() => setIsProcessing(false));
  };

  const handleDeliveryDateChange = (value) => {
    setShipdayDeliveryDate(value);
    validateField(value, "shipday_delivery_date");
    setIsProcessing(true);
    wc.blocksCheckout.extensionCartUpdate({
      namespace: "shipday_woo_delivery_delivery_date_change",
      data: {
        shipday_delivery_date: value
      }
    }).finally(() => setIsProcessing(false));
  };

  const handlePickupDateChange = (value) => {
    setShipdayPickupDate(value);
    validateField(value, "shipday_pickup_date");
    setIsProcessing(true);
    wc.blocksCheckout.extensionCartUpdate({
      namespace: "shipday_woo_delivery_pickup_date_change",
      data: {
        shipday_pickup_date: value
      }
    }).finally(() => setIsProcessing(false));
  };


  const handleDeliveryTimeChange = (event) => {
    const value = event.target.value;
    if(value === ''){
      event.target.classList.add('shipday-select-placeholder');
    }else {
      event.target.classList.remove('shipday-select-placeholder');
    }
    setShipdayDeliveryTime(value);
    validateField(value, "shipday_delivery_time");
    setIsProcessing(true);
    wc.blocksCheckout.extensionCartUpdate({
      namespace: "shipday_woo_delivery_delivery_time_change",
      data: {
        shipday_delivery_time: value
      }
    }).finally(() => setIsProcessing(false));
  };

    const handlePickupTimeChange = (event) => {
      const value = event.target.value;
      if(value === ''){
        event.target.classList.add('shipday-select-placeholder');
      }else {
        event.target.classList.remove('shipday-select-placeholder');
      }
      setPickupTime(value);
      validateField(value, "pickup_time");
      setIsProcessing(true);
      wc.blocksCheckout.extensionCartUpdate({
        namespace: "shipday_woo_delivery_pickup_time_change",
        data: {
          pickup_time: value
        }
      }).finally(() => setIsProcessing(false));
    };


  return shipdaySettings.enable_datetime_plugin && React.createElement("div", {
    className: `shipday-woo-delivery-block-container${isProcessing ? " processing" : ""}`
  }, 
  React.createElement("legend", {
    className: "screen-reader-text"
  }, shipdaySettings.delivery_option_field_label), 

  React.createElement("div", {
    className: "wc-block-components-checkout-step__heading"
  }, 
    React.createElement("h2", {
      className: "wc-block-components-title wc-block-components-checkout-step__title",
      ariaHidden: true
    }, shipdaySettings.delivery_heading_checkout)
  ), 

  // Order Type Component
    shipdaySettings.enable_datetime_plugin && shipdaySettings.enable_delivery_option && React.createElement(Shipday_Woo_Order_Type, {
    shipdaySettings: shipdaySettings,
    handleOrderTypeChange: handleOrderTypeChange,
    getValidationError: getValidationError
  }), 

  // Delivery Date Component
    shipdaySettings.enable_datetime_plugin && shipdaySettings.enable_delivery_date &&
  (!shipdaySettings.enable_delivery_option || shipdayOrderType === "Delivery") &&
  React.createElement(Shipday_Woo_Delivery_Date, {
    shipdaySettings: shipdaySettings,
    handleDeliveryDateChange: handleDeliveryDateChange,
    getValidationError: getValidationError
  }), 

  // Delivery Time Component
    shipdaySettings.enable_datetime_plugin && shipdaySettings.enable_delivery_time &&
  (!shipdaySettings.enable_delivery_option || shipdayOrderType === "Delivery") &&
  React.createElement(Shipday_Woo_Delivery_Time, {
    shipdaySettings: shipdaySettings,
    shipdayDeliveryTime: shipdayDeliveryTime,
    handleDeliveryTimeChange: handleDeliveryTimeChange,
    getValidationError: getValidationError
  }),

  // Pickup Date Component
    shipdaySettings.enable_datetime_plugin && shipdaySettings.enable_pickup_date &&
  (!shipdaySettings.enable_delivery_option || shipdayOrderType === "Pickup") &&
  React.createElement(Shipday_Woo_Pickup_Date, {
    shipdaySettings: shipdaySettings,
    handlePickupDateChange: handlePickupDateChange,
    getValidationError: getValidationError
  }),

    // Pickup Time Component
    shipdaySettings.enable_datetime_plugin && shipdaySettings.enable_pickup_time &&
    (!shipdaySettings.enable_delivery_option || shipdayOrderType === "Pickup") &&
    React.createElement(Shipday_Woo_Pickup_Time, {
      shipdaySettings: shipdaySettings,
      handlePickupTimeChange: handlePickupTimeChange,
      pickupTime: pickupTime,
      getValidationError: getValidationError
    }),

  )
  },


  // Format a date object to YYYY-MM-DD string
  shipdayFormattedDate = date => {
    let year = date.getFullYear(),
        month = ("0" + (date.getMonth() + 1)).slice(-2),
        day = ("0" + date.getDate()).slice(-2);
    return `${year}-${month}-${day}`
  },

  // Get an array of enabled dates based on shipdaySettings
  shipdayEnableDates = (numberOfDays, startDate, disabledWeekDays, disabledDates) => {
    // Ensure numberOfDays is a number
    numberOfDays = parseInt(numberOfDays);

    let enabledDates = [],
        currentDate = new Date(startDate);

    // Loop until we have enough enabled dates
    while (enabledDates.length < numberOfDays) {
      let formattedCurrentDate = shipdayFormattedDate(currentDate);
      // Add date if it's not in disabled weekdays or disabled dates
      if (!disabledWeekDays.includes(currentDate.getDay().toString()) && 
          !disabledDates.includes(formattedCurrentDate)) {
        enabledDates.push(formattedCurrentDate);
      }
      // Move to next day
      currentDate.setDate(currentDate.getDate() + 1);
    }

    return enabledDates
  },
  // Order Type Component
  Shipday_Woo_Order_Type = ({
    shipdaySettings,
    handleOrderTypeChange,
    getValidationError
  }) => {
    // Extract needed properties from shipdaySettings
    let {
      delivery_options: deliveryOptions,
      shipday_order_type: shipdayOrderType
    } = shipdaySettings;

    // Get validation error if any
    const validationError = getValidationError("shipday_woo_order_type_error");

    // Generate options for the select dropdown
    const renderOptions = () => 
      Object.entries(deliveryOptions).map(([value, label]) => 
        React.createElement("option", {
          key: value,
          value: value,
          selected: shipdayOrderType === value
        }, label)
      );

    // Render the component
    return React.createElement("div", {
      className: `shipday-woo-order-type-container${validationError ? " has-error" : ""}`
    }, 
    React.createElement("div", {
      className: `wc-blocks-components-select shipday-woo-delivery-select${shipdayOrderType ? "" : " not-selected"}`
    }, 
      React.createElement("div", {
        className: "wc-blocks-components-select__container"
      }, 
        // Label
        React.createElement("label", {
          htmlFor: "shipday_woo_order_type",
          className: "wc-blocks-components-select__label"
        }, shipdaySettings.delivery_option_field_label), 

        // Select dropdown
        React.createElement("select", {
          size: "1",
          name: "shipday_woo_order_type",
          className: "wc-blocks-components-select__select shipday-select-placeholder",
          id: "shipday_woo_order_type",
          "aria-label": shipdaySettings.delivery_option_field_label,
          "aria-invalid": validationError ? "true" : "false",
          onChange: handleOrderTypeChange,
          required: shipdaySettings.enable_delivery_option
        }, 
          // Default empty option
          React.createElement("option", {
            value: "",
            className: "shipday-select-placeholder"
          }, shipdaySettings.select_order_type_text), 

          // Delivery options
          renderOptions()
        ), 

        // Dropdown arrow icon
        React.createElement("svg", {
          viewBox: "0 0 24 24",
          xmlns: "http://www.w3.org/2000/svg",
          width: "24",
          height: "24",
          className: "wc-blocks-components-select__expand",
          "aria-hidden": "true",
          focusable: "false"
        }, 
          React.createElement("path", {
            d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"
          })
        )
      )
    ), 

    // Error message if validation fails
    validationError && React.createElement("div", {
      className: "wc-block-components-validation-error",
      role: "alert"
    }, 
      React.createElement("p", {}, validationError.message)
    ))
  },

  // Delivery Date Component
  Shipday_Woo_Delivery_Date = ({
    shipdaySettings,
    handleDeliveryDateChange,
    getValidationError
  }) => {
    // Create a ref for the date picker input
    const datePickerRef = React.useRef(null);

    // State to track if the date field is active (has a value)
    const [isActive, setIsActive] = React.useState(!!shipdaySettings.shipday_delivery_date);

    // Flag to prevent onChange handler during initialization
    let isInitializing = false;

    // Get validation error if any
    const validationError = getValidationError("shipday_woo_delivery_date_error");

    // Get disabled days and dates from shipdaySettings
    const disabledWeekDays = shipdaySettings.delivery_disable_week_days;
    const disabledDates = [];

    // Get enabled dates based on shipdaySettings
    const enabledDates = shipdayEnableDates(
      shipdaySettings.delivery_date_selectable_days, 
      shipdaySettings.today, 
      disabledWeekDays, 
      disabledDates
    );

    // Prepare field label, adding "(optional)" if not mandatory
    let fieldLabel = shipdaySettings.delivery_date_field_label;
    if (!shipdaySettings.delivery_date_mandatory) {
      fieldLabel += " (optional)";
    }

    // Initialize flatpickr date picker
    React.useEffect(() => {
      // Create flatpickr instance
      const flatpickrInstance = flatpickr(datePickerRef.current, {
        defaultDate: shipdaySettings.shipday_delivery_date,
        enable: enabledDates,
        minDate: shipdaySettings.today,
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: shipdaySettings.delivery_date_format,
        locale: {
          firstDayOfWeek: shipdaySettings.week_starts_from
        },

        // Handle initialization
        onReady(selectedDates, dateStr, instance) {
          // Check if current value matches shipdaySettings
          const expectedValue = shipdaySettings.shipday_delivery_date !== null ? shipdaySettings.shipday_delivery_date : "";

          // If they don't match, reset the picker
          if (dateStr !== expectedValue) {
            setIsActive(false);
            isInitializing = true;
            instance.clear();
            isInitializing = false;
          }
        },

        // Handle date selection
        onChange(selectedDates, dateStr, instance) {
          // Skip if we're initializing
          if (!isInitializing) {
            handleDeliveryDateChange(dateStr);
          }
        },

        // Update active state when picker opens/closes
        onOpen(selectedDates, dateStr, instance) {
          setIsActive(dateStr !== "");
        },
        onClose(selectedDates, dateStr, instance) {
          setIsActive(dateStr !== "");
        }
      });

      // Cleanup function to destroy flatpickr instance
      return () => {
        if (flatpickrInstance) {
          flatpickrInstance.destroy();
        }
      };
    }, [shipdaySettings]);

    // Render the component
    return React.createElement("div", {
      className: `shipday-woo-delivery-date-container${validationError ? " has-error" : ""}`
    }, 
    React.createElement("div", {
      className: `wc-block-components-text-input shipday-woo-delivery-text-input${isActive ? " is-active" : ""}`
    }, 
      // Date input field
      React.createElement("input", {
        ref: datePickerRef,
        type: "text",
        name: "shipday_woo_delivery_date",
        id: "shipday_woo_delivery_date",
        "aria-label": shipdaySettings.delivery_date_field_label,
        "aria-invalid": validationError ? "true" : "false",
        required: shipdaySettings.delivery_date_mandatory
      }), 

      // Field label
      React.createElement("label", {
        className: "shipday-woo-delivery-date-label",
        htmlFor: "shipday_woo_delivery_date"
      }, fieldLabel),
      // Calendar icon (purely visual)
      React.createElement(
        "span",
        {
          className: "shipday-woo-delivery-date-icon",
          "aria-hidden": "true",
          onClick: () => {
            if (
              datePickerRef &&
              datePickerRef.current &&
              datePickerRef.current._flatpickr
            ) {
              datePickerRef.current._flatpickr.open();
            }
          },
        },
        React.createElement("span", {
          className: "dashicons dashicons-calendar-alt",
        })
      )
    ),


    // Error message if validation fails
    validationError && React.createElement("div", {
      className: "wc-block-components-validation-error",
      role: "alert"
    }, 
      React.createElement("p", {}, validationError.message)
    ))
  },
  // Delivery Time Component
  Shipday_Woo_Delivery_Time = ({
    shipdaySettings,
    shipdayDeliveryTime,
    handleDeliveryTimeChange,
    getValidationError
  }) => {
    // Create a ref for the select dropdown
    const selectRef = React.useRef(null);

    // Extract time options from shipdaySettings
    const {delivery_time_options: timeOptions} = shipdaySettings;

    // Get validation error if any
    const validationError = getValidationError("shipday_woo_delivery_time_error");

    // Reset select to default option if shipdayDeliveryTime is empty
    React.useEffect(() => {
      if (!shipdayDeliveryTime && selectRef.current) {
        selectRef.current.selectedIndex = 0;
      }
    });

    // Prepare field label, adding "(optional)" if not mandatory
    let fieldLabel = shipdaySettings.delivery_time_field_label;
    if (!shipdaySettings.delivery_time_mandatory) {
      fieldLabel += " (optional)";
    }

    // Generate options for the select dropdown
    const renderTimeOptions = () =>
      Object.entries(timeOptions).map(([value, option]) =>
        React.createElement("option", {
          key: value,
          value: value,
          selected: shipdayDeliveryTime === value && !option.disabled,
          disabled: option.disabled
        }, option.title)
      );

    // Render the component
    return React.createElement("div", {
        className: `shipday-woo-delivery-time-container${validationError ? " has-error" : ""}`
      },
      React.createElement("div", {
          className: `wc-blocks-components-select shipday-woo-delivery-select${shipdayDeliveryTime ? "" : " not-selected"}`
        },
        React.createElement("div", {
            className: "wc-blocks-components-select__container"
          },
          // Label
          React.createElement("label", {
            htmlFor: "shipday_woo_delivery_time",
            className: "wc-blocks-components-select__label"
          }, fieldLabel),

          // Select dropdown
          React.createElement("select", {
              ref: selectRef,
              size: "1",
              name: "shipday_woo_delivery_time",
              className: "wc-blocks-components-select__select shipday-select-placeholder",
              id: "shipday_woo_delivery_time",
              "aria-label": shipdaySettings.delivery_time_field_label,
              "aria-invalid": validationError ? "true" : "false",
              onChange: handleDeliveryTimeChange,
              required: shipdaySettings.delivery_time_mandatory
            },
            // Default empty option
            React.createElement("option", {
              value: ""
            }, shipdaySettings.select_delivery_time_text),

            // Time options
            renderTimeOptions()
          ),

          // Dropdown arrow icon
          React.createElement("svg", {
              viewBox: "0 0 24 24",
              xmlns: "http://www.w3.org/2000/svg",
              width: "24",
              height: "24",
              className: "wc-blocks-components-select__expand",
              "aria-hidden": "true",
              focusable: "false"
            },
            React.createElement("path", {
              d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"
            })
          )
        )
      ),
      // Error message if validation fails
      validationError && React.createElement("div", {
          className: "wc-block-components-validation-error",
          role: "alert"
        },
        React.createElement("p", {}, validationError.message)
      ))
    },
      //Pickup Time Component
      Shipday_Woo_Pickup_Time = ({
                                     shipdaySettings,
                                     handlePickupTimeChange,
                                     pickupTime,
                                     getValidationError
                                   }) => {
        // Create a ref for the select dropdown
        const selectRef = React.useRef(null);

        // Extract time options from shipdaySettings
        const { pickup_time_options: timeOptions } = shipdaySettings;

        // Get validation error if any
        const validationError = getValidationError("shipday_woo_pickup_time_error");

        // Reset select to default option if shipdayDeliveryTime is empty
        React.useEffect(() => {
          if (!pickupTime && selectRef.current) {
            selectRef.current.selectedIndex = 0;
          }
        });

        // Prepare field label, adding "(optional)" if not mandatory
        let fieldLabel = "Pickup Slot";
        if (!shipdaySettings.pickup_time_mandatory) {
          fieldLabel += " (optional)";
        }

        // Generate options for the select dropdown
        const renderTimeOptions = () =>
          Object.entries(timeOptions).map(([value, option]) =>
            React.createElement("option", {
              key: value,
              value: value,
              selected: pickupTime === value && !option.disabled,
              disabled:  option.disabled
            }, option.title)
          );

        // Render the component
        return React.createElement("div", {
            className: `shipday-woo-pickup-time-container${validationError ? " has-error" : ""}`
          },
          React.createElement("div", {
              className: `wc-blocks-components-select shipday-woo-delivery-select${pickupTime ? "" : " not-selected"}`
            },
            React.createElement("div", {
                className: "wc-blocks-components-select__container"
              },
              // Label
              React.createElement("label", {
                htmlFor: "shipday_woo_pickup_time",
                className: "wc-blocks-components-select__label"
              }, fieldLabel),

              // Select dropdown
              React.createElement("select", {
                  ref: selectRef,
                  size: "1",
                  name: "shipday_woo_pickup_time",
                  className: "wc-blocks-components-select__select shipday-select-placeholder",
                  id: "shipday_woo_pickup_time",
                  "aria-label": 'Shipday-pickup-time',
                  "aria-invalid": validationError ? "true" : "false",
                  onChange: handlePickupTimeChange,
                  required: shipdaySettings.pickup_time_mandatory
                },
                // Default empty option
                React.createElement("option", {
                  value: ""
                }, "Select Pickup Slot"),

                // Time options
                renderTimeOptions()
              ),

              // Dropdown arrow icon
              React.createElement("svg", {
                  viewBox: "0 0 24 24",
                  xmlns: "http://www.w3.org/2000/svg",
                  width: "24",
                  height: "24",
                  className: "wc-blocks-components-select__expand",
                  "aria-hidden": "true",
                  focusable: "false"
                },
                React.createElement("path", {
                  d: "M17.5 11.6L12 16l-5.5-4.4.9-1.2L12 14l4.5-3.6 1 1.2z"
                })
              )
            )
          ),

          // Error message if validation fails
    validationError && React.createElement("div", {
      className: "wc-block-components-validation-error",
      role: "alert"
    }, 
      React.createElement("p", {}, validationError.message)
    ))
  },


  // Pickup Date Component
  Shipday_Woo_Pickup_Date = ({
                                 shipdaySettings,
                                 handlePickupDateChange,
                                 getValidationError
                               }) => {
    const pickupDatePickerRef = React.useRef(null);
    const [isActive, setIsActive] = React.useState(!!shipdaySettings.shipday_pickup_date);
    let isInitializing = false;
    const validationError = getValidationError("shipday_woo_pickup_date_error");

    // Prepare field label, adding "(optional)" if not mandatory
    let fieldLabel = shipdaySettings.pickup_date_field_label;
    if (!shipdaySettings.pickup_date_mandatory) {
      fieldLabel += " (optional)";
    }

    // Get disabled days and dates from shipdaySettings
    const disabledWeekDays = shipdaySettings.pickup_disable_week_days;
    const disabledDates = [];

    // Get enabled dates based on shipdaySettings
    const enabledDates = shipdayEnableDates(
      shipdaySettings.pickup_date_selectable_days,
      shipdaySettings.today,
      disabledWeekDays,
      disabledDates
    );

    // Initialize flatpickr date picker
    React.useEffect(() => {
      // Create flatpickr instance
      const flatpickrInstance = flatpickr(pickupDatePickerRef.current, {
        defaultDate: shipdaySettings.shipday_pickup_date,
        enable: enabledDates,
        dateFormat: "Y-m-d",
        altInput: true,
        locale: {
          firstDayOfWeek: shipdaySettings.week_starts_from
        },

        // Handle initialization
        onReady(selectedDates, dateStr, instance) {
          // Check if current value matches shipdaySettings
          const expectedValue = shipdaySettings.shipday_pickup_date !== null ? shipdaySettings.shipday_pickup_date : "";
          // If they don't match, reset the picker
          if (dateStr !== expectedValue) {
            setIsActive(false);
            isInitializing = true;
            instance.clear();
            isInitializing = false;
          }
        },

        // Handle date selection
        onChange(selectedDates, dateStr, instance) {
          // Skip if we're initializing
          if (!isInitializing) {
            handlePickupDateChange(dateStr);
          }
        },

        // Update active state when picker opens/closes
        onOpen(selectedDates, dateStr, instance) {
          setIsActive(dateStr !== "");
        },
        onClose(selectedDates, dateStr, instance) {
          setIsActive(dateStr !== "");
        }
      });
      // Cleanup function to destroy flatpickr instance
      return () => {
        if (flatpickrInstance) {
          flatpickrInstance.destroy();
        }
      };
    }, [shipdaySettings]);

    // Render the component
    return React.createElement("div", {
        className: `shipday-woo-delivery-date-container${validationError ? " has-error" : ""}`
      },
      React.createElement("div", {
          className: `wc-block-components-text-input shipday-woo-delivery-text-input${isActive ? " is-active" : ""}`
        },
        // Date input field
        React.createElement("input", {
          ref: pickupDatePickerRef,
          type: "text",
          name: "shipday_woo_pickup_date",
          id: "shipday_woo_pickup_date",
          "aria-label": shipdaySettings.pickup_date_field_label,
          "aria-invalid": validationError ? "true" : "false",
          required: shipdaySettings.pickup_date_mandatory
        }),

        // Field label
        React.createElement("label", {
          className: "shipday-woo-delivery-date-label",
          htmlFor: "shipday_woo_delivery_date"
        }, fieldLabel),
        // Calendar icon (purely visual)
        React.createElement(
          "span",
          {
            className: "shipday-woo-delivery-date-icon",
            "aria-hidden": "true",
            onClick: () => {
              if (
                pickupDatePickerRef &&
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

      // Error message if validation fails
      validationError && React.createElement("div", {
          className: "wc-block-components-validation-error",
          role: "alert"
        },
        React.createElement("p", {}, validationError.message)
      ))
  };


  // Block metadata definition
  shipday_woo_delivery_metadata = {
    apiVersion: 3,
    name: "shipday-woo-delivery",
    title: "Shipday Woocommerce",
    category: "woocommerce",
    icon: "calendar-alt",
    description: "Show delivery/pickup inputs in WooCommerce checkout block",
    supports: {
      multiple: false
    },
    editorScript: "file:js/editor.js",
    viewStyle: ["file:css/editor.css"],
    parent: [shipday_woo_delivery_localize_settings.block_field_position],
    attributes: {
      lock: {
        type: "object",
        default: {
          remove: true,
          move: true
        }
      }
    }
  };

  // Block registration options
  shipday_woo_delivery_options = {
    metadata: shipday_woo_delivery_metadata,
    component: Shipday_Woo_Delivery
  };

// Register the checkout block with WooCommerce
wc.blocksCheckout.registerCheckoutBlock(shipday_woo_delivery_options);
