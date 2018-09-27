<?php

namespace Tests;

class OrderRetailcrmLibraryTest extends OpenCartTest
{
    private $order;
    private $apiClientMock;
    private $retailcrm;

    const CUSTOMER_ID = 1;
    const ORDER_WITH_CUST_ID = 1;
    const ORDER_ID = 2;

    public function setUp()
    {
        parent::setUp();

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

        $this->retailcrm = new \Retailcrm\Retailcrm(self::$registry);

        $this->order = $this->retailcrm->createObject(\Retailcrm\Order::class);

        $this->setSetting(
            \Retailcrm\Retailcrm::MODULE,
            array(
                \Retailcrm\Retailcrm::MODULE . '_apiversion' => 'v5',
                \Retailcrm\Retailcrm::MODULE . '_order_number' => 1,
                \Retailcrm\Retailcrm::MODULE . '_status' => array(
                    1 => 'new'
                ),
                \Retailcrm\Retailcrm::MODULE . '_delivery' => array(
                    'flat.flat' => 'flat'
                ),
                \Retailcrm\Retailcrm::MODULE . '_payment' => array(
                    'cod' => 'cod'
                )
            )
        );
    }

    public function testPrepareCreateOrderWithCustomer()
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

        $this->order->prepare($order);
        $orderPrepare = $this->order->getData();

