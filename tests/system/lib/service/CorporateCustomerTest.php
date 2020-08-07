<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CorporateCustomerTest extends TestCase {
    const ORDER_ID = 2;

    public function testBuildCorporateCustomerFromOrder() {
        $model = $this->loadModel('checkout/order');
        $order_data = $model->getOrder(static::ORDER_ID);
        $order_data['payment_company'] = 'Test company';

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $customer_repository = new \retailcrm\repository\CustomerRepository(static::$registry);

        $corporate_customer = new \retailcrm\service\CorporateCustomer($proxy, $customer_repository);
        $corp = $corporate_customer->buildCorporateCustomerFromOrder($order_data, 1);

        $this->assertNotEmpty($corp);
        $this->assertEquals(1, $corp['customerContacts'][0]['customer']['id']);
    }

    public function testCreateCorporateCustomerFromExistingCustomer() {
        $customer = array(
            'externalId' => 1
        );

        $model = $this->loadModel('checkout/order');
        $order_data = $model->getOrder(OrderManagerTest::ORDER_WITH_CUST_ID);

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'customersGet',
                'customersCorporateList',
                'customersCorporateCreate',
                'customersCorporateAddressesCreate',
                'customersCorporateCompaniesCreate'
            ])
            ->getMock();

        $proxy->expects($this->once())->method('customersGet')->willReturn(
            new \RetailcrmApiResponse(200, '{"success": true, "customer": {"id": 1}}')
        );

        $proxy->expects($this->any())->method('customersCorporateList')->willReturn(
            new \RetailcrmApiResponse(200, '{"success": true, "customersCorporate": []}')
        );

        $proxy->expects($this->once())->method('customersCorporateCreate')->willReturn(
            new \RetailcrmApiResponse(201, '{"success": true, "id": 1}')
        );

        $proxy->expects($this->once())->method('customersCorporateAddressesCreate')->willReturn(
            new \RetailcrmApiResponse(201, '{"success": true, "id": 1}')
        );

        $customer_repository = new \retailcrm\repository\CustomerRepository(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer($proxy, $customer_repository);

        $corp = $corporate_customer->createCorporateCustomer($order_data, $customer);

        $this->assertEquals(1, $corp);
    }

    public function testCreateCorporateCustomerFromNotExistingCustomer() {
        $customer = array(
            'id' => 1
        );

        $model = $this->loadModel('checkout/order');
        $order_data = $model->getOrder(OrderManagerTest::ORDER_WITH_CUST_ID);

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'customersCorporateList',
                'customersCorporateCreate',
                'customersCorporateAddressesCreate',
                'customersCorporateCompaniesCreate'
            ])
            ->getMock();

        $proxy->expects($this->atLeast(2))->method('customersCorporateList')->willReturn(
            new \RetailcrmApiResponse(200, '{"success": true, "customersCorporate": []}')
        );

        $proxy->expects($this->once())->method('customersCorporateCreate')->willReturn(
            new \RetailcrmApiResponse(201, '{"success": true, "id": 1}')
        );

        $proxy->expects($this->once())->method('customersCorporateAddressesCreate')->willReturn(
            new \RetailcrmApiResponse(201, '{"success": true, "id": 1}')
        );

        $customer_repository = new \retailcrm\repository\CustomerRepository(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer($proxy, $customer_repository);

        $corp = $corporate_customer->createCorporateCustomer($order_data, $customer);

        $this->assertEquals(1, $corp);
    }
}
