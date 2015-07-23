<?php

class ModelRetailcrmHistory extends Model
{
    public function request()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('sale/order');
        $this->load->model('sale/customer');
        $this->load->model('retailcrm/tools');
        $this->load->model('catalog/product');
        $this->load->model('localisation/zone');

        $this->load->language('module/retailcrm');

        $settings = $this->model_setting_setting->getSetting('retailcrm');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        if (!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $crm = new ApiHelper($settings);
            $orders = $crm->ordersHistory();
            $ordersIdsFix = array();
            $customersIdsFix = array();
            $subtotalSettings = $this->model_setting_setting->getSetting('sub_total');
            $totalSettings = $this->model_setting_setting->getSetting('total');
            $shippingSettings = $this->model_setting_setting->getSetting('shipping');

            $delivery = array_flip($settings['retailcrm_delivery']);
            $payment = array_flip($settings['retailcrm_payment']);
            $status = array_flip($settings['retailcrm_status']);

            $ocPayment = $this->model_retailcrm_tools->getOpercartPaymentTypes();
            $ocDelivery = $this->model_retailcrm_tools->getOpercartDeliveryMethods();

            $zones = $this->model_localisation_zone->getZones();

            foreach ($orders as $order) {

                if (empty($order['deleted'])) {

                    $data = array();

                    $customer_id = (!empty($order['customer']['externalId']))
                        ? $order['customer']['externalId']
                        : ''
                    ;

                    if (isset($order['externalId'])) {
                        /*
                         * opercart developers believe that to remove all
                         * products from the order during the editing is a good
                         * idea...
                         *
                         * so we have to get order data from crm
                         *
                         */
                        $order = $crm->getOrder($order['externalId']);
                    } else {
                        if ($customer_id == '') {
                            $cData = array(
                                'customer_group_id' => '1',
                                'firstname' => $order['customer']['firstName'],
                                'lastname' => (isset($order['customer']['lastName']))
                                    ? $order['customer']['lastName']
                                    : ' '
                            ,
                                'email' => $order['customer']['email'],
                                'telephone' => (isset($order['customer']['phones'][0]['number']))
                                    ? $order['customer']['phones'][0]['number']
                                    : ' '
                            ,
                                'newsletter' => 0,
                                'password' => 'tmppass',
                                'status' => 1,
                                'address' => array(
                                    'firstname' => $order['customer']['firstName'],
                                    'lastname' => (isset($order['customer']['lastName']))
                                        ? $order['customer']['lastName']
                                        : ' '
                                ,
                                    'address_1' => $order['customer']['address']['text'],
                                    'city' => isset($order['customer']['address']['city'])
                                        ? $order['customer']['address']['city']
                                        : $order['delivery']['address']['city']
                                ,
                                    'postcode' => isset($order['customer']['address']['index'])
                                        ? $order['customer']['address']['index']
                                        : $order['delivery']['address']['index']
                                ,
                                ),
                                'tax_id' => '',
                                'zone_id' => '',
                            );

                            $this->model_sale_customer->addCustomer($cData);

                            if (isset($order['customer']['email']) && $order['customer']['email'] != '') {
                                $tryToFind = $this->model_sale_customer->getCustomerByEmail($order['customer']['email']);
                                $customer_id = $tryToFind['customer_id'];
                            } else {
                                $last = $this->model_sale_customer->getCustomers(
                                    $data = array('order' => 'DESC', 'limit' => 1)
                                );
                                $customer_id = $last[0]['customer_id'];
                            }

                            $customersIdsFix[] = array('id' => $order['customer']['id'], 'externalId' => (int)$customer_id);
                        }
                    }

                    /*
                     * Build order data
                     */

                    $data['store_id'] = ($this->config->get('config_store_id') == null)
                        ? 0
                        : $this->config->get('config_store_id')
                    ;
                    $data['customer'] = $order['customer']['firstName'];
                    $data['customer_id'] = $customer_id;
                    $data['customer_group_id'] = 1;
                    $data['firstname'] = $order['firstName'];
                    $data['lastname'] = (isset($order['lastName'])) ? $order['lastName'] : ' ';
                    $data['email'] = $order['customer']['email'];
                    $data['telephone'] = (isset($order['customer']['phones'][0]['number']))
                        ? $order['customer']['phones'][0]['number']
                        : ' '
                    ;

                    $data['comment'] = isset($order['customerComment']) ? $order['customerComment'] : '';
                    $data['fax'] = '';

                    $data['payment_address'] = '0';
                    $data['payment_firstname'] = $order['firstName'];
                    $data['payment_lastname'] = (isset($order['lastName'])) ? $order['lastName'] : ' ';
                    $data['payment_address_1'] = $order['customer']['address']['text'];
                    $data['payment_address_2'] = '';
                    $data['payment_company'] = '';
                    $data['payment_company_id'] = '';
                    $data['payment_city'] = isset($order['customer']['address']['city'])
                        ? $order['customer']['address']['city']
                        : $order['delivery']['address']['city']
                    ;
                    $data['payment_postcode'] = isset($order['customer']['address']['index'])
                        ? $order['customer']['address']['index']
                        : $order['delivery']['address']['index']
                    ;

                    /*
                     * Country & zone id detection
                     */

                    $country = 0;
                    $region = '';

                    if(is_int($order['delivery']['address']['region'])) {
                        $region = $order['delivery']['address']['region'];
                    } else {
                        foreach($zones as $zone) {
                            if($order['delivery']['address']['region'] == $zone['name']) {
                                $region = $zone['zone_id'];
                            }
                        }

                    }

                    $data['payment_country_id'] = isset($order['customer']['address']['country'])
                        ? $order['customer']['address']['country']
                        : $order['delivery']['address']['country']
                    ;
                    $data['payment_zone_id'] = isset($order['customer']['address']['region'])
                        ? $order['customer']['address']['region']
                        : $region
                    ;

                    $data['shipping_country_id'] = $order['delivery']['address']['country'];
                    $data['shipping_zone_id'] = $region;

                    $data['shipping_address'] = '0';
                    $data['shipping_firstname'] = $order['customer']['firstName'];
                    $data['shipping_lastname'] = (isset($order['customer']['lastName']))
                        ? $order['customer']['lastName']
                        : ' '
                    ;
                    $data['shipping_address_1'] = $order['delivery']['address']['text'];
                    $data['shipping_address_2'] = '';
                    $data['shipping_company'] = '';
                    $data['shipping_company_id'] = '';
                    $data['shipping_city'] = $order['delivery']['address']['city'];
                    $data['shipping_postcode'] = $order['delivery']['address']['index'];

                    $data['shipping'] = $delivery[$order['delivery']['code']];
                    $data['shipping_method'] = $ocDelivery[$data['shipping']];
                    $data['shipping_code'] = $delivery[$order['delivery']['code']];
                    $data['payment'] = $payment[$order['paymentType']];
                    $data['payment_method'] = $ocPayment[$data['payment']];
                    $data['payment_code'] = $payment[$order['paymentType']];

                    // this data will not retrive from crm for now
                    $data['tax'] = '';
                    $data['tax_id'] = '';
                    $data['product'] = '';
                    $data['product_id'] = '';
                    $data['reward'] = '';
                    $data['affiliate'] = '';
                    $data['affiliate_id'] = '';
                    $data['payment_tax_id'] = '';
                    $data['order_product_id'] = '';
                    $data['payment_company'] = '';
                    $data['payment_company_id'] = '';
                    $data['company'] = '';
                    $data['company_id'] = '';

                    $data['order_product'] = array();

                    foreach($order['items'] as $item) {
                        $p = $this->model_catalog_product->getProduct($item['offer']['externalId']);
                        $data['order_product'][] = array(
                            'product_id' => $item['offer']['externalId'],
                            'name' => $item['offer']['name'],
                            'quantity' => $item['quantity'],
                            'price' => $item['initialPrice'],
                            'total' => $item['initialPrice'] * $item['quantity'],
                            'model' => $p['model'],

                            // this data will not retrive from crm
                            'order_product_id' => '',
                            'tax' => 0,
                            'reward' => 0
                        );
                    }

                    $deliveryCost = isset($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

                    $data['order_total'] = array(
                        array(
                            'order_total_id' => '',
                            'code' => 'sub_total',
                            'title' => $this->language->get('product_summ'),
                            'value' => $order['summ'],
                            'text' => $order['summ'],
                            'sort_order' => $subtotalSettings['sub_total_sort_order']
                        ),
                        array(
                            'order_total_id' => '',
                            'code' => 'shipping',
                            'title' => $ocDelivery[$data['shipping_code']],
                            'value' => $deliveryCost,
                            'text' => $deliveryCost,
                            'sort_order' => $shippingSettings['shipping_sort_order']
                        ),
                        array(
                            'order_total_id' => '',
                            'code' => 'total',
                            'title' => $this->language->get('column_total'),
                            'value' => isset($order['totalSumm'])
                                ? $order['totalSumm']
                                : $order['summ'] + $deliveryCost
                        ,
                            'text' => isset($order['totalSumm'])
                                ? $order['totalSumm']
                                : $order['summ'] + $deliveryCost
                        ,
                            'sort_order' => $totalSettings['total_sort_order']
                        )
                    );

                    $data['fromApi'] = true;

                    if (isset($order['externalId'])) {
                        if(array_key_exists($order['status'], $status)) {
                            $data['order_status_id'] = $status[$order['status']];
                        } else {
                            $tmpOrder = $this->model_sale_order->getOrder($order['externalId']);
                            $data['order_status_id'] = $tmpOrder['order_status_id'];
                        }

                        $this->model_sale_order->editOrder($order['externalId'], $data);
                    } else {
                        $data['order_status_id'] = 1;
                        $this->model_sale_order->addOrder($data);
                        $last = $this->model_sale_order->getOrders($data = array('order' => 'DESC', 'limit' => 1));
                        $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $last[0]['order_id']);
                    }

                }
            }

            if (!empty($customersIdsFix)) {
                $crm->customerFixExternalIds($customersIdsFix);
            }

            if (!empty($ordersIdsFix)) {
                $crm->orderFixExternalIds($ordersIdsFix);
            }

        } else {
            $this->log->addNotice(
                '['.
                $this->config->get('store_name').
                '] RestApi::orderHistory: you need to configure retailcrm module first.'
            );
        }
    }
}
