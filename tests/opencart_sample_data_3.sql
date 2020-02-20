TRUNCATE TABLE `oc_customer`;
INSERT INTO `oc_customer` (`customer_id`, `customer_group_id`, `store_id`, `language_id`, `firstname`, `lastname`, `email`, `telephone`, `fax`, `password`, `salt`, `cart`, `wishlist`, `newsletter`, `address_id`, `custom_field`, `ip`, `status`, `safe`, `token`, `code`, `date_added`) VALUES ('1', '1', '0', '1', 'Test', 'Test', 'test@mail.ru', '+7 (000) 000-00-00', '', 'ed3798da75d6cdd695e99e87a60d587a10aa95ff', '51TalnrgH', '', '', '0', '1', '', '172.21.0.1', '1', '0', '', '', '2018-06-07 13:50:08');

TRUNCATE TABLE `oc_customer_activity`;
TRUNCATE TABLE `oc_customer_group`;
INSERT INTO `oc_customer_group` (`customer_group_id`, `approval`, `sort_order`) VALUES ('1', '0', '1');
INSERT INTO `oc_customer_group` (`customer_group_id`, `approval`, `sort_order`) VALUES ('2', '0', '1');
INSERT INTO `oc_customer_group` (`customer_group_id`, `approval`, `sort_order`) VALUES ('3', '0', '0');

TRUNCATE TABLE `oc_customer_group_description`;
INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES ('1', '1', 'Default', 'test');
INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES ('2', '1', 'Test2', 'test2');
INSERT INTO `oc_customer_group_description` (`customer_group_id`, `language_id`, `name`, `description`) VALUES ('3', '1', 'test3', 'test3');

TRUNCATE TABLE `oc_customer_history`;
TRUNCATE TABLE `oc_customer_ip`;
INSERT INTO `oc_customer_ip` (`customer_ip_id`, `customer_id`, `ip`, `date_added`) VALUES ('4', '1', '172.21.0.1', '2018-06-07 13:50:29');

TRUNCATE TABLE `oc_customer_login`;
TRUNCATE TABLE `oc_customer_online`;
TRUNCATE TABLE `oc_customer_reward`;
TRUNCATE TABLE `oc_customer_search`;
TRUNCATE TABLE `oc_customer_transaction`;
TRUNCATE TABLE `oc_customer_wishlist`;
TRUNCATE TABLE `oc_order`;

