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
        $this->assertEquals(1, $corp['customerContacts']['customer']['id']);
        $this->assertNotEmpty($corp['addresses']);
        $this->assertNotEmpty($corp['companies']);
    }
}
