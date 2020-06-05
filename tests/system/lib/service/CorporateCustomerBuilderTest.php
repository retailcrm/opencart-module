<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CorporateCustomerBuilderTest extends TestCase {
    public function testBuild() {
        $address = array(
            'address_id' => 1,
            'postcode' => '111111',
            'iso_code_2' => 'RU',
            'zone' => 'Zone',
            'city' => 'City',
            'company' => 'Company',
            'address_1' => 'Address',
            'address_2' => ''
        );

        $corp = \retailcrm\service\CorporateCustomerBuilder::create()
            ->setCustomerExternalId(1)
            ->setCompany('Company')
            ->addCompany($address)
            ->addAddress($address)
            ->build();

        $this->assertNotEmpty($corp);
        $this->assertNotEmpty($corp['addresses']);
        $this->assertNotEmpty($corp['companies']);
        $this->assertEquals(1, $corp['addresses'][0]['externalId']);
    }
}
