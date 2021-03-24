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

    public function testGetWeightOption() {
        $retailCrm = new \retailcrm\Retailcrm(self::$registry);

        $reflection = new ReflectionClass($retailCrm);
        $reflectionMethod = $reflection->getMethod('getWeightOption');
        $reflectionMethod->setAccessible('true');

        $result = $reflectionMethod->invokeArgs(
            $retailCrm,
            [['weight_prefix' => '+', 'weight' => 5]]
        );

        $this->assertEquals(5, $result);

        $result = $reflectionMethod->invokeArgs(
            $retailCrm,
            [['weight_prefix' => '-', 'weight' => 5]]
        );

        $this->assertEquals(-5, $result);;
    }
}
