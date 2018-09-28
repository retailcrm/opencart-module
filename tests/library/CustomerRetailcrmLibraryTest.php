<?php

namespace Tests;

class CustomerRetailcrmLibraryTest extends OpenCartTest
{
    private $customer;
    private $apiClientMock;

    const CUSTOMER_ID = 1;

    public function setUp()
    {
        parent::setUp();

        $this->apiClientMock = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'customersCreate',
                'customersEdit'
            ))
            ->getMock();

        $retailcrm = new \Retailcrm\Retailcrm(self::$registry);
        $this->customer = $retailcrm->createObject(\Retailcrm\Customer::class);
    }

    public function testPrepareCustomer()
    {
        $customerModel = $this->loadModel('account/customer');
        $customer = $customerModel->getCustomer(self::CUSTOMER_ID);

        $this->customer->prepare($customer);
        $customerSend = $this->customer->getData();

        $this->assertArrayHasKey('externalId', $customerSend);
        $this->assertEquals(self::CUSTOMER_ID, $customerSend['externalId']);
        $this->assertArrayHasKey('firstName', $customerSend);
        $this->assertEquals('Test', $customerSend['firstName']);
        $this->assertArrayHasKey('lastName', $customerSend);
        $this->assertEquals('Test', $customerSend['lastName']);
        $this->assertArrayHasKey('email', $customerSend);
        $this->assertEquals('test@mail.ru', $customerSend['email']);
        $this->assertArrayHasKey('phones', $customerSend);
        $this->assertEquals('+7 (000) 000-00-00', $customerSend['phones'][0]['number']);
    }
}