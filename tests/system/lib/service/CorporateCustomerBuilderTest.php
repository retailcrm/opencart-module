<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CorporateCustomerBuilderTest extends TestCase {
    public function testBuild() {
        $data = array(
            'payment_postcode' => '111111',
            'payment_iso_code_2' => 'RU',
            'payment_zone' => 'Zone',
            'payment_city' => 'City',
            'payment_company' => 'Company',
            'payment_address_1' => 'Address',
            'payment_address_2' => '',
            'shipping_postcode' => '111111',
            'shipping_iso_code_2' => 'RU',
            'shipping_zone' => 'Zone',
            'shipping_city' => 'City',
            'shipping_company' => 'Company',
            'shipping_address_1' => 'Address',
            'shipping_address_2' => ''
        );

        $corp = \retailcrm\service\CorporateCustomerBuilder::create()
            ->setCustomerExternalId(1)
            ->setCompany('Company')
            ->addCompany($data)
            ->addAddress($data)
            ->build();

        $this->assertNotEmpty($corp);
        $this->assertNotEmpty($corp['addresses']);
        $this->assertNotEmpty($corp['companies']);
        $this->assertNotEmpty($corp['customerContacts'][0]['customer']);
        $this->assertEquals(1, $corp['customerContacts'][0]['customer']['externalId']);
    }
}
