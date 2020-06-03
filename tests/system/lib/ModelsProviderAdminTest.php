<?php

require_once __DIR__ . '/../../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelsProviderAdminTest extends TestCase {
    public function testAdminIncludeDependencies() {
        $provider = new \retailcrm\ModelsProvider(static::$registry);

        $provider->includeDependencies();

        $this->assertNotEmpty($this->model_sale_order);
    }
}
