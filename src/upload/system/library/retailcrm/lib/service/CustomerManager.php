<?php

namespace retailcrm\service;

class CustomerManager {
    protected $api;
    protected $customer_converter;

    public function __construct(\RetailcrmProxy $proxy, RetailcrmCustomerConverter $customer_converter) {
        $this->api = $proxy;
        $this->customer_converter = $customer_converter;
    }

    public function createCustomer($customer_data, $address) {
        $customer = $this->prepareCustomer($customer_data, $address, !empty($customer_data['newsletter']));

        $this->api->customersCreate($customer);
    }

    public function editCustomer($customer_data, $address) {
        $customer = $this->prepareCustomer($customer_data, $address);

        $this->api->customersEdit($customer);
    }

    public function editCustomerNewsLetter($customer_data) {
        $this->api->customersEdit(
            [
                'externalId' => $customer_data['customer_id'],
                'subscribed' => !empty($customer_data['newsletter']),
            ]
        );
    }

    public function uploadCustomers($customers) {
        $this->api->customersUpload($customers);
    }

    public function prepareCustomer($customer_data, $address, $isSubscribed = null) {
        return $this->customer_converter
            ->initCustomerData($customer_data, $address, $isSubscribed)
            ->setCustomerData()
            ->setAddress()
            ->setCustomFields()
            ->getCustomer();
    }

    public function getCustomerForOrder($order_data) {
        $customer = $this->searchCustomer($order_data['telephone'], $order_data['email']);
        if ($customer) {
            return $customer;
        }

        $new_customer = array(
            'firstName' => $order_data['firstname'],
            'lastName' => $order_data['lastname'],
            'email' => $order_data['email'],
            'createdAt' => $order_data['date_added'],
            'address' => array(
                'countryIso' => $order_data['payment_iso_code_2'],
                'index' => $order_data['payment_postcode'],
                'city' => $order_data['payment_city'],
                'region' => $order_data['payment_zone'],
                'text' => $order_data['payment_address_1'] . ' ' . $order_data['payment_address_2']
            )
        );

        if (!empty($order_data['telephone'])) {
            $new_customer['phones'] = array(
                array(
                    'number' => $order_data['telephone']
                )
            );
        }

        $res = $this->api->customersCreate($new_customer);

        $customer = array();
        if ($res && $res->isSuccessful() && isset($res['id'])) {
            $customer['id'] = $res['id'];
        }

        return $customer;
    }

    private function searchCustomer($phone, $email) {
        $customer = array();

        $response = $this->api->customersList(
            array(
                'name' => $phone,
                'email' => $email
            ),
            1,
            100
        );

        if ($response && $response->isSuccessful() && isset($response['customers'])) {
            $customers = $response['customers'];

            if ($customers) {
                $customer = end($customers);
            }
        }

        return $customer;
    }
}
