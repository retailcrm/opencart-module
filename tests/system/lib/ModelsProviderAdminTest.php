<?php

require_once __DIR__ . '/../../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelsProviderAdminTest extends TestCase {
    public function testAdminIncludeDependencies() {
        // TODO should be run with "processIsolation", but with that not working asserting
//        $provider = new \retailcrm\ModelsProvider(static::$registry);
//
//        $provider->includeDependencies();
//
//        $this->assertNotEmpty($this->model_sale_order);
    }
}
