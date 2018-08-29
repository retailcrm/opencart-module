<?php

namespace Retailcrm;

class Order extends Base
{
    protected $registry;
    protected $data = array(
        'number' => 0,
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
        'items' => array(),
        'delivery' => array(),
        'customFields' => array(),
        'payments' => array()
    );

    public function prepare($order) {
        if (file_exists(DIR_SYSTEM . 'library/retailcrm/custom.php')) {
            $custom = new \Retailcrm\Custom($this->registry);
            $this->data = $custom->processOrder($order);
        } else {
            $this->load->model('setting/setting');
            $this->load->model('catalog/product');
            $settings = $this->model_setting_setting->getSetting(\Retailcrm\Retailcrm::MODULE);
            $delivery_settings = $settings[\Retailcrm\Retailcrm::MODULE . '_delivery'];
            $payments_settings = $settings[\Retailcrm\Retailcrm::MODULE . '_payment'];
            $status_settings = $settings[\Retailcrm\Retailcrm::MODULE . '_status'];

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
            } elseif (isset($order['order_status_id']) && $order['order_status_id'] == 0) {
                $status = $settings[\Retailcrm\Retailcrm::MODULE . '_missing_status'];
            }

            $fields = array(
                'firstName' => $order['firstname'],
                'lastName' => $order['lastname'],
                'email' => $order['email'],
                'phone' => $order['telephone'],
                'customerComment' => $order['comment'],
                'createdAt' => $order['date_added'],
                'discountManualAmount' => $coupon_total,
                'status' => $status
            );

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
        }
    }

    public function create($retailcrm_api_client) {
        $retailcrm_api_client->ordersCreate($this->data);
    }

    public function edit($retailcrm_api_client) {
        $retailcrm_api_client->ordersEdit($this->data);
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

        return $delivery_code;
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

                    $options[$option['product_option_id']] = $option['option_value_id'];
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