INSERT INTO `oc_order` (`order_id`, `invoice_no`, `invoice_prefix`, `store_id`, `store_name`, `store_url`, `customer_id`, `customer_group_id`, `firstname`, `lastname`, `email`, `telephone`, `fax`, `custom_field`, `payment_firstname`, `payment_lastname`, `payment_company`, `payment_address_1`, `payment_address_2`, `payment_city`, `payment_postcode`, `payment_country`, `payment_country_id`, `payment_zone`, `payment_zone_id`, `payment_address_format`, `payment_custom_field`, `payment_method`, `payment_code`, `shipping_firstname`, `shipping_lastname`, `shipping_company`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_postcode`, `shipping_country`, `shipping_country_id`, `shipping_zone`, `shipping_zone_id`, `shipping_address_format`, `shipping_custom_field`, `shipping_method`, `shipping_code`, `comment`, `total`, `order_status_id`, `affiliate_id`, `commission`, `marketing_id`, `tracking`, `language_id`, `currency_id`, `currency_code`, `currency_value`, `ip`, `forwarded_ip`, `user_agent`, `accept_language`, `date_added`, `date_modified`) VALUES ('1', '0', 'INV-2016-00', '0', 'Opencart', 'http://localhost:8000/', '1', '1', 'Test', 'Test', 'test@mail.ru', '+7 (000) 000-00-00', '', '', 'Test', 'Test', '', 'Address', 'Address 2', 'Test', '111111', 'Russian Federation', '176', 'Rostov-na-Donu', '99', '', '[]', 'Cash on delivery', 'cod', 'Test', 'Test', '', 'Address', 'Address 2', 'Test', '111111', 'Russian Federation', '176', 'Rostov-na-Donu', '99', '', '[]', 'Flat Rate', 'flat.flat', 'test comment', '106.0000', '1', '0', '0.0000', '0', '', '1', '1', 'USD', '1.00000000', '172.21.0.1', '', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36', 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7', '2018-06-07 13:51:10', '2018-06-07 13:51:23');
INSERT INTO `oc_order` (`order_id`, `invoice_no`, `invoice_prefix`, `store_id`, `store_name`, `store_url`, `customer_id`, `customer_group_id`, `firstname`, `lastname`, `email`, `telephone`, `fax`, `custom_field`, `payment_firstname`, `payment_lastname`, `payment_company`, `payment_address_1`, `payment_address_2`, `payment_city`, `payment_postcode`, `payment_country`, `payment_country_id`, `payment_zone`, `payment_zone_id`, `payment_address_format`, `payment_custom_field`, `payment_method`, `payment_code`, `shipping_firstname`, `shipping_lastname`, `shipping_company`, `shipping_address_1`, `shipping_address_2`, `shipping_city`, `shipping_postcode`, `shipping_country`, `shipping_country_id`, `shipping_zone`, `shipping_zone_id`, `shipping_address_format`, `shipping_custom_field`, `shipping_method`, `shipping_code`, `comment`, `total`, `order_status_id`, `affiliate_id`, `commission`, `marketing_id`, `tracking`, `language_id`, `currency_id`, `currency_code`, `currency_value`, `ip`, `forwarded_ip`, `user_agent`, `accept_language`, `date_added`, `date_modified`) VALUES ('2', '0', 'INV-2016-00', '0', 'Opencart', 'http://localhost:8000/', '0', '1', 'Test', 'Test', 'test@mail.ru', '+7 (000) 000-00-00', '', '[]', 'Test', 'Test', '', 'Address', 'Address 2', 'Test', '111111', 'Russian Federation', '176', 'Rostov-na-Donu', '99', '', '[]', 'Cash on delivery', 'cod', 'Test', 'Test', '', 'Address', 'Address 2', 'Test', '111111', 'Russian Federation', '176', 'Rostov-na-Donu', '99', '', '[]', 'Flat Rate', 'flat.flat', 'test comment', '85.0000', '1', '0', '0.0000', '0', '', '1', '1', 'USD', '1.00000000', '172.21.0.1', '', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.62 Safari/537.36', 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7', '2018-06-07 13:53:50', '2018-06-07 13:54:00');

TRUNCATE TABLE `oc_order_history`;
INSERT INTO `oc_order_history` (`order_history_id`, `order_id`, `order_status_id`, `notify`, `comment`, `date_added`) VALUES ('19', '2', '1', '0', '', '2018-06-07 13:54:00');
INSERT INTO `oc_order_history` (`order_history_id`, `order_id`, `order_status_id`, `notify`, `comment`, `date_added`) VALUES ('18', '1', '1', '0', '', '2018-06-07 13:51:23');

TRUNCATE TABLE `oc_order_option`;

INSERT INTO `oc_order_option` (`order_option_id`, `order_id`, `order_product_id`, `product_option_id`, `product_option_value_id`, `name`, `value`, `type`) VALUES ('15', '2', '55', '226', '15', 'Select', 'Red', 'select');

TRUNCATE TABLE `oc_order_product`;
INSERT INTO `oc_order_product` (`order_product_id`, `order_id`, `product_id`, `name`, `model`, `quantity`, `price`, `total`, `tax`, `reward`) VALUES ('54', '1', '40', 'iPhone', 'product 11', '1', '101.0000', '101.0000', '18.0000', '20');
INSERT INTO `oc_order_product` (`order_product_id`, `order_id`, `product_id`, `name`, `model`, `quantity`, `price`, `total`, `tax`, `reward`) VALUES ('55', '2', '30', 'Canon EOS 5D', 'Product 3', '1', '80.0000', '80.0000', '18.0000', '200');

TRUNCATE TABLE `oc_order_recurring`;
TRUNCATE TABLE `oc_order_recurring_transaction`;
TRUNCATE TABLE `oc_order_total`;

INSERT INTO `oc_order_total` (`order_total_id`, `order_id`, `code`, `title`, `value`, `sort_order`) VALUES ('162', '1', 'shipping', 'Flat Rate', '5.0000', '3');
INSERT INTO `oc_order_total` (`order_total_id`, `order_id`, `code`, `title`, `value`, `sort_order`) VALUES ('161', '1', 'sub_total', 'Sub-Total', '101.0000', '1');
INSERT INTO `oc_order_total` (`order_total_id`, `order_id`, `code`, `title`, `value`, `sort_order`) VALUES ('164', '2', 'sub_total', 'Sub-Total', '80.0000', '1');
INSERT INTO `oc_order_total` (`order_total_id`, `order_id`, `code`, `title`, `value`, `sort_order`) VALUES ('165', '2', 'shipping', 'Flat Rate', '5.0000', '3');
INSERT INTO `oc_order_total` (`order_total_id`, `order_id`, `code`, `title`, `value`, `sort_order`) VALUES ('163', '1', 'total', 'Total', '106.0000', '9');
INSERT INTO `oc_order_total` (`order_total_id`, `order_id`, `code`, `title`, `value`, `sort_order`) VALUES ('166', '2', 'total', 'Total', '85.0000', '9');
INSERT INTO `oc_product_special` (`product_id`, `customer_group_id`, `priority`, `price`,`date_start`, `date_end`) values ('42', '2', '1', '110.000', CURDATE(), ADDDATE(CURDATE(),INTERVAL 10 DAY));
INSERT INTO `oc_product_special` (`product_id`, `customer_group_id`, `priority`, `price`,`date_start`, `date_end`) values ('40', '1', '1', '50.000', CURDATE(), ADDDATE(CURDATE(),INTERVAL 10 DAY));

TRUNCATE TABLE `oc_order_voucher`;
