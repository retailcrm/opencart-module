<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class OrderConverterFactoryTest extends TestCase {
    public function testCreate() {
        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);

        $this->assertInstanceOf(\retailcrm\service\RetailcrmOrderConverter::class, $converter);
    }
}
