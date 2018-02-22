<?php

class ModelExtensionRetailcrmOrder extends Model {
    protected $settings;
    protected $moduleTitle;
    protected $retailcrmApiClient;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
        $this->retailcrmApiClient = $this->retailcrm->getApiClient();
    }

    /**
     * Upload orders to CRM
     * 
     * @param array $orders
     * 
     * @return void
     */
    public function uploadToCrm($orders)
    {
        if ($this->retailcrmApiClient === false) {
            return;
        }

        $ordersToCrm = array();

        foreach($orders as $order) {
            $ordersToCrm[] = $this->process($order);
        }

        $chunkedOrders = array_chunk($ordersToCrm, 50);

        foreach($chunkedOrders as $ordersPart) {
            $this->retailcrmApiClient->ordersUpload($ordersPart);
        }
    }

    /**
     * Send one order by id
     * 
     * @param array $order_data
     * 
     * @return object $result
     */
    public function uploadOrder($order_data)
    {
        if ($this->retailcrmApiClient === false) {
            return;
        }

        if(isset($this->request->post['fromApi'])) {
            return;
        }

        $customers = $this->retailcrmApiClient->customersList(
            array(
                'name' => $order_data['telephone'],
                'email' => $order_data['email']
            ),
            1,
            100
        );

        $order = $this->process($order_data);

        if ($customers) {
            foreach ($customers['customers'] as $customer) {
                $order['customer']['id'] = $customer['id'];
            }
        }

        unset($customers);

        $result = $this->retailcrmApiClient->ordersCreate($order);

        if ($this->settings[$this->moduleTitle . '_apiversion'] == 'v5' && $result->isSuccessful()) {
            $this->createPayment($order_data, $order_data['order_id']);
        }

        return $result;
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
        $payment_code = $order_data['payment_code'];

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

        $order['number'] = $order_data['order_id'];
        $order['externalId'] = $order_data['order_id'];
        $order['firstName'] = $order_data['firstname'];
        $order['lastName'] = $order_data['lastname'];
        $order['phone'] = $order_data['telephone'];
        $order['customerComment'] = $order_data['comment'];

        if(!empty($order_data['email'])) {
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
            'code' => $delivery_code,
            'cost' => $deliveryCost,
            'address' => array(
                'index' => $order_data['shipping_postcode'],
                'city' => $order_data['shipping_city'],
                'country' => $order_data['shipping_country_id'],
                'region' => $order_data['shipping_zone_id'],
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

            if(!empty($product['option'])) {
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

                    if(!in_array($option['type'], $offerOptions)) continue;
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
                if (isset($this->settings[$moduleTitle . '_custom_field']['o_' . $key])) {
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
     * Create payment
     * 
     * @param array $order
     * @param int $order_id
     * 
     * @return void
     */
    protected function createPayment($order, $order_id)
    {
        $payment_code = $order['payment_code'];

        foreach ($order['totals'] as $total) {
            if ($total['code'] == 'total') {
                $amount = $total['value'];
            }
        }

        $payment = array(
            'externalId' => $order_id,
            'type' => $this->settings[$this->moduleTitle . '_payment'][$payment_code],
            'amount' => $amount
        );

        $payment['order'] = array(
            'externalId' => $order_id
        );

        $this->retailcrmApiClient->ordersPaymentCreate($payment);
    }
}
