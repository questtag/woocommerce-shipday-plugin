const { __ } = wp.i18n;
const { createElement: el } = wp.element;
const { registerBlockType } = wp.blocks;

const shipdayBlockFieldPosition =
  window.shipdayWooDeliveryBlockData?.blockFieldPosition ||
  "woocommerce/checkout-contact-information-block";

registerBlockType("shipday-for-woocommerce/delivery-block", {
  apiVersion: 3,
  title: __("Shipday Delivery / Pickup", "shipday-for-woocommerce"),
  icon: "calendar-alt",
  category: "woocommerce",
  description: __(
    "Shows the Shipday delivery, pickup, date, and time fields inside Checkout.",
    "shipday-for-woocommerce"
  ),
  parent: [shipdayBlockFieldPosition],
  supports: {
    multiple: false,
    inserter: true,
    reusable: false,
  },
  attributes: {
    lock: {
      type: "object",
      default: {
        remove: true,
        move: true,
      },
    },
  },
  edit: () =>
    el(
      "div",
      {
        className: "shipday-woo-delivery-block-editor-placeholder",
      },
      el("strong", {}, __("Shipday Delivery / Pickup", "shipday-for-woocommerce")),
      el(
        "p",
        {},
        __(
          "This block renders the Shipday order type, delivery date, pickup date, and time fields on the checkout page.",
          "shipday-for-woocommerce"
        )
      )
    ),
  save: () => null,
});
