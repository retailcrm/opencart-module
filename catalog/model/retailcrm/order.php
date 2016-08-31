<?php

class ModelRetailcrmOrder extends Model {

    public function sendToCrm($order_data, $order_id)
    {
        if(isset($this->request->post['fromApi'])) return;

        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                DIR_SYSTEM . 'logs/retailcrm.log'
            );

            $order = array();

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

            $order['externalId'] = $order_id;
            $order['firstName'] = $order_data['firstname'];
            $order['lastName'] = $order_data['lastname'];
            $order['email'] = $order_data['email'];
            $order['phone'] = $order_data['telephone'];
            $order['customerComment'] = $order_data['comment'];

            $deliveryCost = 0;
            $altTotals = isset($order_data['order_total']) ? $order_data['order_total'] : "";
            $orderTotals = isset($order_data['totals']) ? $order_data['totals'] : $altTotals ;

            if (!empty($orderTotals)) {
                foreach ($orderTotals as $totals) {
                    if ($totals['code'] == 'shipping') {
                        $deliveryCost = $totals['value'];
                    }
                }
            }

            $order['createdAt'] = date('Y-m-d H:i:s');

            $payment_code = $order_data['payment_code'];
            $order['paymentType'] = $settings['retailcrm_payment'][$payment_code];

            if(!isset($order_data['shipping_iso_code_2']) && isset($order_data['shipping_country_id'])) {
                $this->load->model('localisation/country');
                $shipping_country = $this->model_localisation_country->getCountry($order_data['shipping_country_id']);
                $order_data['shipping_iso_code_2'] = $shipping_country['iso_code_2'];
            }

            $delivery_code = $order_data['shipping_code'];
            $order['delivery'] = array(
                'code' => !empty($delivery_code) ? $settings['retailcrm_delivery'][$delivery_code] : '',
                'cost' => $deliveryCost,
                'address' => array(
                    'index' => $order_data['shipping_postcode'],
                    'city' => $order_data['shipping_city'],
                    'countryIso' => $order_data['shipping_iso_code_2'],
                    'region' => $order_data['shipping_zone'],
                    'text' => implode(', ', array(
                        $order_data['shipping_postcode'],
                        (isset($order_data['shipping_country'])) ? $order_data['shipping_country'] : '',
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

                    foreach($product['option'] as $option) {
                        if(!in_array($option['type'], $offerOptions)) continue;
                        $options[$option['product_option_id']] = $option['option_value_id'];
                    }

                    ksort($options);

                    $offerId = array();
                    foreach($options as $optionKey => $optionValue) {
                        $offerId[] = $optionKey.'-'.$optionValue;
                    }
                    $offerId = implode('_', $offerId);
                }


                $order['items'][] = array(
                    'productId' => !empty($offerId) ? $product['product_id'].'#'.$offerId : $product['product_id'],
                    'productName' => $product['name'],
                    'initialPrice' => $product['price'],
                    'quantity' => $product['quantity'],
                );
            }

            if (isset($order_data['order_status_id']) && $order_data['order_status_id'] > 0) {
                $order['status'] = $settings['retailcrm_status'][$order_data['order_status_id']];
            }

            $this->retailcrm->ordersCreate($order);
        }
    }

    public function changeInCrm($order_data, $order_id)
    {
        if(isset($this->request->post['fromApi'])) return;

        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                DIR_SYSTEM . 'logs/retailcrm.log'
            );

            $order = array();

            $payment_code = $order_data['payment_code'];
            $delivery_code = $order_data['shipping_code'];

            $order['externalId'] = $order_id;
            $order['firstName'] = $order_data['firstname'];
            $order['lastName'] = $order_data['lastname'];
            $order['email'] = $order_data['email'];
            $order['phone'] = $order_data['telephone'];
            $order['customerComment'] = $order_data['comment'];

            $deliveryCost = 0;
            $orderTotals = isset($order_data['totals']) ? $order_data['totals'] : $order_data['order_total'] ;

            foreach ($orderTotals as $totals) {
                if ($totals['code'] == 'shipping') {
                    $deliveryCost = $totals['value'];
                }
            }

            $order['createdAt'] = date('Y-m-d H:i:s');
            $order['paymentType'] = $settings['retailcrm_payment'][$payment_code];

            $country = (isset($order_data['shipping_country'])) ? $order_data['shipping_country'] : '' ;

            $order['delivery'] = array(
                'code' => !empty($delivery_code) ? $settings['retailcrm_delivery'][$delivery_code] : '',
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

                    foreach($product['option'] as $option) {
                        if(!in_array($option['type'], $offerOptions)) continue;
                        $options[$option['product_option_id']] = $option['option_value_id'];
                    }

                    ksort($options);

                    $offerId = array();
                    foreach($options as $optionKey => $optionValue) {
                        $offerId[] = $optionKey.'-'.$optionValue;
                    }
                    $offerId = implode('_', $offerId);
                }


                $order['items'][] = array(
                    'productId' => !empty($offerId) ? $product['product_id'].'#'.$offerId : $product['product_id'],
                    'productName' => $product['name'],
                    'initialPrice' => $product['price'],
                    'quantity' => $product['quantity'],
                );
            }

            if (isset($order_data['order_status_id']) && $order_data['order_status_id'] > 0) {
                $order['status'] = $settings['retailcrm_status'][$order_data['order_status_id']];
            }

            $this->retailcrm->ordersEdit($order);
        }
    }
}
