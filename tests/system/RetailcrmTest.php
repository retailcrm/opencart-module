<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class RetailcrmTest extends TestCase {
    public function testGetOrderManager() {
        $retailcrm = new retailcrm\Retailcrm(static::$registry);

        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        static::$registry->set(\RetailcrmProxy::class, $proxy);
        $manager = $retailcrm->getOrderManager();

        $this->assertInstanceOf(\retailcrm\service\OrderManager::class, $manager);
    }
}
