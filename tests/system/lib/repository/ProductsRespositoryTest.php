<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class ProductsRespositoryTest extends TestCase {
    const CUSTOMER_ID = 1;

    public function testProductSpecials() {
        $repository = new \retailcrm\repository\ProductsRepository(static::$registry);

        $specials = $repository->getProductSpecials(42);

        $this->assertNotEmpty($specials);
    }

    public function testProductOptions() {
        $repository = new \retailcrm\repository\ProductsRepository(static::$registry);

        $options = $repository->getProductOptions(42);

        $this->assertNotEmpty($options);
    }
}
