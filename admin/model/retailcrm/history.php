<?php

require_once 'base_history.php';

class ModelRetailcrmHistory extends ModelRetailcrmBaseHistory
{
    protected $createResult;

    private $opencartApiClient;

    public function request()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('user/api');
        $this->load->model('sale/order');
        if (version_compare(VERSION, '2.1.0.0', '>=')) {
            $this->load->model('customer/customer');
        } else {
            $this->load->model('sale/customer');
        }
        $this->load->model('retailcrm/references');
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
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

        $this->opencartApiClient = new OpencartApiClient($this->registry);

        $crm = new RetailcrmProxy(
            $settings['retailcrm_url'],
            $settings['retailcrm_apikey'],
            $this->setLogs()
        );

        $lastRun = !empty($history['retailcrm_history'])
            ? new DateTime($history['retailcrm_history'])
            : new DateTime(date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s')))));

        $packs = $crm->ordersHistory(array(
            'startDate' => $lastRun->format('Y-m-d H:i:s'),
        ), 1, 100);
        if (!$packs->isSuccessful() && count($packs->history) <= 0) {
            
            return false;
        }

        $orders = RetailcrmHistoryHelper::assemblyOrder($packs->history);

        $generatedAt = $packs['generatedAt'];

        $this->subtotalSettings = $this->model_setting_setting->getSetting('sub_total');
        $this->totalSettings = $this->model_setting_setting->getSetting('total');
        $this->shippingSettings = $this->model_setting_setting->getSetting('shipping');

        $this->delivery = array_flip($settings['retailcrm_delivery']);
        $this->payment = array_flip($settings['retailcrm_payment']);
        $this->status = array_flip($settings['retailcrm_status']);
        $this->settings = $settings;
        $this->ocPayment = $this->model_retailcrm_references
            ->getOpercartPaymentTypes();

        $this->ocDelivery = $this->model_retailcrm_references
            ->getOpercartDeliveryTypes();

        $this->zones = $this->model_localisation_zone->getZones();

        $updatedOrders = array();
        $newOrders = array();

        foreach ($orders as $order) {

            if (isset($order['deleted'])) {
                continue;
            }

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
            $ocOrder = $this->model_sale_order->getOrder($order['externalId']);

            if (isset($order['paymentType'])) {
                $payment['type'] = $order['paymentType'];
            }

            $data = array();

            $mail = isset($order['email']) ? $order['email'] : $order['customer']['email'];
            $phone = isset($order['phone']) ? $order['phone'] : '';

            if (!$phone) {
                $data['telephone'] = $order['customer']['phones'] ? $order['customer']['phones'][0]['number'] : '80000000000';
            } else {
                $data['telephone'] = $phone;
            }

            if (isset($order['customer']['externalId']) && $order['customer']['externalId']) {
                if (version_compare(VERSION, '2.1.0.0', '>=')) {
                    $customer = $this->model_customer_customer->getCustomer($order['customer']['externalId']);
                } else {
                    $customer = $this->model_sale_customer->getCustomer($order['customer']['externalId']);
                }
            }

            $data['customer'] = $order['firstName'];
            $data['customer_id'] = (!empty($order['customer']['externalId'])) ? $order['customer']['externalId'] : 0;
            $data['customer_group_id'] = (isset($customer)) ? $customer['customer_group_id'] : 1;
            $data['firstname'] = $order['firstName'];
            $data['lastname'] = isset($order['lastName']) ? $order['lastName'] : $order['firstName'];
            $data['email'] = $mail ? $mail : uniqid() . '@retailrcm.ru';
            $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';
            $data['payment_address'] = '0';
            $data['payment_firstname'] = $order['firstName'];
            $data['payment_lastname'] = isset($order['lastName']) ? $order['lastName'] : $order['firstName'];
            $data['payment_address_1'] = isset($order['customer']['address']) ? $order['customer']['address']['text'] : '';
            $data['payment_address_2'] = '';
            $data['payment_city'] = !empty($order['customer']['address']['city']) ? $order['customer']['address']['city'] : $order['delivery']['address']['city'];
            $data['payment_postcode'] = !empty( $order['customer']['address']['index'] ) ? $order['customer']['address']['index'] : $order['delivery']['address']['index'];

            $shippingZone = '';

            if (is_int($order['delivery']['address']['region'])) {
                $shippingZone = $order['delivery']['address']['region'];
            } else {
                $shippingZone = $this->getZoneByName($order['delivery']['address']['region']);

                if ($shippingZone) {
                    $shipping_zone_id = $shippingZone['zone_id'];
                } else {
                    $shipping_zone_id = 0;
                }
            }

            if (isset($order['customer']['address']['region'])) {
                $paymentZone = $this->getZoneByName($order['customer']['address']['region']);

                if ($paymentZone) {
                    $payment_zone_id = $paymentZone['zone_id'];
                } else {
                    $payment_zone_id = 0;
                }
            }

            if (isset($order['countryIso']) && !empty($order['countryIso'])) {
                $shippingCountry = $this->getCountryByIsoCode($order['countryIso']);
            }

            if (isset($order['customer']['address']['countryIso']) && !empty($order['customer']['address']['countryIso'])) {
                $paymentCountry = $this->getCountryByIsoCode($order['customer']['address']['countryIso']);
            } else {
                $paymentCountry = $this->getCountryByIsoCode($order['countryIso']);
            }

            $delivery = isset($order['delivery']['code']) ? $order['delivery']['code'] : null;
            $data['payment_country_id'] = $paymentCountry ? $paymentCountry['country_id'] : $ocOrder['payment_country_id'];
            $data['payment_country'] = isset($paymentCountry) ? $paymentCountry['name'] : $ocOrder['payment_country'];
            $data['payment_zone_id'] = $payment_zone_id ? $payment_zone_id : $ocOrder['payment_zone_id'];
            $data['payment_zone'] = isset($order['customer']['address']['region']) ? $order['customer']['address']['region'] : $ocOrder['payment_zone'];
            $data['shipping_country_id'] = $shippingCountry ? $shippingCountry['country_id'] : $ocOrder['shipping_country_id'];
            $data['shipping_country'] = $shippingCountry ? $shippingCountry['name'] : $ocOrder['shipping_country'];
            $data['shipping_zone_id'] = $shipping_zone_id ? $shipping_zone_id : $ocOrder['shipping_zone_id'];
            $data['shipping_zone'] = $shippingZone ? $shippingZone['name'] : $ocOrder['shipping_zone'];
            $data['shipping_address'] = '0';
            $data['shipping_firstname'] = $order['firstName'];
            $data['shipping_lastname'] = isset($order['lastName']) ? $order['lastName'] : $order['firstName'];
            $data['shipping_address_1'] = $order['delivery']['address']['text'];
            $data['shipping_address_2'] = '';
            $data['shipping_company'] = '';
            $data['shipping_company_id'] = '';
            $data['shipping_city'] = $order['delivery']['address']['city'];
            $data['shipping_postcode'] = $order['delivery']['address']['index'];

            if ($delivery !== null) {
                if (isset($this->settings['retailcrm_delivery'][$ocOrder['shipping_code']])
                    && isset($this->delivery[$delivery])
                ) {
                    $data['shipping'] = $this->delivery[$delivery];

                    $shipping = explode('.', $data['shipping']);
                    $shippingModule = $shipping[0];

                    if (isset($this->ocDelivery[$shippingModule][$data['shipping']]['title'])) {
                        $data['shipping_method'] = $this->ocDelivery[$shippingModule][$data['shipping']]['title'];
                    } else {
                        $data['shipping_method'] =$this->ocDelivery[$shippingModule]['title'];
                    }

                    $data['shipping_code'] = $data['shipping'];
                } elseif (!isset($this->settings['retailcrm_delivery'][$ocOrder['shipping_code']])
                    ) {
                    $data['shipping_method'] = $ocOrder['shipping_method'];
                    $data['shipping_code'] = $ocOrder['shipping_code'];
                }
            } else {
                if (!isset($this->settings[$ocOrder['shipping_code']])
                    || !isset($this->delivery[$delivery])
                ) {
                    $data['shipping_method'] = $ocOrder['shipping_method'];
                    $data['shipping_code'] = $ocOrder['shipping_code'];
                }
            }

            if (isset($payment)) {
                $data['payment'] = $this->payment[$payment['type']];
                $data['payment_method'] = isset($this->ocPayment[$data['payment']]) ? $this->ocPayment[$data['payment']] : $ocOrder['payment_method'];
                $data['payment_code'] = isset($this->payment[$payment['type']]) ? $this->payment[$payment['type']] : $ocOrder['payment_code'];
            } else {
                $data['payment_method'] = $ocOrder['payment_method'];
                $data['payment_code'] = $ocOrder['payment_code'];
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
                $productId = $item['offer']['externalId'];
                $options = array();

                if (mb_strpos($item['offer']['externalId'], '#') > 1) {
                    $offer = explode('#', $item['offer']['externalId']);
                    $productId = $offer[0];
                    $optionsFromCRM = explode('_', $offer[1]);

                    foreach ($optionsFromCRM as $optionFromCRM) {
                        $optionData = explode('-', $optionFromCRM);
                        $productOptionId = $optionData[0];
                        $optionValueId = $optionData[1];

                        $productOptions = $this->model_catalog_product->getProductOptions($productId);

                        foreach($productOptions as $productOption) {
                            if($productOptionId == $productOption['product_option_id']) {
                                foreach($productOption['product_option_value'] as $productOptionValue) {
                                    if($productOptionValue['option_value_id'] == $optionValueId) {
                                        $options[] = array(
                                            'product_option_id' => $productOptionId,
                                            'product_option_value_id' => $productOptionValue['product_option_value_id'],
                                            'value' => $this->getOptionValue($productOptionValue['option_value_id'], 'name'),
                                            'type' => $productOption['type'],
                                            'name' => $productOption['name'],
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                $product = $this->model_catalog_product->getProduct($productId);

                $data['order_product'][] = array(
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'price' => $item['initialPrice'],
                    'total' => (float)($item['initialPrice'] * $item['quantity']),
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'option' => $options
                );
            }

            $deliveryCost = !empty($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

            $data['total'] = $order['totalSumm'];
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
                    'title' => $data['shipping_method'],
                    'value' => $deliveryCost,
                    'text' => $deliveryCost,
                    'sort_order' => $this->shippingSettings[$this->totalTitle . 'shipping_sort_order']
                ),
                array(
                    'order_total_id' => '',
                    'code' => 'total',
                    'title' => $this->language->get('column_total'),
                    'value' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'text' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'sort_order' => $this->totalSettings[$this->totalTitle . 'total_sort_order']
                )
            );

            if (isset($order['discount']) && $order['discount'] > 0) {
                $orderTotals = $this->model_sale_order->getOrderTotals($order['externalId']);
                foreach($orderTotals as $orderTotal) {
                    if ($orderTotal['code'] == 'coupon'
                        || $orderTotal['code'] == 'reward'
                    ) {
                        $data['order_total'][] = $orderTotal;
                    }
                }
            }

            $data['fromApi'] = true;

            if (array_key_exists($order['status'], $this->status)) {
                $data['order_status_id'] = $this->status[$order['status']];
            } else {
                $tmpOrder = $this->model_sale_order->getOrder($order['externalId']);
                $data['order_status_id'] = $tmpOrder['order_status_id'];
            }

            $this->editOrder($order['externalId'], $data);
            $this->opencartApiClient->addHistory($order['externalId'], $data['order_status_id']);
        }
    }

    protected function createOrders($orders)
    {
        $customersIdsFix = array();
        $ordersIdsFix = array();

        foreach ($orders as $order) {
            $store = $this->config->get('config_store_id');

            if (isset($order['paymentType'])) {
                $payment['type'] = $order['paymentType'];
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
                    'approved' => 1,
                    'safe' => 0,
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

            $mail = isset($order['email']) ? $order['email'] : $order['customer']['email'];
            $phone = isset($order['phone']) ? $order['phone'] : '';

            if (!$phone) {
                $data['telephone'] = $order['customer']['phones'] ? $order['customer']['phones'][0]['number'] : '80000000000';
            } else {
                $data['telephone'] = $phone;
            }

            $data['store_url'] = $this->config->get('config_url');
            $data['currency_code'] = $this->config->get('config_currency');
            $data['currency_value'] = $this->getCurrencyByCode($data['currency_code'], 'value');
            $data['currency_id'] = $this->getCurrencyByCode($data['currency_code'], 'currency_id');
            $data['language_id'] = $this->getLanguageByCode($this->config->get('config_language'), 'language_id');
            $data['store_id'] = $store == null ? 0 : $store;
            $data['store_name'] = $this->config->get('config_name');
            $data['customer'] = $order['firstName'];
            $data['customer_id'] = $customer_id;
            $data['customer_group_id'] = 1;
            $data['firstname'] = $order['firstName'];
            $data['lastname'] = (isset($order['lastName'])) ? $order['lastName'] : $order['firstName'];
            $data['email'] = $mail ? $mail : uniqid() . '@retailrcm.ru';
            $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';
            $data['fax'] = '';
            $data['payment_address'] = '0';
            $data['payment_firstname'] = $order['firstName'];
            $data['payment_lastname'] = (isset($order['lastName'])) ? $order['lastName'] : $order['firstName'];
            $data['payment_address_1'] = $order['customer']['address']['text'];
            $data['payment_address_2'] = '';
            $data['payment_company'] = '';
            $data['payment_company_id'] = '';
            $data['payment_city'] = !empty($order['customer']['address']['city']) ? $order['customer']['address']['city'] : $order['delivery']['address']['city'];
            $data['payment_postcode'] = !empty($order['customer']['address']['index']) ? $order['customer']['address']['index'] : $order['delivery']['address']['index'];

            $shippingZone = '';

            if (!empty($order['delivery']['address']['region']) && is_int($order['delivery']['address']['region'])) {
                $shippingZone = $order['delivery']['address']['region'];
            } else {
                $shippingZone = $this->getZoneByName($order['delivery']['address']['region']);

                if ($shippingZone) {
                    $shipping_zone_id = $shippingZone['zone_id'];
                } else {
                    $shipping_zone_id = 0;
                }
            }

            if (isset($order['customer']['address']['region'])) {
                $paymentZone = $this->getZoneByName($order['customer']['address']['region']);

                if ($paymentZone) {
                    $payment_zone_id = $paymentZone['zone_id'];
                } else {
                    $payment_zone_id = 0;
                }
            }

            if (isset($order['delivery']['address']['countryIso'])) {
                $shippingCountry = $this->getCountryByIsoCode($order['delivery']['address']['countryIso']);
            }

            if (isset($order['customer']['address']['countryIso'])) {
                $paymentCountry = $this->getCountryByIsoCode($order['customer']['address']['countryIso']);
            }

            $delivery = isset($order['delivery']['code']) ? $order['delivery']['code'] : null;
            $data['payment_country_id'] = $paymentCountry ? $paymentCountry['country_id'] : 0;
            $data['payment_country'] = isset($paymentCountry) ? $paymentCountry['name'] : '';
            $data['payment_zone_id'] = $payment_zone_id;
            $data['payment_zone'] = isset($order['customer']['address']['region']) ? $order['customer']['address']['region'] : '';
            $data['shipping_country_id'] = $shippingCountry ? $shippingCountry['country_id'] : 0;
            $data['shipping_country'] = $shippingCountry ? $shippingCountry['name'] : '';
            $data['shipping_zone_id'] = $shipping_zone_id;
            $data['shipping_zone'] = $shippingZone ? $shippingZone['name'] : $data['payment_zone'];
            $data['shipping_address'] = '0';
            $data['shipping_firstname'] = $order['firstName'];
            $data['shipping_lastname'] = (isset($order['lastName'])) ? $order['lastName'] : $order['firstName'];
            $data['shipping_address_1'] = $order['delivery']['address']['text'];
            $data['shipping_address_2'] = '';
            $data['shipping_company'] = '';
            $data['shipping_company_id'] = '';
            $data['shipping_city'] = $order['delivery']['address']['city'];
            $data['shipping_postcode'] = $order['delivery']['address']['index'];
            $data['shipping'] = $delivery != null ? $this->delivery[$delivery] : '';
            $data['shipping_code'] = $delivery != null ? $this->delivery[$delivery] : '';

            $shipping = explode('.', $data['shipping']);
            $shippingModule = $shipping[0];

            if (isset($this->ocDelivery[$shippingModule][$data['shipping']]['title'])) {
                $data['shipping_method'] = $this->ocDelivery[$shippingModule][$data['shipping']]['title'];
            } else {
                $data['shipping_method'] =$this->ocDelivery[$shippingModule]['title'];
            }

            if (isset($payment)) {
                $data['payment'] = $this->payment[$payment['type']];
                $data['payment_method'] = $this->ocPayment[$data['payment']];
                $data['payment_code'] = $this->payment[$payment['type']];
            } else {
                $data['payment'] = 'free_checkout';
                $data['payment_method'] = $this->ocPayment[$data['payment']];
                $data['payment_code'] = 'free_checkout';
            }

            // this data will not retrive from crm for now
            $data['tax'] = '';
            $data['tax_id'] = '';
            $data['product'] = '';
            $data['product_id'] = '';
            $data['reward'] = '';
            $data['affiliate'] = '';
            $data['affiliate_id'] = 0;
            $data['payment_tax_id'] = '';
            $data['order_product_id'] = '';
            $data['payment_company'] = '';
            $data['payment_company_id'] = '';
            $data['company'] = '';
            $data['company_id'] = '';

            $data['order_product'] = array();

            foreach ($order['items'] as $item) {
                $productId = $item['offer']['externalId'];
                $options = array();

                if(mb_strpos($item['offer']['externalId'], '#') > 1) {
                    $offer = explode('#', $item['offer']['externalId']);
                    $productId = $offer[0];
                    $optionsFromCRM = explode('_', $offer[1]);

                    foreach ($optionsFromCRM as $optionFromCRM) {
                        $optionData = explode('-', $optionFromCRM);
                        $productOptionId = $optionData[0];
                        $optionValueId = $optionData[1];

                        $productOptions = $this->model_catalog_product->getProductOptions($productId);

                        foreach($productOptions as $productOption) {
                            if($productOptionId == $productOption['product_option_id']) {
                                foreach($productOption['product_option_value'] as $productOptionValue) {
                                    if($productOptionValue['option_value_id'] == $optionValueId) {
                                        $options[] = array(
                                            'product_option_id' => $productOptionId,
                                            'product_option_value_id' => $productOptionValue['product_option_value_id'],
                                            'value' => $this->getOptionValue($productOptionValue['option_value_id'], 'name'),
                                            'type' => $productOption['type'],
                                            'name' => $productOption['name'],
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                $product = $this->model_catalog_product->getProduct($productId);

                $data['order_product'][] = array(
                    'name' => $product['name'],
                    'model' => $product['model'],
                    'price' => $item['initialPrice'],
                    'total' => (float)($item['initialPrice'] * $item['quantity']),
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'option' => $options
                );
            }

            $deliveryCost = !empty($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

            $data['total'] = $order['totalSumm'];
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
                    'title' => $data['shipping_method'],
                    'value' => $deliveryCost,
                    'text' => $deliveryCost,
                    'sort_order' => $this->shippingSettings[$this->totalTitle . 'shipping_sort_order']
                ),
                array(
                    'order_total_id' => '',
                    'code' => 'total',
                    'title' => $this->language->get('column_total'),
                    'value' => !empty($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'text' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                    'sort_order' => $this->totalSettings[$this->totalTitle . 'total_sort_order']
                )
            );

            $data['fromApi'] = true;
            $data['order_status_id'] = 1;

            $order_id = $this->addOrder($data);

            $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $order_id);
        }

        return array('customers' => $customersIdsFix, 'orders' => $ordersIdsFix);
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
