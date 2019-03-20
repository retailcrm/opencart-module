<?php

class ModelExtensionRetailcrmHistory extends Model
{
    /**
     * Create order in OC
     *
     * @param array $order
     *
     * @return int $order_id
     */
    public function addOrder($order)
    {
        $this->db->query("INSERT INTO `" . DB_PREFIX . "order` SET store_id = '" . (int)$order['store_id'] . "', store_name = '" . $order['store_name'] . "', customer_id = '" . (int)$order['customer_id'] . "', customer_group_id = '" . (int)$order['customer_group_id'] . "', firstname = '" . $this->db->escape($order['firstname']) . "', lastname = '" . $this->db->escape($order['lastname']) . "', email = '" . $this->db->escape($order['email']) . "', telephone = '" . $this->db->escape($order['telephone']) . "', custom_field = '" . $this->db->escape(isset($order['custom_field']) ? json_encode($order['custom_field']) : '') . "', payment_firstname = '" . $this->db->escape($order['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($order['payment_lastname']) . "', payment_address_1 = '" . $this->db->escape($order['payment_address_1']) . "', payment_city = '" . $this->db->escape($order['payment_city']) . "', payment_postcode = '" . $this->db->escape($order['payment_postcode']) . "', payment_country = '" . $this->db->escape($order['payment_country']) . "', payment_country_id = '" . (int)$order['payment_country_id'] . "', payment_zone = '" . $this->db->escape($order['payment_zone']) . "', payment_zone_id = '" . (int)$order['payment_zone_id'] . "', payment_method = '" . $this->db->escape($order['payment_method']) . "', payment_code = '" . $this->db->escape($order['payment_code']) . "', shipping_firstname = '" . $this->db->escape($order['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($order['shipping_lastname']) . "', shipping_address_1 = '" . $this->db->escape($order['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($order['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($order['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($order['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($order['shipping_country']) . "', shipping_country_id = '" . (int)$order['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($order['shipping_zone']) . "', shipping_zone_id = '" . (int)$order['shipping_zone_id'] . "', shipping_method = '" . $this->db->escape($order['shipping_method']) . "', shipping_code = '" . $this->db->escape($order['shipping_code']) . "', comment = '" . $this->db->escape($order['comment']) . "', total = '" . (float)$order['total'] . "', affiliate_id = '" . (int)$order['affiliate_id'] . "', language_id = '" . (int)$order['language_id'] . "', currency_id = '" . (int)$order['currency_id'] . "', currency_code = '" . $this->db->escape($order['currency_code']) . "', currency_value = '" . (float)$order['currency_value'] . "', order_status_id = '" . (int)$order['order_status_id'] . "',  date_added = NOW(), date_modified = NOW()");

        $order_id = $this->db->getLastId();

        // Products
        if (isset($order['order_product']) && $order['order_product']) {
            $this->addOrderProducts($order_id, $order['order_product']);
        }

        // Totals
        if (isset($order['order_total'])) {
            $this->addOrderTotals($order_id, $order['order_total']);
        }

        return $order_id;
    }

    /**
     * Edit order in OC
     *
     * @param int $order_id
     * @param array $order
     *
     * @return void
     */
    public function editOrder($order_id, $order)
    {
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET customer_id = '" . (int)$order['customer_id'] . "', customer_group_id = '" . (int)$order['customer_group_id'] . "', firstname = '" . $this->db->escape($order['firstname']) . "', lastname = '" . $this->db->escape($order['lastname']) . "', email = '" . $this->db->escape($order['email']) . "', telephone = '" . $this->db->escape($order['telephone']) . "', custom_field = '" . $this->db->escape(json_encode($order['custom_field'])) . "', payment_firstname = '" . $this->db->escape($order['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($order['payment_lastname']) . "',  payment_address_1 = '" . $this->db->escape($order['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($order['payment_address_2']) . "', payment_city = '" . $this->db->escape($order['payment_city']) . "', payment_postcode = '" . $this->db->escape($order['payment_postcode']) . "', payment_country = '" . $this->db->escape($order['payment_country']) . "', payment_country_id = '" . (int)$order['payment_country_id'] . "', payment_zone = '" . $this->db->escape($order['payment_zone']) . "', payment_zone_id = '" . (int)$order['payment_zone_id'] . "', payment_method = '" . $this->db->escape($order['payment_method']) . "', payment_code = '" . $this->db->escape($order['payment_code']) . "', shipping_firstname = '" . $this->db->escape($order['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($order['shipping_lastname']) . "', shipping_address_1 = '" . $this->db->escape($order['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($order['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($order['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($order['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($order['shipping_country']) . "', shipping_country_id = '" . (int)$order['shipping_country_id'] . "', shipping_zone = '" . $this->db->escape($order['shipping_zone']) . "', shipping_zone_id = '" . (int)$order['shipping_zone_id'] . "', shipping_method = '" . $this->db->escape($order['shipping_method']) . "', shipping_code = '" . $this->db->escape($order['shipping_code']) . "', comment = '" . $this->db->escape($order['comment']) . "', total = '" . (float)$order['total'] . "', order_status_id = '" . (int)$order['order_status_id'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

        $this->db->query("DELETE FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        // Products
        if (isset($order['order_product']) && $order['order_product']) {
            $this->addOrderProducts($order_id, $order['order_product']);
        }

        // Totals
        $this->db->query("DELETE FROM " . DB_PREFIX . "order_total WHERE order_id = '" . (int)$order_id . "'");

        if (isset($order['order_total'])) {
            $this->addOrderTotals($order_id, $order['order_total']);
        }
    }

    /**
     * Add order products
     *
     * @param int $order_id
     * @param array $products
     *
     * @return void
     */
    public function addOrderProducts($order_id, $products)
    {
        foreach ($products as $product) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_product SET order_id = '" . (int)$order_id . "', product_id = '" . (int)$product['product_id'] . "', name = '" . $this->db->escape($product['name']) . "', model = '" . $this->db->escape($product['model']) . "', quantity = '" . (int)$product['quantity'] . "', price = '" . (float)$product['price'] . "', total = '" . (float)$product['total'] . "', reward = '" . (float)$product['reward'] . "'");

            $order_product_id = $this->db->getLastId();

            foreach ($product['option'] as $option) {
                $this->db->query("INSERT INTO " . DB_PREFIX . "order_option SET order_id = '" . (int)$order_id . "', order_product_id = '" . (int)$order_product_id . "', product_option_id = '" . (int)$option['product_option_id'] . "', product_option_value_id = '" . (int)$option['product_option_value_id'] . "', name = '" . $this->db->escape($option['name']) . "', `value` = '" . $this->db->escape($option['value']) . "', `type` = '" . $this->db->escape($option['type']) . "'");
            }
        }
    }

    /**
     * Add order totals
     *
     * @param int $order_id
     * @param array $totals
     *
     * @return void
     */
    public function addOrderTotals($order_id, $totals)
    {
        foreach ($totals as $total) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_total SET order_id = '" . (int)$order_id . "', code = '" . $this->db->escape($total['code']) . "', title = '" . $this->db->escape($total['title']) . "', `value` = '" . (float)$total['value'] . "', sort_order = '" . (int)$total['sort_order'] . "'");
        }
    }

    /**
     * Get total titles
     *
     * @return string $title
     */
    protected function totalTitles()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = '';
        } else {
            $title = 'total_';
        }

        return $title;
    }

    /**
     * Get country by iso code 2
     *
     * @param string $isoCode
     *
     * @return array
     */
    public function getCountryByIsoCode($isoCode)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE iso_code_2 = '" . $isoCode . "'");

        return $query->row;
    }

    /**
     * Get zone by name
     *
     * @param string $name
     *
     * @return array
     */
    public function getZoneByName($name)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE name = '" . $name . "'");

        return $query->row;
    }

    /**
     * Get currency
     *
     * @param string $code
     * @param string $field (default = '')
     *
     * @return mixed array | string
     */
    public function getCurrencyByCode($code, $field = '')
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "currency` WHERE code = '" . $code . "'");

        if (!$field) {
            return $query->row;
        }

        return $query->row[$field];
    }

    /**
     * Get language
     *
     * @param string $code
     * @param string $field (default = '')
     *
     * @return mixed array | string
     */
    public function getLanguageByCode($code, $field = '')
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE code = '" . $code . "'");

        if (!$field) {
            return $query->row;
        }

        return $query->row[$field];
    }

    /**
     * Get product option value
     *
     * @param int $option_value_id
     * @param string $field
     *
     * @return mixed array | string
     */
    public function getOptionValue($option_value_id, $field = '')
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "option_value_description` WHERE option_value_id = '" . $option_value_id . "'");

        if (!$field) {
            return $query->row;
        }

        return $query->row[$field];
    }
}
