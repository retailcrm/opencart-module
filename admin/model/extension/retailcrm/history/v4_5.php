<?php

class ModelExtensionRetailcrmHistoryV45 extends Model
{
    protected $createResult;

    private $opencartApiClient;

    public function request()
    {
        $moduleTitle = $this->getModuleTitle();
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('user/api');
        $this->load->model('sale/order');
        $this->load->model('customer/customer');        
        $this->load->model('extension/retailcrm/references');
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
        $this->load->model('localisation/zone');

        $this->load->language('extension/module/retailcrm');

        $settings = $this->model_setting_setting->getSetting($moduleTitle);
        $history = $this->model_setting_setting->getSetting('retailcrm_history');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        $url = isset($settings[$moduleTitle . '_url']) ? $settings[$moduleTitle . '_url'] : null;
        $key = isset($settings[$moduleTitle . '_apikey']) ? $settings[$moduleTitle . '_apikey'] : null;

        if (empty($url) || empty($key)) {
            $this->log->addNotice('You need to configure retailcrm module first.');
            return false;
        }

        $this->opencartApiClient = new OpencartApiClient($this->registry);

        $crm = new RetailcrmProxy(
            $settings[$moduleTitle . '_url'],
            $settings[$moduleTitle . '_apikey'],
            DIR_SYSTEM . 'storage/logs/retailcrm.log',
            $settings[$moduleTitle . '_apiversion']
        );

        $lastRun = !empty($history['retailcrm_history'])
            ? new DateTime($history['retailcrm_history'])
            : new DateTime(date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s')))));

        $packsOrders = $crm->ordersHistory(array(
            'startDate' => $lastRun->format('Y-m-d H:i:s'),
        ), 1, 100);
        $packsCustomers = $crm->customersHistory(array(
            'startDate' => $lastRun->format('Y-m-d H:i:s'),
        ), 1, 100);
        if(!$packsOrders->isSuccessful() && count($packsOrders->history) <= 0 && !$packsCustomers->isSuccessful() && count($Customers->history) <= 0)
            return false;
        
        $orders = RetailcrmHistoryHelper::assemblyOrder($packsOrders->history);
        $customers = RetailcrmHistoryHelper::assemblyCustomer($packsCustomers->history);

        $generatedAt = $packsOrders['generatedAt'];

        $this->subtotalSettings = $this->model_setting_setting->getSetting('sub_total');
        $this->totalSettings = $this->model_setting_setting->getSetting('total');
        $this->shippingSettings = $this->model_setting_setting->getSetting('shipping');

        $this->delivery = array_flip($settings[$moduleTitle . '_delivery']);
        $this->payment = array_flip($settings[$moduleTitle . '_payment']);
        $this->status = array_flip($settings[$moduleTitle . '_status']);

        $this->ocPayment = $this->model_extension_retailcrm_references
            ->getOpercartPaymentTypes();

        $this->ocDelivery = $settings[$moduleTitle . '_delivery'];
            
        $this->zones = $this->model_localisation_zone->getZones();

        $updatedOrders = array();
        $newOrders = array();

        foreach ($orders as $order) {

            if (isset($order['deleted'])) continue;

            if (isset($order['externalId'])) {
                $updatedOrders[] = $order['id'];
            } else {
                $newOrders[] = $order['id'];
            }
        }

        unset($orders);

        $updateCustomers = array();

        foreach ($customers as $customer) {

            if (isset($customer['deleted'])) continue;

            if (isset($customer['externalId'])) {
                $updateCustomers[] = $customer['id'];
            }
        }

        unset($customers);

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

        if (!empty($updateCustomers)) {
            $customers = $crm->customersList($filter = array('ids' => $updateCustomers));
            if ($customers) {
                $this->updateCustomers($customers['customers']);
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

            if ($order['payments']) {
                foreach ($order['payments'] as $orderPayment) {
                    if (isset($orderPayment['externalId'])) {
                        $payment = $orderPayment;
                    }
                }

                if (!isset($payment) && count($order['payments']) == 1) {
                    $payment = end($order['payments']);
                }
            }

            $data = array();

            $data['store_id'] = $store == null ? 0 : $store;
            $data['customer'] = $order['firstName'];
            $data['customer_id'] = (!empty($order['customer']['externalId'])) ? $order['customer']['externalId'] : 0;
            $data['customer_group_id'] = 1;
            $data['firstname'] = $order['firstName'];
            $data['lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['email'] = $order['email'];
            $data['telephone'] = (!empty($order['phone'])) ? $order['phone'] : '';
            $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';
            $data['fax'] = '';

            $data['payment_address'] = '0';
            $data['payment_firstname'] = $order['firstName'];
            $data['payment_lastname'] = (!empty($order['lastName'])) ? $order['lastName'] : ' ';
            $data['payment_address_1'] = isset($order['customer']['address']) ? $order['customer']['address']['text'] : '';
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

            if (isset($payment)) {
                $data['payment'] = $this->payment[$payment['type']];
                $data['payment_method'] = $this->ocPayment[$data['payment']];
                $data['payment_code'] = $this->payment[$payment['type']];
            } else {
                $this->load->model('sale/order');
                $order_data = $this->model_sale_order->getOrder($order['externalId']);
                $data['payment_method'] = $order_data['payment_method'];
                $data['payment_code'] = $order_data['payment_code'];
            }

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
                //$product = $this->model_catalog_product->getProduct($item['offer']['externalId']);
                $productId = $item['offer']['externalId'];
                $options = array();
                if(mb_strpos($item['offer']['externalId'], '#') > 1) {
                    $offer = explode('#', $item['offer']['externalId']);
                    $productId = $offer[0];
                    $optionsFromCRM = explode('_', $offer[1]);

                    foreach($optionsFromCRM as $optionFromCRM) {
                        $optionData = explode('-', $optionFromCRM);
                        $productOptionId = $optionData[0];
                        $optionValueId = $optionData[1];

                        $productOptions = $this->model_catalog_product->getProductOptions($productId);

                        foreach($productOptions as $productOption) {
                            if($productOptionId == $productOption['product_option_id']) {
                                foreach($productOption['product_option_value'] as $productOptionValue) {
                                    if($productOptionValue['option_value_id'] == $optionValueId) {
                                        $options[$productOptionId] = $productOptionValue['product_option_value_id'];
                                    }
                                }
                            }
                        }
                    }
                }
                $data['order_product'][] = array(
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'option' => $options
                );
            }

            $deliveryCost = !empty($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

            if(isset($order['discount']) && $order['discount'] > 0) {
                $orderTotals = $this->model_sale_order->getOrderTotals($order['externalId']);
                foreach($orderTotals as $orderTotal) {
                    if($orderTotal['code'] == 'coupon') {
                        $data['order_total'][] = $orderTotal;
                    }
                }
            }
            
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

            $this->opencartApiClient->editOrder($order['externalId'], $data);
        }
    }

    protected function createOrders($orders)
    {   
        $customersIdsFix = array();
        $ordersIdsFix = array();

        foreach ($orders as $order) {
            $store = $this->config->get('config_store_id');

            if ($order['payments']) {
                $payment = end($order['payments']);
            }

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

                
                $this->model_customer_customer->addCustomer($cData);
                
                if (!empty($order['email'])) {
                    $tryToFind = $this->model_customer_customer->getCustomerByEmail($order['email']);
                    $customer_id = $tryToFind['customer_id'];
                } else {
                    $last = $this->model_customer_customer->getCustomers($data = array('order' => 'DESC', 'limit' => 1));
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

            if (isset($payment)) {
                $data['payment'] = $this->payment[$payment['type']];
                $data['payment_method'] = $this->ocPayment[$data['payment']];
                $data['payment_code'] = $this->payment[$payment['type']];
            }
            
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

            $this->opencartApiClient->addOrder($data);

            $last = $this->model_sale_order->getOrders($data = array('order' => 'DESC', 'limit' => 1, 'start' => 0));

            $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $last[0]['order_id']);
        }

        return array('customers' => $customersIdsFix, 'orders' => $ordersIdsFix);
    }

    protected function updateCustomers($customers)
    {   
        foreach ($customers as $customer) {
            
            $customer_id = $customer['externalId'];
            $customerData = $this->model_customer_customer->getCustomer($customer_id);
             
            $customerData['firstname'] = $customer['firstName'];
            $customerData['lastname'] = $customer['lastName'];
            $customerData['email'] = $customer['email'];
            $customerData['telephone'] = $customer['phones'][0]['number'];
                
            $customerAddress = $this->model_customer_customer->getAddress($customerData['address_id']);
           
            $customerAddress['firstname'] = $customer['firstName'];
            $customerAddress['lastname'] = $customer['lastName'];
            $customerAddress['address_1'] = $customer['address']['text'];
            $customerAddress['city'] = $customer['address']['city'];
            $customerAddress['postcode'] = $customer['address']['index'];
            $customerData['address'] = array($customerAddress);

            $this->model_customer_customer->editCustomer($customer_id, $customerData);
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
