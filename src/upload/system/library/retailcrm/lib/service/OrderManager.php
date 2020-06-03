<?php

namespace retailcrm\service;

/**
 * Class OrderManager
 */
class OrderManager {
    protected $api;
    protected $customer_manager;
    protected $order_converter;

    /**
     * OrderManager constructor.
     * @param \RetailcrmProxy $proxy
     * @param CustomerManager $customer_manager
     * @param RetailcrmOrderConverter $order_converter
     */
    public function __construct(
        \RetailcrmProxy $proxy,
        CustomerManager $customer_manager,
        RetailcrmOrderConverter $order_converter
    ) {
        $this->api = $proxy;
        $this->customer_manager = $customer_manager;
        $this->order_converter = $order_converter;
    }

    /**
     * @param array $order_data
     * @param array $order_products
     * @param array $order_totals
     */
    public function createOrder($order_data, $order_products, $order_totals) {
        $order = $this->prepareOrder($order_data, $order_products, $order_totals);

        if (!isset($order['customer'])) {
            $customer = $this->customer_manager->getCustomerForOrder($order_data);
            if (!empty($customer)) {
                $order['customer'] = $customer;
            }
        }

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
                $order['customer'] = $customer;
            }
        }

        $payments = $order['payments'];
        unset($order['payments']);

        $order_payment = $payments[0];
        $order = Utils::filterRecursive($order);
        $response = $this->api->ordersEdit($order);

        if ($response->isSuccessful()) {
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
     * @param array $order_data
     * @param array $order_products
     * @param array $order_totals
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

    /**
     * @param array $order_payment
     * @param int $orderId
     */
    private function updatePayment($order_payment, $orderId) {
        $response_order = $this->api->ordersGet($orderId);

        if ($response_order->isSuccessful()) {
            $order_info = $response_order['order'];
        }

        foreach ($order_info['payments'] as $payment_data) {
            if (isset($payment_data['externalId']) && $payment_data['externalId'] == $orderId) {
                $payment = $payment_data;
            }
        }

        if (isset($payment) && $payment['type'] != $order_payment['type']) {
            $response = $this->api->ordersPaymentDelete($payment['id']);

            if ($response->isSuccessful()) {
                $this->api->ordersPaymentCreate($order_payment);
            }
        } elseif (isset($payment) && $payment['type'] == $order_payment['type']) {
            $this->api->ordersPaymentEdit($order_payment);
        }
    }
}
