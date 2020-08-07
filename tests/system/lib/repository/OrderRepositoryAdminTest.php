<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class OrderRepositoryAdminTest extends TestCase {
    public function testGetOrder() {
        $repository = new \retailcrm\repository\OrderRepository(static::$registry);

        $order = $repository->getOrder(OrderManagerTest::ORDER_WITH_CUST_ID);

        $this->assertNotEmpty($order);
        $this->assertEquals(OrderManagerTest::ORDER_WITH_CUST_ID, $order['order_id']);
    }

    public function testGetOrderTotals() {
        $repository = new \retailcrm\repository\OrderRepository(static::$registry);

        $totals = $repository->getOrderTotals(OrderManagerTest::ORDER_WITH_CUST_ID);

        $this->assertNotEmpty($totals);
    }
}
