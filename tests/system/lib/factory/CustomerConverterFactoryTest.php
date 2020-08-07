<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CustomerConverterFactoryTest extends TestCase {
    public function testCreate() {
        $converter = \retailcrm\factory\CustomerConverterFactory::create(static::$registry);

        $this->assertInstanceOf(\retailcrm\service\RetailcrmCustomerConverter::class, $converter);
    }
}
