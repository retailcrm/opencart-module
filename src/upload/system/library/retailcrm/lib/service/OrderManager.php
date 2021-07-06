<?php

namespace retailcrm\service;

class OrderManager {
    protected $api;
    protected $customer_manager;
    protected $order_converter;
    protected $corporate_customer_service;
    protected $settings_manager;

    /**
     * OrderManager constructor.
     * @param \RetailcrmProxy $proxy
     * @param CustomerManager $customer_manager
     * @param RetailcrmOrderConverter $order_converter
     * @param CorporateCustomer $corporate_customer_service
     * @param SettingsManager $settings_manager
     */
    public function __construct(
        \RetailcrmProxy $proxy,
        CustomerManager $customer_manager,
        RetailcrmOrderConverter $order_converter,
        CorporateCustomer $corporate_customer_service,
        SettingsManager $settings_manager
    ) {
        $this->api = $proxy;
        $this->customer_manager = $customer_manager;
        $this->order_converter = $order_converter;
        $this->corporate_customer_service = $corporate_customer_service;
        $this->settings_manager = $settings_manager;
    }

    /**
     * @param array $order_data
     * @param array $order_products
     * @param array $order_totals
     */
    public function createOrder($order_data, $order_products, $order_totals) {
        $order = $this->prepareOrder($order_data, $order_products, $order_totals);

        if (
            !isset($order['customer'])
            || (isset($order['customer']['externalId'])
                && !$this->checkExistCustomer($order['customer']['externalId']))
        ) {
            $customer = $this->customer_manager->getCustomerForOrder($order_data);
            if (!empty($customer)) {
                $order['customer'] = $customer;
            }
        }

        $order['contragent']['contragentType'] = 'individual';
        $this->handleCorporate($order, $order_data);

        return $this->api->ordersCreate($order);
    }

    /**
     * @param array $order_data
     * @param array $order_products
     * @param array $order_totals
     */
    public function editOrder($order_data, $order_products, $order_totals) {
        $order = $this->prepareOrder($order_data, $order_products, $order_totals);

        if (!isset($order['customer'])) {
            $customer = $this->customer_manager->getCustomerForOrder($order_data);
            if (!empty($customer)) {
                $order['customer'] = array(
                    'id' => $customer['id']
                );
            }
        }

        $order['contragent']['contragentType'] = 'individual';
        $this->handleCorporate($order, $order_data);

        $payments = $order['payments'];
        unset($order['payments']);

        $order_payment = $payments[0];
        $order = \retailcrm\Utils::filterRecursive($order);
        $response = $this->api->ordersEdit($order);

        if ($response && $response->isSuccessful()) {
            $this->updatePayment($order_payment, $order['externalId']);
        }
    }

    /**
     * @param array $orders
     */
    public function uploadOrders($orders) {
        $this->api->ordersUpload($orders);
    }

    /**
     * @param array $order_data array of order fields
     * @param array $order_products array of order products
     * @param array $order_totals array of order totals
     * @return array
     */
    public function prepareOrder($order_data, $order_products, $order_totals) {
        return $this->order_converter->initOrderData($order_data, $order_products, $order_totals)
            ->setOrderData()
            ->setDelivery()
            ->setItems()
            ->setPayment(false)
            ->setDiscount()
            ->setCustomFields()
            ->getOrder();
    }

    private function handleCorporate(&$order, $order_data) {
        if ($this->isCorporateOrder($order_data)
            && !empty($order['customer'])
            && $this->settings_manager->getSetting('corporate_enabled') == 1
        ) {
            $order_data['payment_company'] = htmlspecialchars_decode($order_data['payment_company']);
            $corp_customer_id = $this->corporate_customer_service->createCorporateCustomer($order_data, $order['customer']);

            if ($corp_customer_id) {
                $order = $this->order_converter->setCorporateCustomer($order, $corp_customer_id);
                $companiesResponse = $this->api->customersCorporateCompanies($corp_customer_id, array(), 1, 100, 'id');
                if ($companiesResponse && $companiesResponse->isSuccessful() && !empty($companiesResponse['companies'])) {
                    foreach ($companiesResponse['companies'] as $company) {
                        if ($company['name'] === $order_data['payment_company']) {
                            $order['company'] = array(
                                'id' => $company['id']
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * @param array $order_data array of order fields
     * @return bool
     */
    private function isCorporateOrder($order_data) {
        return !empty($order_data['payment_company']);
    }

    /**
     * @param array $order_payment
     * @param int $orderId
     */
    private function updatePayment($order_payment, $orderId) {
        $response_order = $this->api->ordersGet($orderId);

        if ($response_order && $response_order->isSuccessful()) {
            $order_info = $response_order['order'];
        }

        foreach ($order_info['payments'] as $payment_data) {
            if (isset($payment_data['externalId']) && $payment_data['externalId'] == $orderId) {
                $payment = $payment_data;
            }
        }

        if (isset($payment) && $payment['type'] != $order_payment['type']) {
            $response = $this->api->ordersPaymentDelete($payment['id']);

            if ($response && $response->isSuccessful()) {
                $this->api->ordersPaymentCreate($order_payment);
            }
        } elseif (isset($payment) && $payment['type'] == $order_payment['type']) {
            $this->api->ordersPaymentEdit($order_payment);
        }
    }

    /**
     * @param $customerExternalId
     *
     * @return bool
     */
    private function checkExistCustomer($customerExternalId)
    {
        $result = $this->api->customersGet($customerExternalId);

        return $result && $result->isSuccessful() && $result->offsetExists('customer');
    }
}
