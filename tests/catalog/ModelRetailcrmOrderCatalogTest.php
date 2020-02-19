<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmOrderCatalogTest extends TestCase
{
    private $orderModel;
    private $apiClientMock;
    private $retailcrm;

    const CUSTOMER_ID = 1;
    const ORDER_WITH_CUST_ID = 1;
    const ORDER_ID = 2;

    public function setUp()
    {
        parent::setUp();

        $this->orderModel = $this->loadModel('extension/retailcrm/order');

        $this->apiClientMock = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'ordersCreate',
                'ordersEdit',
                'ordersGet',
                'ordersPaymentEdit',
                'customersList',
                'customersCreate'
            ))
            ->getMock();

        $this->retailcrm = new \retailcrm\Retailcrm(self::$registry);

        $this->setSetting(
            $this->retailcrm->getModuleTitle(),
            array(
                $this->retailcrm->getModuleTitle() . '_apiversion' => 'v5',
                $this->retailcrm->getModuleTitle() . '_order_number' => 1,
                $this->retailcrm->getModuleTitle() . '_status' => array(
                    1 => 'new'
                ),
                $this->retailcrm->getModuleTitle() . '_delivery' => array(
                    'flat.flat' => 'flat'
                ),
                $this->retailcrm->getModuleTitle() . '_payment' => array(
                    'cod' => 'cod'
                ),
                $this->retailcrm->getModuleTitle() . '_special_1' => 'special1',
                $this->retailcrm->getModuleTitle() . '_special_2' => 'special2',
                $this->retailcrm->getModuleTitle() . '_special_3' => 'special3'
            )
        );
    }

    public function testCreateOrderWithCustomer()
    {
        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_WITH_CUST_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $order['products'] = $orderAccountModel->getOrderProducts($order_id);
        $order['totals'] = $orderAccountModel->getOrderTotals($order_id);

        foreach ($order['products'] as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $order['products'][$key]['option'] = $productOptions;
            }
        }

        $orderProcess = $this->orderModel->processOrder($order);
        $orderSend = $this->orderModel->sendToCrm($orderProcess, $this->apiClientMock, $order);

        $this->assertArrayHasKey('status', $orderSend);
        $this->assertEquals('new', $orderSend['status']);
        $this->assertArrayHasKey('externalId', $orderSend);
        $this->assertArrayHasKey('number', $orderSend);
        $this->assertArrayHasKey('firstName', $orderSend);
        $this->assertEquals('Test', $orderSend['firstName']);
        $this->assertArrayHasKey('lastName', $orderSend);
        $this->assertEquals('Test', $orderSend['lastName']);
        $this->assertArrayHasKey('email', $orderSend);
        $this->assertEquals('test@mail.ru', $orderSend['email']);
        $this->assertArrayHasKey('phone', $orderSend);
        $this->assertEquals('+7 (000) 000-00-00', $orderSend['phone']);
        $this->assertArrayHasKey('createdAt', $orderSend);
        $this->assertArrayHasKey('delivery', $orderSend);
        $this->assertInternalType('array', $orderSend['delivery']);
        $this->assertEquals('flat', $orderSend['delivery']['code']);
        $this->assertEquals('Test', $orderSend['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $orderSend['delivery']['address']['region']);
        $this->assertEquals('111111', $orderSend['delivery']['address']['index']);
        $this->assertArrayHasKey('items', $orderSend);

        foreach($orderSend['items'] as $item) {
            $this->assertArrayHasKey('priceType', $item);
            $this->assertEquals('special1', $item['priceType']['code']);
        }

        $this->assertArrayHasKey('customerComment', $orderSend);
        $this->assertArrayHasKey('customer', $orderSend);
        $this->assertArrayHasKey('externalId', $orderSend['customer']);
        $this->assertEquals(self::CUSTOMER_ID, $orderSend['customer']['externalId']);
        $this->assertArrayHasKey('payments', $orderSend);
        $this->assertEquals('cod', $orderSend['payments'][0]['type']);
        $this->assertNotEmpty($orderSend['payments']);
    }

    public function testEditOrderWithCustomer()
    {
        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_WITH_CUST_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $order['products'] = $orderAccountModel->getOrderProducts($order_id);
        $order['totals'] = $orderAccountModel->getOrderTotals($order_id);

        foreach ($order['products'] as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $order['products'][$key]['option'] = $productOptions;
            }
        }

        $orderEditResponse = new \RetailcrmApiResponse(
            200,
            json_encode(
                array(
                    'success' => true,
                    'id' => 1
                )
            )
        );

        $ordersGetResponse = new \RetailcrmApiResponse(
            200,
            json_encode(
                array(
                    'success' => true,
                    'order' => $this->getOrder($order_id)
                )
            )
        );

        $this->apiClientMock->expects($this->any())->method('ordersEdit')->willReturn($orderEditResponse);
        $this->apiClientMock->expects($this->any())->method('ordersGet')->willReturn($ordersGetResponse);
        $this->apiClientMock->expects($this->any())->method('customersCreate')->willReturn($orderEditResponse);
        $orderProcess = $this->orderModel->processOrder($order);
        $orderSend = $this->orderModel->sendToCrm($orderProcess, $this->apiClientMock, $order, false);

        $this->assertArrayHasKey('status', $orderSend);
        $this->assertEquals('new', $orderSend['status']);
        $this->assertArrayHasKey('externalId', $orderSend);
        $this->assertArrayHasKey('number', $orderSend);
        $this->assertArrayHasKey('firstName', $orderSend);
        $this->assertEquals('Test', $orderSend['firstName']);
        $this->assertArrayHasKey('lastName', $orderSend);
        $this->assertEquals('Test', $orderSend['lastName']);
        $this->assertArrayHasKey('email', $orderSend);
        $this->assertEquals('test@mail.ru', $orderSend['email']);
        $this->assertArrayHasKey('phone', $orderSend);
        $this->assertEquals('+7 (000) 000-00-00', $orderSend['phone']);
        $this->assertArrayHasKey('createdAt', $orderSend);
        $this->assertArrayHasKey('delivery', $orderSend);
        $this->assertInternalType('array', $orderSend['delivery']);
        $this->assertEquals('flat', $orderSend['delivery']['code']);
        $this->assertEquals('Test', $orderSend['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $orderSend['delivery']['address']['region']);
        $this->assertEquals('111111', $orderSend['delivery']['address']['index']);
        $this->assertArrayHasKey('items', $orderSend);

        foreach($orderSend['items'] as $item) {
            $this->assertArrayHasKey('priceType', $item);
            $this->assertEquals('special1', $item['priceType']['code']);
        }

        $this->assertArrayHasKey('customerComment', $orderSend);
    }

    public function testOrderCreateWithoutCustomerTest()
    {
        $order_id = self::ORDER_ID;
        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order = $orderCheckoutModel->getOrder($order_id);
        $order['products'] = $orderAccountModel->getOrderProducts($order_id);
        $order['totals'] = $orderAccountModel->getOrderTotals($order_id);

        foreach($order['products'] as $key => $product) {
            $order['products'][$key]['option'] = $orderAccountModel->getOrderOptions($product['order_id'], $product['order_product_id']);
        }

        $responseCustomerCreate = new \RetailcrmApiResponse(
            201,
            json_encode(
                array(
                    'success' => true,
                    'id' => 1
                )
            )
        );

        $responseCustomerList = new \RetailcrmApiResponse(
            201,
            json_encode(
                array(
                    'success' => true,
                    "pagination"=> [
                    "limit"=>20,
                    "totalCount"=> 0,
                    "currentPage"=> 1,
                    "totalPageCount"=> 0
                ],
                "customers"=> []
                )
            )
        );

        $this->apiClientMock->expects($this->any())->method('customersList')->willReturn($responseCustomerList);
        $this->apiClientMock->expects($this->any())->method('customersCreate')->willReturn($responseCustomerCreate);
        $orderProcess = $this->orderModel->processOrder($order);
        $orderSend = $this->orderModel->sendToCrm($orderProcess, $this->apiClientMock, $order);

        $this->assertArrayHasKey('customer', $orderSend);
        $this->assertArrayNotHasKey('externalId', $orderSend['customer']);
        $this->assertArrayHasKey('id', $orderSend['customer']);
        $this->assertEquals(1, $orderSend['customer']['id']);

        foreach($orderSend['items'] as $item) {
            $this->assertArrayNotHasKey('priceType', $item);
        }
    }

    protected function setSetting($code, $data, $store_id = 0) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($code)) == $code) {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                }
            }
        }
    }

    private function getOrder($id)
    {
        return array(
            'payments' => array(
                array(
                    'id' => 1,
                    'status' => 'not-paid',
                    'type' => 'cod',
                    'externalId' => $id,
                    'amount' => '100'
                )
            )
        );
    }
}
