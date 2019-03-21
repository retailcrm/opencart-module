<?php

class ModelExtensionRetailcrmOrder extends Model {
    protected $settings;
    protected $moduleTitle;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
    }

    /**
     * Create order in CRM
     *
     * @param array $order
     * @param \RetailcrmProxy $retailcrmApiClient
     * @param array $data
     * @param bool $create (default = true)
     *
     * @return mixed
     */
    public function sendToCrm($order, $retailcrmApiClient, $data, $create = true) {
        if ($retailcrmApiClient === false) {
            return false;
        }

        if (!isset($order['customer']['externalId'])) {
            $customer = $this->searchCustomer($order['phone'], $order['email'], $retailcrmApiClient);

            if ($customer) {
                $order['customer']['id'] = $customer['id'];
            }
        }

        if (!isset($order['customer']['externalId']) && !isset($order['customer']['id'])) {
            $new_customer = array(
                'firstName' => $data['firstname'],
                'lastName' => $data['lastname'],
                'email' => $data['email'],
                'createdAt' => $data['date_added'],
                'address' => array(
                    'countryIso' => $data['payment_iso_code_2'],
                    'index' => $data['payment_postcode'],
                    'city' => $data['payment_city'],
                    'region' => $data['payment_zone'],
                    'text' => $data['payment_address_1'] . ' ' . $data['payment_address_2']
                )
            );

            if (!empty($data['telephone'])) {
                $new_customer['phones'] = array(
                    array(
                        'number' => $data['telephone']
                    )
                );
            }

            $res = $retailcrmApiClient->customersCreate($new_customer);

            if ($res->isSuccessful() && isset($res['id'])) {
                $order['customer']['id'] = $res['id'];
            }
        }

        if ($create) {
            $retailcrmApiClient->ordersCreate($order);
        } else {
            $order_payment = reset($order['payments']);
            unset($order['payments']);
            $response = $retailcrmApiClient->ordersEdit($order);

            if ($this->settings[$this->moduleTitle . '_apiversion'] == 'v5' && $response->isSuccessful()) {
                $this->updatePayment($order_payment, $order['externalId'], $retailcrmApiClient);
            }
        }

        return $order;
    }

    /**
     * Process order
     *
     * @param array $order_data
     * @param bool $create (default = true)
     *
     * @return array $order
     */
    public function processOrder($order_data, $create = true) {
        $this->load->model('setting/setting');
        $this->load->model('catalog/product');
        $this->load->model('account/customer');
        $this->load->model('extension/retailcrm/product');
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
        $order_id = $order_data['order_id'];

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

                array_walk($deliveries, function ($item, $key) use ($shippingModule, &$shipping_code) {
                    if (strripos($item, $shippingModule) !== false) {
                        $shipping_code = $item;
                    }
                });

                $delivery_code = $this->settings[$this->moduleTitle . '_delivery'][$shipping_code];
            }
        }

        if (!empty($order_data['shipping_iso_code_2'])) {
            $order['countryIso'] = $order_data['shipping_iso_code_2'];
        }

        if (isset($this->settings[$this->moduleTitle . '_order_number'])
            && $this->settings[$this->moduleTitle . '_order_number'] == 1
        ) {
            $order['number'] = $order_data['order_id'];
        }

        $order['externalId'] = $order_id;
        $order['firstName'] = $order_data['shipping_firstname'];
        $order['lastName'] = $order_data['shipping_lastname'];
        $order['phone'] = $order_data['telephone'];
        $order['customerComment'] = $order_data['comment'];

        if ($order_data['customer_id']) {
            $order['customer']['externalId'] = $order_data['customer_id'];
        }

        if (!empty($order_data['email'])) {
            $order['email'] = $order_data['email'];
        }

        $deliveryCost = 0;
        $couponTotal = 0;
        $orderTotals = isset($order_data['totals']) ? $order_data['totals'] : $order_data['order_total'] ;

        $totals = $this->explodeTotals($orderTotals);

        if (isset($totals['shipping'])) {
            $deliveryCost = $totals['shipping'];
        }

        if (isset($totals['coupon'])) {
            $couponTotal += abs($totals['coupon']);
        }

        if (isset($totals['reward'])) {
            $couponTotal += abs($totals['reward']);
        }

        $order['createdAt'] = $order_data['date_added'];

        if ($this->settings[$this->moduleTitle . '_apiversion'] != 'v5') {
            $order['paymentType'] = $payment_code;
            if ($couponTotal > 0) {
                $order['discount'] = $couponTotal;
            }
        } else {
            if ($couponTotal > 0) {
                $order['discountManualAmount'] = $couponTotal;
            }
        }

        $country = isset($order_data['shipping_country']) ? $order_data['shipping_country'] : '' ;

        $order['delivery'] = array(
            'code' => isset($delivery_code) ? $delivery_code : '',
            'address' => array(
                'index' => $order_data['shipping_postcode'],
                'city' => $order_data['shipping_city'],
                'countryIso' => $order_data['shipping_iso_code_2'],
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

        if (!empty($deliveryCost)){
            $order['delivery']['cost'] = $deliveryCost;
        }

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
                foreach ($options as $optionKey => $optionValue) {
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
                    'quantity' => $product['quantity']
                );

                $specials = $this->model_extension_retailcrm_product->getProductSpecials($product['product_id']);

                if (!empty($specials)) {
                    $customer = $this->model_account_customer->getCustomer($order_data['customer_id']);

                    foreach ($specials as $special) {
                        if (isset($customer['customer_group_id'])) {
                            if ($special['customer_group_id'] == $customer['customer_group_id']) {
                                if ($this->settings[$this->moduleTitle . '_special_' . $customer['customer_group_id']]) {
                                    $item['priceType']['code'] = $this->settings[$this->moduleTitle . '_special_' . $customer['customer_group_id']];
                                }
                            }
                        }
                    }
                }
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

            if (isset($order_data['order_status_id']) && $order_data['order_status_id'] > 0) {
                $order['status'] = $this->settings[$this->moduleTitle . '_status'][$order_data['order_status_id']];
            } elseif (isset($order_data['order_status_id']) && $order_data['order_status_id'] == 0) {
                $order['status'] = $this->settings[$this->moduleTitle . '_missing_status'];
            }

            if (isset($this->settings[$this->moduleTitle . '_custom_field']) && $order_data['custom_field']) {
                $customFields = $order_data['custom_field'];

                foreach ($customFields as $key => $value) {
                    if (isset($this->settings[$this->moduleTitle . '_custom_field']['o_' . $key])) {
                        $customFieldsToCrm[$this->settings[$this->moduleTitle . '_custom_field']['o_' . $key]] = $value;
                    }
                }

                if (isset($customFieldsToCrm)) {
                    $order['customFields'] = $customFieldsToCrm;
                }
            }
        }

        $payment = array(
            'externalId' => $order_id,
            'type' => $payment_code,
            'amount' => $totals['total']
        );

        if (!$create) {
            $payment['order'] = array(
                'externalId' => $order_id
            );
        }

        $order['payments'][] = $payment;

        return $order;
    }

    /**
     * Update payment in CRM
     *
     * @param array $order_payment
     * @param int $orderId
     *
     * @return void
     */
    private function updatePayment($order_payment, $orderId, $retailcrmApiClient) {
        $response_order = $retailcrmApiClient->ordersGet($orderId);

        if ($response_order->isSuccessful()) {
            $order_info = $response_order['order'];
        }

        foreach ($order_info['payments'] as $payment_data) {
            if (isset($payment_data['externalId']) && $payment_data['externalId'] == $orderId) {
                $payment = $payment_data;
            }
        }

        if (isset($payment) && $payment['type'] != $order_payment['type']) {
            $response = $retailcrmApiClient->ordersPaymentDelete($payment['id']);

            if ($response->isSuccessful()) {
                $retailcrmApiClient->ordersPaymentCreate($order_payment);
            }
        } elseif (isset($payment) && $payment['type'] == $order_payment['type']) {
            $retailcrmApiClient->ordersPaymentEdit($order_payment);
        }
    }

    private function searchCustomer($phone, $email, $retailcrmApiClient) {
        $customer = array();

        $response = $retailcrmApiClient->customersList(
            array(
                'name' => $phone,
                'email' => $email
            ),
            1,
            100
        );

        if ($response->isSuccessful() && isset($response['customers'])) {
            $customers = $response['customers'];

            if ($customers) {
                $customer = end($customers);
            }
        }

        return $customer;
    }

    /**
     * @param $totals
     *
     * @return array
     */
    private function explodeTotals($totals)
    {
        $resultTotals = array();

        foreach ($totals as $total) {
            $resultTotals[$total['code']] = $total['value'];
        }

        return $resultTotals;
    }
}
