<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class RetailcrmCustomerConverterTest extends TestCase {
    const CUSTOMER_ID = 1;

    public function testSetCustomerData() {
        $converter = \retailcrm\factory\CustomerConverterFactory::create(static::$registry);
        $model = $this->loadModel('account/customer');
        $customer_data = $model->getCustomer(1);

        $customer = $converter
            ->initCustomerData($customer_data, array())
            ->setCustomerData()
            ->getCustomer();

        $this->assertEquals($customer_data['customer_id'], $customer['externalId']);
        $this->assertEquals($customer_data['firstname'], $customer['firstName']);
        $this->assertEquals($customer_data['lastname'], $customer['lastName']);
        $this->assertEquals($customer_data['email'], $customer['email']);
        $this->assertEquals($customer_data['date_added'], $customer['createdAt']);
    }

    public function testSetAddress() {
        $converter = \retailcrm\factory\CustomerConverterFactory::create(static::$registry);
        $model = $this->loadModel('account/customer');
        $customer_data = $model->getCustomer(static::CUSTOMER_ID);
        $address = array(
            'postcode' => '111111',
            'iso_code_2' => 'EN',
            'zone' => 'Zone',
            'city' => 'City',
            'address_1' => 'Address',
            'address_2' => ''
        );

        $customer = $converter
            ->initCustomerData($customer_data, $address)
            ->setAddress()
            ->getCustomer();

        $this->assertEquals($address['postcode'], $customer['address']['index']);
        $this->assertEquals($address['iso_code_2'], $customer['address']['countryIso']);
        $this->assertEquals($address['zone'], $customer['address']['region']);
        $this->assertEquals($address['city'], $customer['address']['city']);
        $this->assertEquals(
            $address['address_1'] . ' '. $address['address_2'],
            $customer['address']['text']
        );
    }
}
