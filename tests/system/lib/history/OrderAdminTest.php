<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class OrderAdminTest extends TestCase {
    private $order;
    private $order_history;

    public function setUp() {
        parent::setUp();

        $this->order = $this->getOrder();

        $data_repository = new \retailcrm\repository\DataRepository(static::$registry);
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);
        $products_repository = new \retailcrm\repository\ProductsRepository(static::$registry);
        $order_repository = new \retailcrm\repository\OrderRepository(static::$registry);

        $this->order_history = new \retailcrm\history\Order(
            $data_repository,
            $settings_manager,
            $products_repository,
            $order_repository
        );

        $this->order_history->setOcDelivery(array(
            "flat" => array(
                "title" => "Flat Rate",
                "flat.flat" => array(
                    "code" => "flat.flat",
                    "title" => "Flat Shipping Rate",
                    "cost" => "5.00",
                    "tax_class_id" => "9",
                    "text" => "$8.00"
                )
            )
        ));

        $this->order_history->setOcPayment(array(
            "cod" => "Cash on delivery"
        ));
    }

    public function testHandleBaseOrderData() {
        $data = array();

        $this->order_history->handleBaseOrderData($data, $this->order);

        $this->assertNotEmpty($data['telephone']);
        $this->assertNotEmpty($data['email']);
        $this->assertNotEmpty($data['email']);
        $this->assertNotEmpty($data['firstname']);
        $this->assertNotEmpty($data['lastname']);
        $this->assertNotEmpty($data['store_name']);
    }

    public function testHandlePayment() {
        $data = array();

        $this->order_history->handlePayment($data, $this->order);

        $this->assertNotEmpty($data['payment_firstname']);
        $this->assertNotEmpty($data['payment_lastname']);
        $this->assertNotEmpty($data['payment_city']);
        $this->assertNotEmpty($data['payment_postcode']);
        $this->assertNotEmpty($data['payment_country']);
        $this->assertNotEmpty($data['payment_method']);
        $this->assertNotEmpty($data['payment_code']);
        $this->assertEquals('cod', $data['payment_code']);
    }

    public function testHandleShipping() {
        $data = array();

        $this->order_history->handleShipping($data, $this->order);

        $this->assertNotEmpty($data['shipping_firstname']);
        $this->assertNotEmpty($data['shipping_lastname']);
        $this->assertNotEmpty($data['shipping_city']);
        $this->assertNotEmpty($data['shipping_postcode']);
        $this->assertNotEmpty($data['shipping_method']);
        $this->assertNotEmpty($data['shipping_code']);
        $this->assertEquals('flat.flat', $data['shipping_code']);
    }

    public function testHandleTotals() {
        $data = array('shipping_method' => 'Flat rate');

        $this->order_history->handleTotals($data, $this->order);

        $this->assertNotEmpty($data['order_total']);
        $this->assertNotEmpty($data['total']);
    }

    private function getOrder() {
        return array(
            'externalId' => '1',
            'email' => 'test@test.com',
            'phone' => '8000000000',
            'firstName' => 'Test',
            'lastName' => 'Test',
            'customerComment' => 'Test comment',
            'countryIso' => 'RU',
            'totalSumm' => 200,
            'summ' => 100,
            'customer' => array(
                'firstName' => 'Test customer',
                'lastName' => 'Test customer',
                'address' => array(
                    'countryIso' => 'RU',
                    'index' => '111111',
                    'region' => 'Test region',
                    'city' => 'Test city',
                    'text' => ''
                )
            ),
            'delivery' => array(
                'code' => 'flat',
                'cost' => 100,
                'address' => array(
                    'index' => '111111',
                    'region' => 'Test region',
                    'city' => 'Test city',
                    'text' => ''
                )
            ),
            'payments' => array(
                array(
                    'externalId' => 'opencart_1',
                    'type' => 'cod'
                )
            ),
            'items' => array(
                array(
                    'initialPrice' => 100,
                    'discountTotal' => 0,
                    'quantity' => 1,
                    'offer' => array(
                        'displayName' => 'Test',
                        'externalId' => '42#217-39_218-43',
                        'name' => 'Test'
                    )
                )
            )
        );
    }
}
