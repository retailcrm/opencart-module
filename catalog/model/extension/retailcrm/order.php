<?php

class ModelExtensionRetailcrmOrder extends Model {

    public function sendToCrm($order_data, $order_id)
    {
        if(isset($this->request->post['fromApi'])) return;

        $this->initApi();

        $order = $this->processOrder($order_data, $order_id);

        $customers = $this->retailcrm->customersList(
            array(
                'name' => $order_data['telephone'],
                'email' => $order_data['email']
            ),
            1,
            100
        );

        if($customers) {
            foreach ($customers['customers'] as $customer) {
                $order['customer']['id'] = $customer['id'];
            }
        }

        unset($customers);

        $response = $this->retailcrm->ordersCreate($order);

        if ($this->settings[$this->moduleTitle . '_apiversion'] == 'v5' && $response->isSuccessful()) {
            $this->createPayment($order_data, $order_id);
        }
    }

    public function changeInCrm($order_data, $order_id)
    {
        if(isset($this->request->post['fromApi'])) return;

        $this->initApi();

        $order = $this->processOrder($order_data, $order_id);

        $customers = $this->retailcrm->customersList(
            array(
                'name' => $order_data['telephone'],
                'email' => $order_data['email']
            ),
            1,
            100
        );

        if($customers) {
            foreach ($customers['customers'] as $customer) {
                $order['customer']['id'] = $customer['id'];
            }
        }

        unset($customers);

        $response = $this->retailcrm->ordersEdit($order);

        if ($this->settings[$this->moduleTitle . '_apiversion'] == 'v5' && $response->isSuccessful()) {
            $response_order = $this->retailcrm->ordersGet($order_id);
            if ($response_order->isSuccessful()) {
                $order_info = $response_order['order'];
            }

            foreach ($order_info['payments'] as $payment_data) {
                if (isset($payment_data['externalId']) && $payment_data['externalId'] == $order_id) {
                    $payment = $payment_data;
                }
            }

            if (isset($payment) && $payment['type'] != $this->settings[$this->moduleTitle . '_payment'][$order_data['payment_code']]) {
                $response = $this->retailcrm->ordersPaymentDelete($payment['id']);

                if ($response->isSuccessful()) {
                    $this->createPayment($order_data, $order_id);
                }
            } elseif (isset($payment) && $payment['type'] == $this->settings[$this->moduleTitle . '_payment'][$order_data['payment_code']]) {
                $this->editPayment($order_data, $order_id);
            }
        }
    }

    protected function processOrder($order_data, $order_id)
    {   
        $this->moduleTitle = $this->getModuleTitle();
        $this->load->model('setting/setting');
        $this->load->model('catalog/product');
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);

        $payment_code = $order_data['payment_code'];
        $order['externalId'] = $order_id;
        $order['firstName'] = $order_data['firstname'];
        $order['lastName'] = $order_data['lastname'];
        $order['phone'] = $order_data['telephone'];
        $order['customerComment'] = $order_data['comment'];

        if(!empty($order_data['email'])) {
            $order['email'] = $order_data['email'];
        }
      
        $deliveryCost = 0;
        $orderTotals = isset($order_data['totals']) ? $order_data['totals'] : $order_data['order_total'] ;

        foreach ($orderTotals as $totals) {
            if ($totals['code'] == 'shipping') {
                $deliveryCost = $totals['value'];
            }

            if ($totals['code'] == 'coupon') {
                $couponTotal = abs($totals['value']);
            }
        }

        $order['createdAt'] = $order_data['date_added'];

        if ($this->settings[$this->moduleTitle . '_apiversion'] != 'v5') {
            $order['paymentType'] = $this->settings[$this->moduleTitle . '_payment'][$payment_code];
            if (isset($couponTotal)) $order['discount'] = $couponTotal;
        } else {
            if (isset($couponTotal)) $order['discountManualAmount'] = $couponTotal;
        }

        $country = (isset($order_data['shipping_country'])) ? $order_data['shipping_country'] : '' ;

        if(isset($this->settings[$this->moduleTitle . '_delivery'][$order_data['shipping_code']])) {
            $delivery_code = $order_data['shipping_code'];
        } else {
            $delivery_code = stristr($order_data['shipping_code'], '.', TRUE);
        }

        $order['delivery'] = array(
            'code' => !empty($delivery_code) ? $this->settings[$this->moduleTitle . '_delivery'][$delivery_code] : '',
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

        if(!empty($deliveryCost)){
            $order['delivery']['cost'] = $deliveryCost;
        }
        
        $orderProducts = isset($order_data['products']) ? $order_data['products'] : $order_data['order_product'];
        $offerOptions = array('select', 'radio');

        foreach ($orderProducts as $product) {
            $offerId = '';

            if (!empty($product['option'])) {
                $options = array();

                $productOptions = $this->model_catalog_product->getProductOptions($product['product_id']);

                foreach($product['option'] as $option) {
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

            if (isset($order_data['order_status_id']) && $order_data['order_status_id'] > 0) {
                $order['status'] = $this->settings[$this->moduleTitle . '_status'][$order_data['order_status_id']];
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

        return $order;
    }

    protected function createPayment($order, $order_id)
    {
        $payment_code = $order['payment_code'];

        foreach ($order['totals'] as $total) {
            if ($total['code'] == 'total') $amount = $total['value'];
        }

        $payment = array(
            'externalId' => $order_id,
            'type' => $this->settings[$this->moduleTitle . '_payment'][$payment_code],
            'amount' => $amount
        );

        $payment['order'] = array(
            'externalId' => $order_id
        );

        $this->retailcrm->ordersPaymentCreate($payment);
    }

    protected function editPayment($order, $order_id)
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

        $this->retailcrm->ordersPaymentEdit($payment);
    }

    private function initApi()
    {
        $moduleTitle = $this->getModuleTitle();
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting($moduleTitle);

        if(!empty($settings[$moduleTitle . '_url']) && !empty($settings[$moduleTitle . '_apikey'])) {

            require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

            $this->retailcrm = new RetailcrmProxy(
                $settings[$moduleTitle . '_url'],
                $settings[$moduleTitle . '_apikey'],
                DIR_SYSTEM . 'storage/logs/retailcrm.log',
                $settings[$moduleTitle . '_apiversion']
            );
        }
    }

    private function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }

}
