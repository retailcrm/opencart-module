<?php

class ModelExtensionRetailcrmHistoryV45 extends Model
{
    protected $createResult;

    private $opencartApiClient;
    private $customFieldSetting;

    public function request()
    {
        $this->moduleTitle = $this->getModuleTitle();
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

        $settings = $this->model_setting_setting->getSetting($this->moduleTitle);
        $history = $this->model_setting_setting->getSetting('retailcrm_history');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        $url = isset($settings[$this->moduleTitle . '_url']) ? $settings[$this->moduleTitle . '_url'] : null;
        $key = isset($settings[$this->moduleTitle . '_apikey']) ? $settings[$this->moduleTitle . '_apikey'] : null;

        if (empty($url) || empty($key)) {
            $this->log->addNotice('You need to configure retailcrm module first.');
            return false;
        }

        $this->opencartApiClient = new OpencartApiClient($this->registry);

        $crm = new RetailcrmProxy(
            $settings[$this->moduleTitle . '_url'],
            $settings[$this->moduleTitle . '_apikey'],
            DIR_SYSTEM . 'storage/logs/retailcrm.log',
            $settings[$this->moduleTitle . '_apiversion']
        );

        $sinceIdOrders = $history['retailcrm_history_orders'] ? $history['retailcrm_history_orders'] : null;
        $sinceIdCustomers = $history['retailcrm_history_customers'] ? $history['retailcrm_history_customers'] : null;

        $packsOrders = $crm->ordersHistory(array(
            'sinceId' => $sinceIdOrders ? $sinceIdOrders : 0
        ), 1, 100);
        $packsCustomers = $crm->customersHistory(array(
            'sinceId' => $sinceIdCustomers ? $sinceIdCustomers : 0
        ), 1, 100);

        if(!$packsOrders->isSuccessful() && count($packsOrders->history) <= 0 && !$packsCustomers->isSuccessful() && count($packsCustomers->history) <= 0) {
            return false;
        }

        $generatedAt = $packsOrders['generatedAt'];
        $orders = RetailcrmHistoryHelper::assemblyOrder($packsOrders->history);
        $customers = RetailcrmHistoryHelper::assemblyCustomer($packsCustomers->history);

        $ordersHistory = $packsOrders->history;
        $customersHistory = $packsCustomers->history;

        $lastChangeOrders = $ordersHistory ? end($ordersHistory) : null;
        $lastChangeCustomers = $customersHistory ? end($customersHistory) : null;

        if ($lastChangeOrders !== null) {
            $sinceIdOrders = $lastChangeOrders['id'];
        }

        if ($lastChangeCustomers !== null) {
            $sinceIdCustomers = $lastChangeCustomers['id'];
        }

        $this->totalTitle = $this->totalTitles();
        $this->subtotalSettings = $this->model_setting_setting->getSetting($this->totalTitle . 'sub_total');
        $this->totalSettings = $this->model_setting_setting->getSetting($this->totalTitle . 'total');
        $this->shippingSettings = $this->model_setting_setting->getSetting($this->totalTitle . 'shipping');

        $this->delivery = array_flip($settings[$this->moduleTitle . '_delivery']);
        $this->payment = array_flip($settings[$this->moduleTitle . '_payment']);
        $this->status = array_flip($settings[$this->moduleTitle . '_status']);
        $this->delivery_default = $settings[$this->moduleTitle . '_default_shipping'];
        $this->payment_default = $settings[$this->moduleTitle . '_default_payment'];
        $this->ocPayment = $this->model_extension_retailcrm_references
            ->getOpercartPaymentTypes();

        $this->ocDelivery = $settings[$this->moduleTitle . '_delivery'];
            
        $this->zones = $this->model_localisation_zone->getZones();

        if (isset($settings[$this->moduleTitle . '_custom_field'])) {
            $this->customFieldSetting = array_flip($settings[$this->moduleTitle . '_custom_field']);
        }

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

        if (!empty($updateCustomers)) {
            $customers = $crm->customersList($filter = array('ids' => $updateCustomers));
            if ($customers) {
                $this->updateCustomers($customers['customers']);
            }
        }

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

        $this->model_setting_setting->editSetting(
            'retailcrm_history', 
            array(
                'retailcrm_history_orders' => $sinceIdOrders, 
                'retailcrm_history_customers' => $sinceIdCustomers,
                'retailcrm_history_datetime' => $generatedAt
            )
        );

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
            } elseif (isset($order['paymentType'])) {
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

            $data['store_id'] = $store == null ? 0 : $store;
            $data['customer'] = $order['firstName'];
            $data['customer_id'] = (!empty($order['customer']['externalId'])) ? $order['customer']['externalId'] : 0;
            $data['customer_group_id'] = 1;
            $data['firstname'] = $order['firstName'];
            $data['lastname'] = isset($order['lastName']) ? $order['lastName'] : $order['firstName'];
            $data['email'] = $mail ? $mail : uniqid() . '@retailrcm.ru';
            $data['comment'] = !empty($order['customerComment']) ? $order['customerComment'] : '';
            $data['fax'] = '';

            $data['payment_address'] = '0';
            $data['payment_firstname'] = $order['firstName'];
            $data['payment_lastname'] = isset($order['lastName']) ? $order['lastName'] : $order['firstName'];
            $data['payment_address_1'] = isset($order['customer']['address']) ? $order['customer']['address']['text'] : '';
            $data['payment_address_2'] = '';
            $data['payment_company'] = '';
            $data['payment_company_id'] = '';
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

            if (isset($order['delivery']['address']['countryIso'])) {
                $shippingCountry = $this->getCountryByIsoCode($order['delivery']['address']['countryIso']);
            }

            if (isset($order['customer']['address']['countryIso'])) {
                $paymentCountry = $this->getCountryByIsoCode($order['customer']['address']['countryIso']);
            }
            
            $delivery = isset($order['delivery']['code']) ? $order['delivery']['code'] : null;
            $data['payment_country_id'] = $paymentCountry ? $paymentCountry['country_id'] : 0;
            $data['payment_zone_id'] = $payment_zone_id;
            $data['shipping_country_id'] = $shippingCountry ? $shippingCountry['country_id'] : 0;
            $data['shipping_zone_id'] = $shipping_zone_id;
            $data['shipping_address'] = '0';
            $data['shipping_firstname'] = $order['firstName'];
            $data['shipping_lastname'] = isset($order['lastName']) ? $order['lastName'] : $order['firstName'];
            $data['shipping_address_1'] = $order['delivery']['address']['text'];
            $data['shipping_address_2'] = '';
            $data['shipping_company'] = '';
            $data['shipping_company_id'] = '';
            $data['shipping_city'] = $order['delivery']['address']['city'];
            $data['shipping_postcode'] = $order['delivery']['address']['index'];
            $data['shipping'] = $delivery != null ? $this->delivery[$delivery] : $this->delivery_default;
            $data['shipping_method'] = $this->ocDelivery[$data['shipping']];
            $data['shipping_code'] = $delivery != null ? $this->delivery[$delivery] : $this->delivery_default;

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

            if (isset($this->customFieldSetting) && $order['customFields']) {
                foreach ($order['customFields'] as $code => $value) {
                    if (array_key_exists($code, $this->customFieldSetting)) {
                        $fieldCode = str_replace('o_', '', $this->customFieldSetting[$code]);
                        $customFields[$fieldCode] = $value;
                    }
                }

                $data['custom_field'] = isset($customFields) ? $customFields : '';
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
            } elseif (isset($order['paymentType'])) {
                $payment['type'] = $order['paymentType'];
            }

            $customer_id = (!empty($order['customer']['externalId']))
                ? $order['customer']['externalId']
                : 0;

            $data = array();

            if ($customer_id == 0) {
                if (isset($order['customer']['address']['countryIso'])) {
                    $customerCountry = $this->getCountryByIsoCode($order['customer']['address']['countryIso']);
                } else {
                    $customerCountry = $this->getCountryByIsoCode($order['delivery']['address']['countryIso']);
                }

                if (isset($order['customer']['address']['region'])) {
                    $customerZone = $this->getZoneByName($order['customer']['address']['region']);
                } else {
                    $customerZone = $this->getZoneByName($order['delivery']['address']['region']);
                }

                $cData = array(
                    'store_id' => 0,
                    'customer_group_id' => '1',
                    'firstname' => isset($order['patronymic']) ? $order['firstName'] . ' ' . $order['patronymic'] : $order['firstName'],
                    'lastname' => (!empty($order['customer']['lastName'])) ? $order['customer']['lastName'] : ' ',
                    'email' => $order['customer']['email'],
                    'telephone' => $order['customer']['phones'] ? $order['customer']['phones'][0]['number'] : ' ',
                    'fax' => '',
                    'newsletter' => 0,
                    'password' => 'tmppass',
                    'status' => 1,
                    'approved' => 1,
                    'safe' => 0,
                    'address' => array(
                        array(
                            'firstname' => isset($order['patronymic']) ? $order['firstName'] . ' ' . $order['patronymic'] : $order['firstName'],
                            'lastname' => (!empty($order['customer']['lastName'])) ? $order['customer']['lastName'] : ' ',
                            'address_1' => $order['customer']['address']['text'],
                            'address_2' => ' ',
                            'city' => !empty($order['customer']['address']['city']) ? $order['customer']['address']['city'] : $order['delivery']['address']['city'],
                            'postcode' => isset($order['customer']['address']['index']) ? $order['customer']['address']['index'] : $order['delivery']['address']['index'],
                            'tax_id' => '1',
                            'company' => '',
                            'company_id' => '',
                            'zone_id' => $customerZone ? $customerZone['zone_id'] : 0,
                            'country_id' => $customerCountry ? $customerCountry['country_id'] : 0,
                            'default' => '1'
                        )
                    ),
                );

                $customer_id = $this->model_customer_customer->addCustomer($cData);

                $customersIdsFix[] = array('id' => $order['customer']['id'], 'externalId' => (int)$customer_id);
            }

            $mail = isset($order['email']) ? $order['email'] : $order['customer']['email'];
            $phone = isset($order['phone']) ? $order['phone'] : '';

            if (!$phone) {
                $data['telephone'] = $order['customer']['phones'] ? $order['customer']['phones'][0]['number'] : '80000000000';
            } else {
                $data['telephone'] = $phone;
            }

            $data['store_id'] = $store == null ? 0 : $store;
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
            $data['payment_zone_id'] = $payment_zone_id;
            $data['shipping_country_id'] = $shippingCountry ? $shippingCountry['country_id'] : 0;
            $data['shipping_zone_id'] = $shipping_zone_id;
            $data['shipping_address'] = '0';
            $data['shipping_firstname'] = $order['firstName'];
            $data['shipping_lastname'] = (isset($order['lastName'])) ? $order['lastName'] : $order['firstName'];
            $data['shipping_address_1'] = $order['delivery']['address']['text'];
            $data['shipping_address_2'] = '';
            $data['shipping_company'] = '';
            $data['shipping_company_id'] = '';
            $data['shipping_city'] = $order['delivery']['address']['city'];
            $data['shipping_postcode'] = $order['delivery']['address']['index'];
            $data['shipping'] = $delivery != null ? $this->delivery[$delivery] : $this->delivery_default;
            $data['shipping_method'] = $this->ocDelivery[$data['shipping']];
            $data['shipping_code'] = $delivery != null ? $this->delivery[$delivery] : $this->delivery_default;

            if (isset($payment)) {
                $data['payment'] = $this->payment[$payment['type']];
                $data['payment_method'] = $this->ocPayment[$data['payment']];
                $data['payment_code'] = $this->payment[$payment['type']];
            } else {
                $data['payment'] = $this->payment_default;
                $data['payment_method'] = $this->ocPayment[$data['payment']];
                $data['payment_code'] = $this->payment_default;
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

            if (isset($this->customFieldSetting) && $order['customFields']) {
                foreach ($order['customFields'] as $code => $value) {
                    if (array_key_exists($code, $this->customFieldSetting)) {
                        $fieldCode = str_replace('o_', '', $this->customFieldSetting[$code]);
                        $customFields[$fieldCode] = $value;
                    }
                }

                $data['custom_field'] = isset($customFields) ? $customFields : '';
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

            $order_id = $this->opencartApiClient->addOrder($data);

            $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $order_id);
        }

        return array('customers' => $customersIdsFix, 'orders' => $ordersIdsFix);
    }