        $this->assertArrayHasKey('status', $orderPrepare);
        $this->assertEquals('new', $orderPrepare['status']);
        $this->assertArrayHasKey('externalId', $orderPrepare);
        $this->assertArrayHasKey('number', $orderPrepare);
        $this->assertArrayHasKey('firstName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['firstName']);
        $this->assertArrayHasKey('lastName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['lastName']);
        $this->assertArrayHasKey('email', $orderPrepare);
        $this->assertEquals('test@mail.ru', $orderPrepare['email']);
        $this->assertArrayHasKey('phone', $orderPrepare);
        $this->assertEquals('+7 (000) 000-00-00', $orderPrepare['phone']);
        $this->assertArrayHasKey('createdAt', $orderPrepare);
        $this->assertArrayHasKey('delivery', $orderPrepare);
        $this->assertInternalType('array', $orderPrepare['delivery']);
        $this->assertEquals('flat', $orderPrepare['delivery']['code']);
        $this->assertEquals('Test', $orderPrepare['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $orderPrepare['delivery']['address']['region']);
        $this->assertEquals('111111', $orderPrepare['delivery']['address']['index']);
        $this->assertArrayHasKey('items', $orderPrepare);
        $this->assertArrayHasKey('customer', $orderPrepare);
        $this->assertArrayHasKey('externalId', $orderPrepare['customer']);
        $this->assertEquals(self::CUSTOMER_ID, $orderPrepare['customer']['externalId']);
        $this->assertArrayHasKey('payments', $orderPrepare);
        $this->assertEquals('cod', $orderPrepare['payments'][0]['type']);
        $this->assertNotEmpty($orderPrepare['payments']);
    }

    public function testPrepareEditOrderWithCustomer()
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
        $this->order->prepare($order);
        $orderPrepare = $this->order->getData();

        $this->assertArrayHasKey('status', $orderPrepare);
        $this->assertEquals('new', $orderPrepare['status']);
        $this->assertArrayHasKey('externalId', $orderPrepare);
        $this->assertArrayHasKey('number', $orderPrepare);
        $this->assertArrayHasKey('firstName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['firstName']);
        $this->assertArrayHasKey('lastName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['lastName']);
        $this->assertArrayHasKey('email', $orderPrepare);
        $this->assertEquals('test@mail.ru', $orderPrepare['email']);
        $this->assertArrayHasKey('phone', $orderPrepare);
        $this->assertEquals('+7 (000) 000-00-00', $orderPrepare['phone']);
        $this->assertArrayHasKey('createdAt', $orderPrepare);
        $this->assertArrayHasKey('delivery', $orderPrepare);
        $this->assertInternalType('array', $orderPrepare['delivery']);
        $this->assertEquals('flat', $orderPrepare['delivery']['code']);
        $this->assertEquals('Test', $orderPrepare['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $orderPrepare['delivery']['address']['region']);
        $this->assertEquals('111111', $orderPrepare['delivery']['address']['index']);
        $this->assertArrayHasKey('items', $orderPrepare);
        $this->assertArrayHasKey('customer', $orderPrepare);
        $this->assertArrayHasKey('externalId', $orderPrepare['customer']);
        $this->assertEquals(self::CUSTOMER_ID, $orderPrepare['customer']['externalId']);
    }

    public function testPrepareCreateOrderWithoutCustomer()
    {
        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $order['products'] = $orderAccountModel->getOrderProducts($order_id);
        $order['totals'] = $orderAccountModel->getOrderTotals($order_id);

        foreach ($order['products'] as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $order['products'][$key]['option'] = $productOptions;
            }
        }

        $this->order->prepare($order);
        $orderPrepare = $this->order->getData();

        $this->assertArrayHasKey('status', $orderPrepare);
        $this->assertEquals('new', $orderPrepare['status']);
        $this->assertArrayHasKey('externalId', $orderPrepare);
        $this->assertArrayHasKey('number', $orderPrepare);
        $this->assertArrayHasKey('firstName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['firstName']);
        $this->assertArrayHasKey('lastName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['lastName']);
        $this->assertArrayHasKey('email', $orderPrepare);
        $this->assertEquals('test@mail.ru', $orderPrepare['email']);
        $this->assertArrayHasKey('phone', $orderPrepare);
        $this->assertEquals('+7 (000) 000-00-00', $orderPrepare['phone']);
        $this->assertArrayHasKey('createdAt', $orderPrepare);
        $this->assertArrayHasKey('delivery', $orderPrepare);
        $this->assertInternalType('array', $orderPrepare['delivery']);
        $this->assertEquals('flat', $orderPrepare['delivery']['code']);
        $this->assertEquals('Test', $orderPrepare['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $orderPrepare['delivery']['address']['region']);
        $this->assertEquals('111111', $orderPrepare['delivery']['address']['index']);
        $this->assertArrayHasKey('items', $orderPrepare);
        $this->assertArrayNotHasKey('customer', $orderPrepare);
        $this->assertArrayHasKey('payments', $orderPrepare);
        $this->assertEquals('cod', $orderPrepare['payments'][0]['type']);
        $this->assertNotEmpty($orderPrepare['payments']);
    }

    public function testPrepareEditOrderWithoutCustomer()
    {
        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_ID;
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
        $this->order->prepare($order);
        $orderPrepare = $this->order->getData();

        $this->assertArrayHasKey('status', $orderPrepare);
        $this->assertEquals('new', $orderPrepare['status']);
        $this->assertArrayHasKey('externalId', $orderPrepare);
        $this->assertArrayHasKey('number', $orderPrepare);
        $this->assertArrayHasKey('firstName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['firstName']);
        $this->assertArrayHasKey('lastName', $orderPrepare);
        $this->assertEquals('Test', $orderPrepare['lastName']);
        $this->assertArrayHasKey('email', $orderPrepare);
        $this->assertEquals('test@mail.ru', $orderPrepare['email']);
        $this->assertArrayHasKey('phone', $orderPrepare);
        $this->assertEquals('+7 (000) 000-00-00', $orderPrepare['phone']);
        $this->assertArrayHasKey('createdAt', $orderPrepare);
        $this->assertArrayHasKey('delivery', $orderPrepare);
        $this->assertInternalType('array', $orderPrepare['delivery']);
        $this->assertEquals('flat', $orderPrepare['delivery']['code']);
        $this->assertEquals('Test', $orderPrepare['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $orderPrepare['delivery']['address']['region']);
        $this->assertEquals('111111', $orderPrepare['delivery']['address']['index']);
        $this->assertArrayHasKey('items', $orderPrepare);
        $this->assertArrayNotHasKey('customer', $orderPrepare);
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