<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmCustomerAdminTest extends TestCase
{
    private $customerModel;
    private $apiClientMock;

    const CUSTOMER_ID = 1;

    public function setUp()
    {
        parent::setUp();

        $this->customerModel = $this->loadModel('extension/retailcrm/customer');

        $this->apiClientMock = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'customersUpload',
                'customersEdit'
            ))
            ->getMock();
    }

    public function testUploadToCrm()
    {
        $customerModel = $this->loadModel('customer/customer');
        $customers = $customerModel->getCustomers();

        $customersSend = $this->customerModel->uploadToCrm($customers, $this->apiClientMock);
        $customer = $customersSend[0][0];

        $this->assertInternalType('array', $customersSend);
        $this->assertInternalType('array', $customersSend[0]);
        $this->assertArrayHasKey('externalId', $customer);
        $this->assertArrayHasKey('firstName', $customer);
        $this->assertArrayHasKey('lastName', $customer);
        $this->assertArrayHasKey('email', $customer);
    }

    public function testChangeInCrm()
    {
        $customerModel = $this->loadModel('customer/customer');
        $customer = $customerModel->getCustomer(self::CUSTOMER_ID);

        $customerSend = $this->customerModel->changeInCrm($customer, $this->apiClientMock);

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