    protected function updateCustomers($customers)
    {
        foreach ($customers as $customer) {
            
            $customer_id = $customer['externalId'];
            $customerData = $this->model_customer_customer->getCustomer($customer_id);

            $customerData['firstname'] = $customer['firstName'];
            $customerData['lastname'] = isset($customer['lastName']) ? $customer['lastName'] : '';
            $customerData['email'] = $customer['email'];
            $customerData['telephone'] = $customer['phones'] ? $customer['phones'][0]['number'] : '';

            $customerAddress = $this->model_customer_customer->getAddress($customerData['address_id']);

            if (isset($customer['address']['countryIso'])) {
                $customerCountry = $this->getCountryByIsoCode($customer['address']['countryIso']);
            }

            if (isset($customer['address']['region'])) {
                $customerZone = $this->getZoneByName($customer['address']['region']);
            }

            $customerAddress['firstname'] = isset($customer['patronymic']) ? $customer['firstName'] . ' ' . $customer['patronymic'] : $customer['firstName'];
            $customerAddress['lastname'] = isset($customer['lastName']) ? $customer['lastName'] : '';
            $customerAddress['address_1'] = $customer['address']['text'];
            $customerAddress['city'] = $customer['address']['city'];
            $customerAddress['postcode'] = $customer['address']['index'] ? $customer['address']['index'] : '';

            if (isset($customerCountry)) {
                $customerAddress['country_id'] = $customerCountry['country_id'];
            }

            if (isset($customerZone)) {
                $customerAddress['zone_id'] = $customerZone['zone_id'];
            }

            $customerData['address'] = array($customerAddress);

            if (isset($this->customFieldSetting) && $customer['customFields']) {
                foreach ($customer['customFields'] as $code => $value) {
                    if (array_key_exists($code, $this->customFieldSetting)) {
                        $fieldCode = str_replace('c_', '', $this->customFieldSetting[$code]);
                        $customFields[$fieldCode] = $value;
                    }
                }

                $customerData['custom_field'] = isset($customFields) ? $customFields : '';
            }

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

    private function totalTitles()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = '';
        } else {
            $title = 'total_';
        }

        return $title;
    }
    
    public function getCountryByIsoCode($isoCode)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE iso_code_2 = '" . $isoCode . "'");
    
        return $query->row;
    }

    public function getZoneByName($name)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE name = '" . $name . "'");

        return $query->row;
    }
}
