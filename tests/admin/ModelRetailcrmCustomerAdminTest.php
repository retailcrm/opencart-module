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

        self::$registry->set(\RetailcrmProxy::class, $this->apiClientMock);
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
}
