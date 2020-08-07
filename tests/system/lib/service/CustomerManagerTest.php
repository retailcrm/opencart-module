<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CustomerManagerTest extends TestCase {
    const CUSTOMER_ID = 1;

    public function testCreateCustomer() {
        $customerModel = $this->loadModel('account/customer');
        $customer = $customerModel->getCustomer(self::CUSTOMER_ID);

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array('customersCreate'))
            ->getMock();

        $proxy->expects($this->once())->method('customersCreate');

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $customer_manager->createCustomer($customer, array());
    }

    public function testEditCustomer() {
        $customerModel = $this->loadModel('account/customer');
        $customer = $customerModel->getCustomer(self::CUSTOMER_ID);

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['customersEdit'])
            ->getMock();

        $proxy->expects($this->once())->method('customersEdit');

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $customer_manager->editCustomer($customer, array());
    }

    public function testPrepareCustomer() {
        $customer_model = $this->loadModel('account/customer');
        $customer = $customer_model->getCustomer(self::CUSTOMER_ID);
        $address = array(
            'postcode' => '111111',
            'iso_code_2' => 'EN',
            'zone' => 'Zone',
            'city' => 'City',
            'address_1' => 'Address',
            'address_2' => ''
        );

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $customer = $customer_manager->prepareCustomer($customer, $address);

        $this->assertArrayHasKey('externalId', $customer);
        $this->assertArrayHasKey('firstName', $customer);
        $this->assertArrayHasKey('lastName', $customer);
        $this->assertArrayHasKey('email', $customer);
        $this->assertArrayHasKey('externalId', $customer);
        $this->assertArrayHasKey('createdAt', $customer);
        $this->assertArrayHasKey('address', $customer);
    }
}
