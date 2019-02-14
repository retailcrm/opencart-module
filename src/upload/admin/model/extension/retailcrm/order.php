<?php

class ModelExtensionRetailcrmOrder extends Model {
    protected $settings;
    protected $moduleTitle;
    protected $retailcrmApiClient;

    protected static $lastRepsonse = null;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
    }

    /**
     * Upload orders to CRM
     *
     * @param array $orders
     * @param \RetailcrmProxy $retailcrmApiClient
     *
     * @return mixed
     */
    public function uploadToCrm($orders, $retailcrmApiClient)
    {
        if ($retailcrmApiClient === false) {
            return false;
        }

        $ordersToCrm = array();

        foreach ($orders as $order) {
            $ordersToCrm[] = $this->process($order);
        }

        $chunkedOrders = array_chunk($ordersToCrm, 50);

        foreach($chunkedOrders as $ordersPart) {
            $retailcrmApiClient->ordersUpload($ordersPart);
        }

        return $chunkedOrders;
    }

    /**
     * Send one order by id
     *
     * @param array $order_data
     * @param \RetailcrmProxy $retailcrmApiClient
     *
     * @return mixed
     */
    public function uploadOrder($order_data, $retailcrmApiClient)
    {
        if ($retailcrmApiClient === false) {
            return false;
        }

        if (isset($this->request->post['fromApi'])) {
            return false;
        }

        $order = $this->process($order_data);

        if (isset($order['customer']['externalId'])) {
            $this->load->model('extension/retailcrm/customer');
            $this->load->model('customer/customer');
            $customer = $this->model_customer_customer->getCustomer($order['customer']['externalId']);
            $this->model_extension_retailcrm_customer->sendToCrm($customer, $retailcrmApiClient);
        } else {
            $customers = $retailcrmApiClient->customersList(
                array(
                    'name' => $order_data['telephone'],
                    'email' => $order_data['email']
                ),
                1,
                100
            );

            if ($customers) {
                foreach ($customers['customers'] as $customer) {
                    $order['customer']['id'] = $customer['id'];
                }
            }

            unset($customers);
        }

        if (!isset($order['customer']['externalId']) && !isset($order['customer']['id'])) {
            $new_customer = array(
                'firstName' => $order_data['firstname'],
                'lastName' => $order_data['lastname'],
                'email' => $order_data['email'],
                'createdAt' => $order_data['date_added'],
                'address' => array(
                    'countryIso' => $order_data['payment_iso_code_2'],
                    'index' => $order_data['payment_postcode'],
                    'city' => $order_data['payment_city'],
                    'region' => $order_data['payment_zone'],
                    'text' => $order_data['payment_address_1'] . ' ' . $order_data['payment_address_2']
                )
            );

            if (!empty($order_data['telephone'])) {
                $new_customer['phones'] = array(
                    array(
                        'number' => $order_data['telephone']
                    )
                );
            }

            $res = $retailcrmApiClient->customersCreate($new_customer);

            if ($res->isSuccessful() && isset($res['id'])) {
                $order['customer']['id'] = $res['id'];
            }
        }

        self::$lastRepsonse = $retailcrmApiClient->ordersCreate($order);

        return $order;
    }

    /**
     * Process order
     *
     * @param array $order_data
     *
     * @return array $order
     */
    private function process($order_data) {
        $order = array();

        $this->load->model('catalog/product');

        if (!empty($order_data['payment_code']) && isset($this->settings[$this->moduleTitle . '_payment'][$order_data['payment_code']])) {
            $payment_code = $this->settings[$this->moduleTitle . '_payment'][$order_data['payment_code']];
        } else {
            $payment_code = '';
        }

        if (!empty($order_data['shipping_code'])) {
            $shippingCode = explode('.', $order_data['shipping_code']);
            $shippingModule = $shippingCode[0];

            if (isset($this->settings[$this->moduleTitle . '_delivery'][$order_data['shipping_code']])) {
               $delivery_code = $this->settings[$this->moduleTitle . '_delivery'][$order_data['shipping_code']];
            } elseif (isset($this->settings[$this->moduleTitle . '_delivery'][$shippingModule])) {
               $delivery_code = $this->settings[$this->moduleTitle . '_delivery'][$shippingModule];
            }
        }

        if (!isset($delivery_code) && isset($shippingModule)) {
            if (isset($this->settings[$this->moduleTitle . '_delivery'])
                && $this->settings[$this->moduleTitle . '_delivery']
            ) {
                $deliveries = array_keys($this->settings[$this->moduleTitle . '_delivery']);
                $shipping_code = '';

                array_walk($deliveries, function($item, $key) use ($shippingModule, &$shipping_code) {
                    if (strripos($item, $shippingModule) !== false) {
                        $shipping_code = $item;
                    }
                });

                $delivery_code = $this->settings[$this->moduleTitle . '_delivery'][$shipping_code];
            }
        }

        if (!empty($order_data['payment_iso_code_2'])) {
            $order['countryIso'] = $order_data['payment_iso_code_2'];
        }

        if (isset($this->settings[$this->moduleTitle . '_order_number'])
            && $this->settings[$this->moduleTitle . '_order_number'] == 1
        ) {
            $order['number'] = $order_data['order_id'];
        }

        $order['externalId'] = $order_data['order_id'];
        $order['firstName'] = $order_data['shipping_firstname'];
        $order['lastName'] = $order_data['shipping_lastname'];
        $order['phone'] = $order_data['telephone'];
        $order['customerComment'] = $order_data['comment'];

        if (!empty($order_data['email'])) {
            $order['email'] = $order_data['email'];
        }

        if ($order_data['customer_id']) {
            $order['customer']['externalId'] = $order_data['customer_id'];
        }

        $deliveryCost = 0;
        $orderTotals = isset($order_data['totals']) ? $order_data['totals'] : $order_data['order_total'] ;

        foreach ($orderTotals as $totals) {
            if ($totals['code'] == 'shipping') {
                $deliveryCost = $totals['value'];
            }
        }

        $order['createdAt'] = $order_data['date_added'];

        if ($this->settings[$this->moduleTitle . '_apiversion'] != 'v5') {
            $order['paymentType'] = $payment_code;
        }

        $country = (isset($order_data['shipping_country'])) ? $order_data['shipping_country'] : '' ;

        $order['delivery'] = array(
            'code' => isset($delivery_code) ? $delivery_code : '',
            'cost' => $deliveryCost,
            'address' => array(
                'countryIso' => $order_data['shipping_iso_code_2'],
                'index' => $order_data['shipping_postcode'],
                'city' => $order_data['shipping_city'],
                'region' => $order_data['shipping_zone'],
                'text' => implode(', ', array(
                    $order_data['shipping_postcode'],
                    $country,
                    $order_data['shipping_city'],
                    $order_data['shipping_address_1'],
                    $order_data['shipping_address_2']
                ))
            )
        );

        $orderProducts = isset($order_data['products']) ? $order_data['products'] : $order_data['order_product'];
        $offerOptions = array('select', 'radio');

        foreach ($orderProducts as $product) {
            $offerId = '';

            if (!empty($product['option'])) {
                $options = array();

                $productOptions = $this->model_catalog_product->getProductOptions($product['product_id']);

                foreach ($product['option'] as $option) {
                    if ($option['type'] == 'checkbox') {
                        $properties[] = array(
                            'code' => $option['product_option_value_id'],
                            'name' => $option['name'],
                            'value' => $option['value']
                        );
                    }

                    if (!in_array($option['type'], $offerOptions)) continue;
                    foreach($productOptions as $productOption) {
                        if($productOption['product_option_id'] = $option['product_option_id']) {
                            foreach($productOption['product_option_value'] as $productOptionValue) {
                                if($productOptionValue['product_option_value_id'] == $option['product_option_value_id']) {
                                    $options[$option['product_option_id']] = $productOptionValue['option_value_id'];
                                }
                            }
                        }
                    }
                }

                ksort($options);

                $offerId = array();
                foreach($options as $optionKey => $optionValue) {
                    $offerId[] = $optionKey.'-'.$optionValue;
                }
                $offerId = implode('_', $offerId);
            }

            if ($this->settings[$this->moduleTitle . '_apiversion'] != 'v3') {
                $item = array(
                    'offer' => array(
                        'externalId' => !empty($offerId) ? $product['product_id'].'#'.$offerId : $product['product_id']
                    ),
                    'productName' => $product['name'],
                    'initialPrice' => $product['price'],
                    'quantity' => $product['quantity'],
                );
            } else {
                $item = array(
                    'productName' => $product['name'],
                    'initialPrice' => $product['price'],
                    'quantity' => $product['quantity'],
                    'productId' => !empty($offerId) ? $product['product_id'].'#'.$offerId : $product['product_id']
                );
            }

            if (isset($properties)) $item['properties'] = $properties;
            $order['items'][] = $item;
        }

        if (isset($order_data['order_status_id']) && $order_data['order_status_id'] > 0) {
            $order['status'] = $this->settings[$this->moduleTitle . '_status'][$order_data['order_status_id']];
        }

        if ($this->settings[$this->moduleTitle . '_apiversion'] == 'v5') {
            if ($payment_code) {
                $payment = array(
                    'externalId' => $order_data['order_id'],
                    'type' => $payment_code
                );

                $order['payments'][] = $payment;
            }
        }

        if (isset($this->settings[$this->moduleTitle . '_custom_field']) && $order_data['custom_field']) {
            $customFields = json_decode($order_data['custom_field']);

            foreach ($customFields as $key => $value) {
                if (isset($this->settings[$this->moduleTitle . '_custom_field']['o_' . $key])) {
                    $customFieldsToCrm[$this->settings[$this->moduleTitle . '_custom_field']['o_' . $key]] = $value;
                }
            }

            if (isset($customFieldsToCrm)) {
                $order['customFields'] = $customFieldsToCrm;
            }
        }

        return $order;
    }

    /**
     * @return mixed
     */
    public static function getLastResponse()
    {
        return self::$lastRepsonse;
    }
}
