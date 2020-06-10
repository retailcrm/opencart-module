<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class DataRepositoryAdminTest extends TestCase {
    public function testGetCountryByIsoCode() {
        $repository = new \retailcrm\repository\DataRepository(static::$registry);

        $country = $repository->getCountryByIsoCode('RU');

        $this->assertNotEmpty($country);
        $this->assertNotEmpty($country['name']);
    }

    public function testGetZoneByName() {
        $repository = new \retailcrm\repository\DataRepository(static::$registry);

        $zone = $repository->getZoneByName('Rostov-na-Donu');

        $this->assertNotEmpty($zone);
        $this->assertNotEmpty($zone['zone_id']);
    }

    public function testGetCurrencyByCode() {
        $repository = new \retailcrm\repository\DataRepository(static::$registry);

        $currency = $repository->getCurrencyByCode('USD');

        $this->assertNotEmpty($currency);

        $currency = $repository->getCurrencyByCode('USD', 'title');

        $this->assertNotEmpty($currency);
    }

    public function testGetLanguageByCode() {
        $repository = new \retailcrm\repository\DataRepository(static::$registry);

        $language = $repository->getLanguageByCode('en-gb');

        $this->assertNotEmpty($language);

        $language = $repository->getLanguageByCode('en-gb', 'name');

        $this->assertNotEmpty($language);
    }

    public function testGetConfig() {
        $repository = new \retailcrm\repository\DataRepository(static::$registry);

        $value = $repository->getConfig('config_currency');

        $this->assertNotEmpty($value);
    }

    public function testGetLanguage() {
        $repository = new \retailcrm\repository\DataRepository(static::$registry);

        $value = $repository->getLanguage('product_summ');

        $this->assertNotEmpty($value);
    }
}
