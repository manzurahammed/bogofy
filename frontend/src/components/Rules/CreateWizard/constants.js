import { __ } from "@wordpress/i18n";

export const ruleTypes = [
  {
    value: "buy_x_get_x",
    label: __("Buy X, Get same free", "bogofy"),
    desc: __("Cheapest of N identical items becomes free.", "bogofy"),
    vis: "same",
  },
  {
    value: "buy_x_get_y",
    label: __("Buy X, Get Y free", "bogofy"),
    desc: __("Customer adds trigger item; gift drops to $0.", "bogofy"),
    vis: "gift",
  },
];

export const defaultData = {
  title: "",
  rule_type: "buy_x_get_y",
  status: "active",
  buy_quantity: 1,
  free_quantity: 1,
  discount_type: "free",
  discount_value: 100,
  apply_to: "specific_products",
  buy_product_ids: [],
  free_product_ids: [],
  category_ids: [],
  max_free_qty: "",
  message_template: "",
  priority: 10,
  start_date: "",
  end_date: "",
};
