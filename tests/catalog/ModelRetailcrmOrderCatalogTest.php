<?php

class ModelRetailcrmOrderCatalogTest extends OpenCartTest
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
                'customersList'
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
                )
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
        $orderSend = $this->orderModel->sendToCrm($orderProcess, $this->apiClientMock);

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
        $orderProcess = $this->orderModel->processOrder($order);
        $orderSend = $this->orderModel->sendToCrm($orderProcess, $this->apiClientMock, false);

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
        $this->assertArrayHasKey('customerComment', $orderSend);
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
