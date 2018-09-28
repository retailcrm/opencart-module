<?php

namespace Retailcrm;

class Order extends Base
{
    protected $registry;
    protected $data = array(
        'number' => 0,
        'createdAt' => null,
        'countryIso' => null,
        'externalId' => 0,
        'customer' => array(),
        'status' => null,
        'firstName' => null,
        'lastName' => null,
        'patronymic' => null,
        'email' => null,
        'phone' => null,
        'discountManualAmount' => 0,
        'customerComment' => null,
        'items' => array(),
        'delivery' => array(),
        'customFields' => array(),
        'payments' => array()
    );

    public function prepare($order) {
        if (file_exists(DIR_SYSTEM . 'library/retailcrm/custom/order.php')) {
            $custom = new \Retailcrm\Custom\Order($this->registry);
            $this->data = $custom->prepare($order);
        } else {
            $this->load->model('setting/setting');
            $this->load->model('catalog/product');
            $this->load->model('extension/retailcrm/products');

            $settings = $this->model_setting_setting->getSetting(\Retailcrm\Retailcrm::MODULE);
            $delivery_settings = isset($settings[\Retailcrm\Retailcrm::MODULE . '_delivery'])
                ? $settings[\Retailcrm\Retailcrm::MODULE . '_delivery']
                : array();
            $payments_settings = isset($settings[\Retailcrm\Retailcrm::MODULE . '_payment'])
                ? $settings[\Retailcrm\Retailcrm::MODULE . '_payment']
                : array();
            $status_settings = isset($settings[\Retailcrm\Retailcrm::MODULE . '_status'])
                ? $settings[\Retailcrm\Retailcrm::MODULE . '_status']
                : array();

            $totals = $this->explodeTotals($order['totals']);
            $coupon_total = 0;
            $delivery_cost = 0;

            if (isset($totals['shipping'])) {
                $delivery_cost = $totals['shipping'];
            }

            if (isset($totals['coupon'])) {
                $coupon_total += abs($totals['coupon']);
            }

            if (isset($totals['reward'])) {
                $coupon_total += abs($totals['reward']);
            }

            if (isset($order['order_status_id']) && $order['order_status_id'] > 0) {
                $status = $status_settings[$order['order_status_id']];
            } elseif (!isset($order['order_status_id'])) {
                $status = $settings[\Retailcrm\Retailcrm::MODULE . '_missing_status'];
            }

            if (isset($settings[\Retailcrm\Retailcrm::MODULE . '_order_number'])
                && $settings[\Retailcrm\Retailcrm::MODULE . '_order_number'] == 1
            ) {
                $this->setField('number', $order['order_id']);
            }

            $fields = array(
                'firstName' => $order['firstname'],
                'lastName' => $order['lastname'],
                'email' => $order['email'],
                'phone' => $order['telephone'],
                'customerComment' => $order['comment'],
                'createdAt' => isset($order['date_added']) ? $order['date_added'] : date('Y-m-d H:i:s'),
                'discountManualAmount' => $coupon_total,
                'status' => $status
            );

            if (isset($order['order_id']) && !$this->data['externalId']) {
                $fields['externalId'] = $order['order_id'];
            }

            $this->setFields($fields);

            if (isset($order['shipping_code'])) {
                $delivery_code = $this->getDeliveryMethod($order['shipping_code'], $delivery_settings);
            }

            $delivery = array(
                'address' => array(
                    'index' => isset($order['shipping_postcode']) ? $order['shipping_postcode'] : '',
                    'city' => isset($order['shipping_city']) ? $order['shipping_city'] : '',
                    'region' => isset($order['shipping_zone']) ? $order['shipping_zone'] : '',
                    'text' => implode(', ', array(
                        isset($order['shipping_postcode']) ? $order['shipping_postcode'] : '',
                        isset($order['shipping_country']) ? $order['shipping_country'] : '',
                        isset($order['shipping_city']) ? $order['shipping_city'] : '',
                        isset($order['shipping_address_1']) ? $order['shipping_address_1'] : '',
                        isset($order['shipping_address_2']) ? $order['shipping_address_2'] : ''
                    ))
                )
            );

            if (isset($delivery_code)) {
                $delivery['code'] = $delivery_code;
            }

            if ($delivery_cost) {
                $delivery['cost'] = $delivery_cost;
            }

            $this->setDataArray($delivery, 'delivery');
            $this->setOrderProducts($order['products']);
            $payments = array();

            if (isset($payments_settings[$order['payment_code']])) {
                $payment = array(
                    'order' => array(
                        'externalId' => $order['order_id']
                    ),
                    'externalId' => $order['order_id'],
                    'type' => $payments_settings[$order['payment_code']],
                    'amount' => $order['total']
                );

                $payments[] = $payment;
            }

            $this->setDataArray($payments, 'payments');

            if (isset($settings[\Retailcrm\Retailcrm::MODULE . '_custom_field']) && $order['custom_field']) {
                $custom_fields = $this->prepareCustomFields($order['custom_field'], $settings, 'o_');

                if ($custom_fields) {
                    $this->setDataArray($custom_fields, 'customFields');
                }
            }

            if ($order['customer_id']) {
                $this->setDataArray(
                    array(
                        'externalId' => $order['customer_id']
                    ),
                    'customer'
                );
            }
        }

        parent::prepare($order);
    }

