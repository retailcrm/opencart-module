<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class SettingsManagerTest extends TestCase {
    public function testGetSettings() {
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $settings = $settings_manager->getSettings();

        $this->assertNotEmpty($settings);
    }

    public function testGetSetting() {
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $setting = $settings_manager->getSetting('payment');
        $this->assertNotEmpty($setting);

        $setting = $settings_manager->getSetting('delivery');
        $this->assertNotEmpty($setting);
    }

    public function testGetPaymentSettings() {
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $settings = $settings_manager->getPaymentSettings();
        $this->assertNotEmpty($settings);
    }

    public function testGetDeliverySettings() {
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $settings = $settings_manager->getDeliverySettings();
        $this->assertNotEmpty($settings);
    }

    public function testGetStatusesSettings() {
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $settings = $settings_manager->getStatusSettings();
        $this->assertNotEmpty($settings);
    }
}
