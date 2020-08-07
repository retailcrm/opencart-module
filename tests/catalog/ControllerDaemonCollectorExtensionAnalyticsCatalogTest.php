<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ControllerDaemonCollectorExtensionAnalyticsCatalogTest extends TestCase {
    public function testIndex() {
        $data = $this->load->controller('extension/analytics/daemon_collector');

        $this->assertContains('RC-XXXXXXXXXX-X', $data);
    }
}
