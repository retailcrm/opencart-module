<?php

class ModelRetailcrmHistory extends Model
{
    protected $createResult;

    public function request()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('sale/order');
        if (version_compare(VERSION, '2.1.0.0', '>=')) {
            $this->load->model('customer/customer');
        } else {
            $this->load->model('sale/customer');
        }
        $this->load->model('retailcrm/references');
        $this->load->model('catalog/product');
        $this->load->model('localisation/zone');

        $this->load->language('module/retailcrm');

        $settings = $this->model_setting_setting->getSetting('retailcrm');
        $history = $this->model_setting_setting->getSetting('retailcrm_history');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        $url = isset($settings['retailcrm_url']) ? $settings['retailcrm_url'] : null;
        $key = isset($settings['retailcrm_apikey']) ? $settings['retailcrm_apikey'] : null;

        if (empty($url) || empty($key)) {
            $this->log->addNotice('You need to configure retailcrm module first.');
            return false;
        }

        $crm = new RetailcrmProxy(
            $settings['retailcrm_url'],
            $settings['retailcrm_apikey'],
            DIR_SYSTEM . 'logs/retailcrm.log'
        );

        $lastRun = !empty($history['retailcrm_history'])
            ? new DateTime($history['retailcrm_history'])
            : new DateTime(date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s')))));

        $orders = $crm->ordersHistory($lastRun);
        $generatedAt = $orders['generatedAt'];

        $this->subtotalSettings = $this->model_setting_setting->getSetting('sub_total');
        $this->totalSettings = $this->model_setting_setting->getSetting('total');
        $this->shippingSettings = $this->model_setting_setting->getSetting('shipping');

        $this->delivery = array_flip($settings['retailcrm_delivery']);
        $this->payment = array_flip($settings['retailcrm_payment']);
        $this->status = array_flip($settings['retailcrm_status']);

        $this->ocPayment = $this->model_retailcrm_references
            ->getOpercartPaymentTypes();

        $this->ocDelivery = $this->model_retailcrm_references
            ->getOpercartDeliveryTypes();

        $this->zones = $this->model_localisation_zone->getZones();

        $updatedOrders = array();
        $newOrders = array();

        foreach ($orders['orders'] as $order) {

            if (isset($order['deleted'])) continue;

            if (isset($order['externalId'])) {
                $updatedOrders[] = $order['id'];
            } else {
                $newOrders[] = $order['id'];
            }
        }

        unset($orders);

        if (!empty($newOrders)) {
            $orders = $crm->ordersList($filter = array('ids' => $newOrders));
            if ($orders) {
                $this->createResult = $this->createOrders($orders['orders']);
            }
        }

        if (!empty($updatedOrders)) {
            $orders = $crm->ordersList($filter = array('ids' => $updatedOrders));
            if ($orders) {
                $this->updateOrders($orders['orders']);
            }
        }

        $this->model_setting_setting->editSetting('retailcrm_history', array('retailcrm_history' => $generatedAt));

        if (!empty($this->createResult['customers'])) {
            $crm->customersFixExternalIds($this->createResult['customers']);
        }

        if (!empty($this->createResult['orders'])) {
            $crm->ordersFixExternalIds($this->createResult['orders']);
        }

    }

