<?php

class ModelRetailcrmOrder extends Model {
    public function uploadToCrm($orders) {
        $this->load->model('catalog/product');

        $this->load->model('setting/setting');
        $this->settings = $this->model_setting_setting->getSetting('retailcrm');

        if(empty($orders))
            return false;
        if(empty($this->settings['retailcrm_url']) || empty($this->settings['retailcrm_apikey']))
            return false;

        require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

        $this->retailcrmApi = new RetailcrmProxy(
            $this->settings['retailcrm_url'],
            $this->settings['retailcrm_apikey'],
            $this->setLogs()
        );
        
        $ordersToCrm = array();

        foreach($orders as $order) {
            $ordersToCrm[] = $this->process($order);
        }

        $chunkedOrders = array_chunk($ordersToCrm, 50);

        foreach($chunkedOrders as $ordersPart) {
            $this->retailcrmApi->ordersUpload($ordersPart);
        }
    }

    private function process($order_data) {
        $order = array();

        $payment_code = $order_data['payment_code'];
        $delivery_code = $order_data['shipping_code'];

        $order['externalId'] = $order_data['order_id'];
        $order['firstName'] = $order_data['firstname'];
        $order['lastName'] = $order_data['lastname'];
        $order['phone'] = $order_data['telephone'];
        $order['customerComment'] = $order_data['comment'];

        if(!empty($order_data['email'])) {
            $order['email'] = $order_data['email'];
        }

        $order['customer']['externalId'] = $order_data['customer_id'];

        $deliveryCost = 0;
        $orderTotals = isset($order_data['totals']) ? $order_data['totals'] : $order_data['order_total'] ;

        foreach ($orderTotals as $totals) {
            if ($totals['code'] == 'shipping') {
                $deliveryCost = $totals['value'];
            }
        }

        $order['createdAt'] = $order_data['date_added'];
        $order['paymentType'] = $this->settings['retailcrm_payment'][$payment_code];

        $country = (isset($order_data['shipping_country'])) ? $order_data['shipping_country'] : '' ;

        $order['delivery'] = array(
            'code' => !empty($delivery_code) ? $this->settings['retailcrm_delivery'][$delivery_code] : '',
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

                foreach($product['option'] as $option) {
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

            $order['items'][] = array(
                'offer' => array(
                    'externalId' => !empty($offerId) ? $product['product_id'].'#'.$offerId : $product['product_id']
                ),
                'productName' => $product['name'],
                'initialPrice' => $product['price'],
                'quantity' => $product['quantity'],
            );
        }

        if (isset($order_data['order_status_id']) && $order_data['order_status_id'] > 0) {
            $order['status'] = $this->settings['retailcrm_status'][$order_data['order_status_id']];
        }

        return $order;
    }

    private function setLogs()
    {
        if (version_compare(VERSION, '2.1', '>')) {
            $logs = DIR_SYSTEM . 'storage/logs/retailcrm.log';
        } else {
            $logs = DIR_SYSTEM . 'logs/retailcrm.log';
        }

        return $logs;
    }
}
