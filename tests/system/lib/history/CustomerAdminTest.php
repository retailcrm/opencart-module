<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class CustomerAdminTest extends TestCase {
    private $customer;

    public function setUp() {
        parent::setUp();

        $this->customer = array(
            'firstName' => 'Test',
            'lastName' => 'Test',
            'email' => 'test@mail.com',
            'phones' => array(
                array('number' => '800000000')
            ),
            'address' => array(
                'countryIso' => 'RU',
                'index' => '111111',
                'city' => 'City',
                'region' => 'Region',
                'text' => 'Text'
            )
        );
    }

    public function testHandleExistingCustomer() {
        $data_repository = new \retailcrm\repository\DataRepository(static::$registry);
        $customer_repository = new \retailcrm\repository\CustomerRepository(static::$registry);
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $customer_history = new \retailcrm\history\Customer($data_repository, $customer_repository, $settings_manager);
        $customer_data = $customer_repository->getCustomer(OrderManagerTest::CUSTOMER_ID);

        $customer_history->handleCustomer($customer_data, $this->customer);

        $this->assertEquals(false, $customer_data['password']);
    }

    public function testHandleNotExistingCustomer() {
        $data_repository = new \retailcrm\repository\DataRepository(static::$registry);
        $customer_repository = new \retailcrm\repository\CustomerRepository(static::$registry);
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $customer_history = new \retailcrm\history\Customer($data_repository, $customer_repository, $settings_manager);
        $customer_data = array();

        $customer_history->handleCustomer($customer_data, $this->customer);

        $this->assertNotEquals(false, $customer_data['password']);
    }

    public function testHandleAddress() {
        $data_repository = new \retailcrm\repository\DataRepository(static::$registry);
        $customer_repository = new \retailcrm\repository\CustomerRepository(static::$registry);
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $customer_history = new \retailcrm\history\Customer($data_repository, $customer_repository, $settings_manager);

        $address = $customer_history->handleAddress($this->customer, array());

        $this->assertEquals(true, $address['default']);
    }
}