    protected function updateOrders($orders)
    {
        foreach ($orders as $order) {
            $store = $this->config->get('config_store_id');

            $data = array();

            $data['store_id'] = $store == null ? 0 : $store;
            $data['customer'] = $order['firstName'];
            $data['customer_id'] = (!empty($order['customer']['externalId'])) ? $order['customer']['externalId'] : 0;
            $data['customer_group_id'] = 1;
            $data['firstname'] = $order['firstName'];
            $data['lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['email'] = $order['email'];
            $data['telephone'] = (!empty($order['phone']['number'])) ? $order['phone']['number'] : ' ';
            $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';
            $data['fax'] = '';

            $data['payment_address'] = '0';
            $data['payment_firstname'] = $order['firstName'];
            $data['payment_lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['payment_address_1'] = $order['customer']['address']['text'];
            $data['payment_address_2'] = '';
            $data['payment_company'] = '';
            $data['payment_company_id'] = '';
            $data['payment_city'] = !empty($order['customer']['address']['city']) ? $order['customer']['address']['city'] : $order['delivery']['address']['city'];
            $data['payment_postcode'] = !empty( $order['customer']['address']['index'] ) ? $order['customer']['address']['index'] : $order['delivery']['address']['index'];

            $region = '';

            if (is_int($order['delivery']['address']['region'])) {
                $region = $order['delivery']['address']['region'];
            } else {
                foreach ($this->zones as $zone) {
                    if ($order['delivery']['address']['region'] == $zone['name']) {
                        $region = $zone['zone_id'];
                    }
                }
            }

            $data['payment_country_id'] = !empty($order['delivery']['address']['country']) ? $order['delivery']['address']['country'] : 0;
            $data['payment_zone_id'] = !empty($order['delivery']['address']['region']) ? $order['delivery']['address']['region'] : $region;

            $data['shipping_country_id'] = !empty($order['delivery']['address']['country']) ? $order['delivery']['address']['country'] : 0;
            $data['shipping_zone_id'] = $region;

            $data['shipping_address'] = '0';
            $data['shipping_firstname'] = $order['firstName'];
            $data['shipping_lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['shipping_address_1'] = $order['delivery']['address']['text'];
            $data['shipping_address_2'] = '';
            $data['shipping_company'] = '';
            $data['shipping_company_id'] = '';
            $data['shipping_city'] = $order['delivery']['address']['city'];
            $data['shipping_postcode'] = $order['delivery']['address']['index'];

            $data['shipping'] = $this->delivery[$order['delivery']['code']];
            $data['shipping_method'] = $this->ocDelivery[$data['shipping']];
            $data['shipping_code'] = $this->delivery[$order['delivery']['code']];
            $data['payment'] = $this->payment[$order['paymentType']];
            $data['payment_method'] = $this->ocPayment[$data['payment']];
            $data['payment_code'] = $this->payment[$order['paymentType']];

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

            foreach ($order['items'] as $item) {
                $product = $this->model_catalog_product->getProduct($item['offer']['externalId']);
                $data['order_product'][] = array(
                    'product_id' => $item['offer']['externalId'],
                    'name' => $item['offer']['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['initialPrice'],
                    'total' => $item['initialPrice'] * $item['quantity'],
                    'model' => $product['model'],

                    // this data will not retrive from crm
                    'order_product_id' => '',
                    'tax' => 0,
                    'reward' => 0
                );
            }

            $deliveryCost = !empty($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

            $data['order_total'] = array(
                array(
                    'order_total_id' => '',
                    'code' => 'sub_total',
                    'title' => $this->language->get('product_summ'),
                    'value' => $order['summ'],
                    'text' => $order['summ'],
                    'sort_order' => $this->subtotalSettings['sub_total_sort_order']
                ),
                array(
                    'order_total_id' => '',
                    'code' => 'shipping',
                    'title' => $this->ocDelivery[$data['shipping_code']],
                    'value' => $deliveryCost,
                    'text' => $deliveryCost,
                    'sort_order' => $this->shippingSettings['shipping_sort_order']
                ),
                array(
                    'order_total_id' => '',
                    'code' => 'total',
                    'title' => $this->language->get('column_total'),
                    'value' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'text' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'sort_order' => $this->totalSettings['total_sort_order']
                )
            );

            $data['fromApi'] = true;

            if (array_key_exists($order['status'], $this->status)) {
                $data['order_status_id'] = $this->status[$order['status']];
            } else {
                $tmpOrder = $this->model_sale_order->getOrder($order['externalId']);
                $data['order_status_id'] = $tmpOrder['order_status_id'];
            }

            $this->model_sale_order->editOrder($order['externalId'], $data);
        }
    }

    protected function createOrders($orders)
    {
        $customersIdsFix = array();
        $ordersIdsFix = array();

        foreach ($orders as $order) {
            $store = $this->config->get('config_store_id');

            $customer_id = (!empty($order['customer']['externalId']))
                ? $order['customer']['externalId']
                : 0;

            $data = array();

            if ($customer_id == 0) {
                $cData = array(
                    'store_id' => 0,
                    'customer_group_id' => '1',
                    'firstname' => $order['firstName'],
                    'lastname' => (!empty($order['lastName'])) ? $order['lastName'] : ' ',
                    'email' => $order['email'],
                    'telephone' => (!empty($order['customer']['phones'][0]['number']) ) ? $order['customer']['phones'][0]['number'] : ' ',
                    'fax' => '',
                    'newsletter' => 0,
                    'password' => 'tmppass',
                    'status' => 1,
                    'address' => array(
                        array(
                            'firstname' => $order['firstName'],
                            'lastname' => (!empty($order['lastName'])) ? $order['lastName'] : ' ',
                            'address_1' => $order['customer']['address']['text'],
                            'address_2' => ' ',
                            'city' => !empty($order['customer']['address']['city']) ? $order['customer']['address']['city'] : $order['delivery']['address']['city'],
                            'postcode' => isset($order['customer']['address']['index']) ? $order['customer']['address']['index'] : $order['delivery']['address']['index'],
                            'tax_id' => '1',
                            'company' => '',
                            'company_id' => '',
                            'zone_id' => '0',
                            'country_id' => 0
                        )
                    ),
                );

                if (version_compare(VERSION, '2.1.0.0', '>=')) {
                    $this->model_customer_customer->addCustomer($cData);
                } else {
                    $this->model_sale_customer->addCustomer($cData);
                }

                if (!empty($order['email'])) {
                    if (version_compare(VERSION, '2.1.0.0', '>=')) {
                        $tryToFind = $this->model_customer_customer->getCustomerByEmail($order['email']);
                    } else {
                        $tryToFind = $this->model_sale_customer->getCustomerByEmail($order['email']);
                    }
                    $customer_id = $tryToFind['customer_id'];
                } else {
                    if (version_compare(VERSION, '2.1.0.0', '>=')) {
                        $last = $this->model_customer_customer->getCustomers($data = array('order' => 'DESC', 'limit' => 1));
                    } else {
                        $last = $this->model_sale_customer->getCustomers($data = array('order' => 'DESC', 'limit' => 1));
                    }
                    $customer_id = $last[0]['customer_id'];
                }

                $customersIdsFix[] = array('id' => $order['customer']['id'], 'externalId' => (int)$customer_id);
            }

            $data['store_id'] = $store == null ? 0 : $store;
            $data['customer'] = $order['firstName'];
            $data['customer_id'] = $customer_id;
            $data['customer_group_id'] = 1;
            $data['firstname'] = $order['firstName'];
            $data['lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['email'] = $order['email'];
            $data['telephone'] = (!empty($order['customer']['phones'][0]['number'])) ? $order['customer']['phones'][0]['number'] : ' ';
            $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';
            $data['fax'] = '';
            $data['payment_address'] = '0';
            $data['payment_firstname'] = $order['firstName'];
            $data['payment_lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['payment_address_1'] = $order['customer']['address']['text'];
            $data['payment_address_2'] = '';
            $data['payment_company'] = '';
            $data['payment_company_id'] = '';
            $data['payment_city'] = !empty($order['customer']['address']['city']) ? $order['customer']['address']['city'] : $order['delivery']['address']['city'];
            $data['payment_postcode'] = !empty($order['customer']['address']['index']) ? $order['customer']['address']['index'] : $order['delivery']['address']['index'];


            $region = '';

            if (!empty($order['delivery']['address']['region']) && is_int($order['delivery']['address']['region'])) {
                $region = $order['delivery']['address']['region'];
            } else {
                foreach ($this->zones as $zone) {
                    if ($order['delivery']['address']['region'] == $zone['name']) {
                        $region = $zone['zone_id'];
                    }
                }
            }

            $data['payment_country_id'] = !empty($order['delivery']['address']['country']) ? $order['delivery']['address']['country'] : 0;
            $data['payment_zone_id'] = !empty($order['delivery']['address']['region']) ? $order['delivery']['address']['region'] : $region;
            $data['shipping_country_id'] = !empty($order['delivery']['address']['country']) ? $order['delivery']['address']['country'] : 0;
            $data['shipping_zone_id'] = $region;
            $data['shipping_address'] = '0';
            $data['shipping_firstname'] = $order['firstName'];
            $data['shipping_lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['shipping_address_1'] = $order['delivery']['address']['text'];
            $data['shipping_address_2'] = '';
            $data['shipping_company'] = '';
            $data['shipping_company_id'] = '';
            $data['shipping_city'] = $order['delivery']['address']['city'];
            $data['shipping_postcode'] = $order['delivery']['address']['index'];

            $data['shipping'] = $this->delivery[$order['delivery']['code']];
            $data['shipping_method'] = $this->ocDelivery[$data['shipping']];
            $data['shipping_code'] = $this->delivery[$order['delivery']['code']];
            $data['payment'] = $this->payment[$order['paymentType']];
            $data['payment_method'] = $this->ocPayment[$data['payment']];
            $data['payment_code'] = $this->payment[$order['paymentType']];

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

            foreach ($order['items'] as $item) {
                $product = $this->model_catalog_product->getProduct($item['offer']['externalId']);
                $data['order_product'][] = array(
                    'product_id' => $item['offer']['externalId'],
                    'name' => $item['offer']['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['initialPrice'],
                    'total' => $item['initialPrice'] * $item['quantity'],
                    'model' => $product['model'],

                    // this data will not retrive from crm
                    'order_product_id' => '',
                    'tax' => 0,
                    'reward' => 0
                );
            }

            $deliveryCost = !empty($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

            $data['order_total'] = array(
                array(
                    'order_total_id' => '',
                    'code' => 'sub_total',
                    'title' => $this->language->get('product_summ'),
                    'value' => $order['summ'],
                    'text' => $order['summ'],
                    'sort_order' => $this->subtotalSettings['sub_total_sort_order']
                ),
                array(
                    'order_total_id' => '',
                    'code' => 'shipping',
                    'title' => $this->ocDelivery[$data['shipping_code']],
                    'value' => $deliveryCost,
                    'text' => $deliveryCost,
                    'sort_order' => $this->shippingSettings['shipping_sort_order']
                ),
                array(
                    'order_total_id' => '',
                    'code' => 'total',
                    'title' => $this->language->get('column_total'),
                    'value' => !empty($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'text' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'sort_order' => $this->totalSettings['total_sort_order']
                )
            );

            $data['fromApi'] = true;
            $data['order_status_id'] = 1;

            $this->model_sale_order->addOrder($data);

            $last = $this->model_sale_order->getOrders($data = array('order' => 'DESC', 'limit' => 1, 'start' => 0));

            $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $last[0]['order_id']);
        }

        return array('customers' => $customersIdsFix, 'orders' => $ordersIdsFix);
    }
}