    /**
     * @param $retailcrm_api_client
     *
     * @return bool|mixed
     */
    public function create($retailcrm_api_client) {
        if ($retailcrm_api_client === false) {
            return false;
        }

        if (!$this->data['customer']) {
            $customer = $this->searchCustomer($this->data['phone'], $this->data['email'], $retailcrm_api_client);

            if ($customer) {
                $this->setDataArray(
                    array(
                        'id' => $customer['id']
                    ),
                    'customer'
                );
            }
        }

        $response = $retailcrm_api_client->ordersCreate($this->data);

        return $response;
    }

    /**
     * @param $retailcrm_api_client
     *
     * @return bool|mixed
     */
    public function edit($retailcrm_api_client) {
        if ($retailcrm_api_client === false) {
            return false;
        }

        $order_payment = reset($this->data['payments']);
        unset($this->data['payments']);

        $response = $retailcrm_api_client->ordersEdit($this->data);

        if ($response->isSuccessful()) {
            $this->updatePayment($order_payment, $this->data['externalId'], $retailcrm_api_client);
        }

        return $response;
    }

    /**
     * Update payment in CRM
     *
     * @param array $order_payment
     * @param int $order_id
     * @param \RetailcrmProxy $retailcrm_api_client
     *
     * @return boolean
     */
    private function updatePayment($order_payment, $order_id, $retailcrm_api_client) {
        $response_order = $retailcrm_api_client->ordersGet($order_id);

        if (!$response_order->isSuccessful()) {
            return false;
        }

        $order_info = $response_order['order'];

        foreach ($order_info['payments'] as $payment_data) {
            if (isset($payment_data['externalId']) && $payment_data['externalId'] == $order_id) {
                $payment = $payment_data;
            }
        }

        if (isset($payment) && $payment['type'] != $order_payment['type']) {
            $response = $retailcrm_api_client->ordersPaymentDelete($payment['id']);
            if ($response->isSuccessful()) {
                $retailcrm_api_client->ordersPaymentCreate($order_payment);
            }
        } elseif (isset($payment) && $payment['type'] == $order_payment['type']) {
            $retailcrm_api_client->ordersPaymentEdit($order_payment);
        }

        return true;
    }

    /**
     * @param $phone
     * @param $email
     * @param $retailcrm_api_client
     *
     * @return array|mixed
     */
    private function searchCustomer($phone, $email, $retailcrm_api_client) {
        $customer = array();
        $response = $retailcrm_api_client->customersList(
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
     * @param string $shipping_code
     * @param array $deliveries
     *
     * @return mixed
     */
    private function getDeliveryMethod($shipping_code, $deliveries) {
        if (!empty($shipping_code)) {
            $shipping_code_array = explode('.', $shipping_code);
            $shipping_module = $shipping_code_array[0];

            if (isset($deliveries[$shipping_code])) {
                $delivery_code = $deliveries[$shipping_code];
            } elseif (isset($deliveries[$shipping_module])) {
                $delivery_code = $deliveries[$shipping_module];
            }
        }

        if (!isset($delivery_code) && isset($shipping_module) && $deliveries) {
            $delivery = '';

            array_walk($deliveries, function ($item, $key) use ($shipping_module, &$delivery) {
                if (strripos($item, $shipping_module) !== false) {
                    $delivery = $item;
                }
            });

            $delivery_code = $deliveries[$delivery];
        }

        return isset($delivery_code) ? $delivery_code : null;
    }

    /**
     * @param array $products
     *
     * @return void
     */
    private function setOrderProducts($products) {
        $offer_options = array('select', 'radio');
        $items = array();

        foreach ($products as $product) {
            if (!empty($product['option'])) {
                $offer_id = '';
                $options = array();
                $properties = array();

                foreach ($product['option'] as $option) {
                    if ($option['type'] == 'checkbox') {
                        $properties[] = array(
                            'code' => $option['product_option_value_id'],
                            'name' => $option['name'],
                            'value' => $option['value']
                        );
                    }

                    if (!in_array($option['type'], $offer_options)) {
                        continue;
                    }

                    $productOptions = $this->model_extension_retailcrm_products->getProductOptions($product['product_id']);

                    foreach ($productOptions as $productOption) {
                        if ($productOption['product_option_id'] == $option['product_option_id']) {
                            foreach ($productOption['product_option_value'] as $productOptionValue) {
                                if ($productOptionValue['product_option_value_id'] == $option['product_option_value_id']) {
                                    $options[$option['product_option_id']] = $productOptionValue['option_value_id'];
                                }
                            }
                        }
                    }
                }

                ksort($options);

                foreach ($options as $optionKey => $optionValue) {
                    $offer_id .= $optionKey . '-' . $optionValue;
                }
            }

            $item = array(
                'offer' => array(
                    'externalId' => !empty($offer_id) ? $product['product_id'] . '#' . $offer_id : $product['product_id']
                ),
                'productName' => $product['name'],
                'initialPrice' => $product['price'],
                'quantity' => $product['quantity'],
            );

            if (!empty($properties)) {
                $item['properties'] = $properties;
            }

            $items[] = $item;
        }

        $this->setDataArray($items, 'items');
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
