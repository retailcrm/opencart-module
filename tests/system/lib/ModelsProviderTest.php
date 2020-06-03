<?php

require_once __DIR__ . '/../../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelsProviderTest extends TestCase {
    public function testCatalogIncludeDependencies() {
        $provider = new \retailcrm\ModelsProvider(static::$registry);

        $provider->includeDependencies();

        $this->assertNotEmpty($this->model_checkout_order);
    }
}
