<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CustomerRespositoryAdminTest extends TestCase {
    const CUSTOMER_ID = 1;

    public function testGetCustomer() {
        $repository = new \retailcrm\repository\CustomerRepository(static::$registry);

        $customer = $repository->getCustomer(static::CUSTOMER_ID);

        $this->assertNotEmpty($customer);
        $this->assertEquals(static::CUSTOMER_ID, $customer['customer_id']);
    }
}
