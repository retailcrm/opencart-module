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

        $builder = \retailcrm\service\CorporateCustomerBuilder::create();
        $corp = $builder
            ->setCustomerExternalId(1)
            ->setCompany('Company')
            ->addCompany($data)
            ->addAddress($data)
            ->build();
        $corp_with_is_main = $builder->setIsMainCompany(true)->build();
        $corp_with_address = $builder->setCompanyAddressId(12)->build();

        self::assertNotEmpty($corp);
        self::assertNotEmpty($corp['addresses']);
        self::assertNotEmpty($corp['companies']);
        self::assertFalse(isset($corp['companies'][0]['isMain']));
        self::assertArrayHasKey('isMain', $corp_with_is_main['companies'][0]);
        self::assertTrue($corp_with_is_main['companies'][0]['isMain']);
        self::assertArrayHasKey('address', $corp_with_address['companies'][0]);
        self::assertNotEmpty($corp_with_address['companies'][0]['address']);
        self::assertEquals(12, $corp_with_address['companies'][0]['address']['id']);
        self::assertNotEmpty($corp['customerContacts'][0]['customer']);
        self::assertEquals(1, $corp['customerContacts'][0]['customer']['externalId']);


    }
}
