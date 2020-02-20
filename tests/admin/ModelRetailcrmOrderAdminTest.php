<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmOrderAdminTest extends TestCase
{
    private $orderModel;
    private $apiClientMock;
    private $settingModel;
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
                'ordersUpload',
                'customersList',
                'ordersCreate',
                'ordersPaymentCreate',
                'customersCreate'
            ))
            ->getMock();

        $this->settingModel = $this->loadModel('setting/setting');
        $this->retailcrm = new \retailcrm\Retailcrm(self::$registry);

        $this->settingModel->editSetting(
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

    public function testUploadToCrm()
    {
        $saleOrderModel = $this->loadModel('sale/order');
        $orders = $saleOrderModel->getOrders();
        $fullOrders = array();

        foreach ($orders as $order) {
            $fullOrder = $saleOrderModel->getOrder($order['order_id']);

            $fullOrder['order_total'] = $saleOrderModel->getOrderTotals($order['order_id']);
            $fullOrder['products'] = $saleOrderModel->getOrderProducts($order['order_id']);

            foreach($fullOrder['products'] as $key => $product) {
                $fullOrder['products'][$key]['option'] = $saleOrderModel->getOrderOptions($product['order_id'], $product['order_product_id']);
            }

            $fullOrders[] = $fullOrder;
        }

        $chunkedOrders = $this->orderModel->uploadToCrm($fullOrders, $this->apiClientMock);

        $order = $chunkedOrders[0][0];

        $this->assertInternalType('array', $chunkedOrders);
        $this->assertInternalType('array', $chunkedOrders[0]);
        $this->assertNotEmpty($chunkedOrders[0]);
        $this->assertArrayHasKey('externalId', $order);
        $this->assertArrayHasKey('number', $order);
        $this->assertArrayHasKey('firstName', $order);
        $this->assertArrayHasKey('lastName', $order);
        $this->assertArrayHasKey('email', $order);
        $this->assertArrayHasKey('phone', $order);
        $this->assertArrayHasKey('createdAt', $order);
        $this->assertArrayHasKey('delivery', $order);
        $this->assertArrayHasKey('status', $order);
        $this->assertArrayHasKey('items', $order);
        $this->assertArrayHasKey('payments', $order);
        $this->assertNotEmpty($order['payments']);
    }

    public function testUploadWithCustomerTest()
    {
        $saleOrderModel = $this->loadModel('sale/order');
        $order = $saleOrderModel->getOrder(self::ORDER_WITH_CUST_ID);

        $order['totals'] = $saleOrderModel->getOrderTotals($order['order_id']);
        $order['products'] = $saleOrderModel->getOrderProducts($order['order_id']);

        foreach($order['products'] as $key => $product) {
            $order['products'][$key]['option'] = $saleOrderModel->getOrderOptions($product['order_id'], $product['order_product_id']);
        }

        $response = new \RetailcrmApiResponse(
            201,
            json_encode(
                array(
                    'success' => true,
                    'id' => 1
                )
            )
        );

        $this->apiClientMock->expects($this->any())->method('ordersCreate')->willReturn($response);
        $orderSend = $this->orderModel->uploadOrder($order, $this->apiClientMock);

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

    public function testUploadWithoutCustomerTest()
    {
        $saleOrderModel = $this->loadModel('sale/order');
        $order = $saleOrderModel->getOrder(self::ORDER_ID);

        $order['totals'] = $saleOrderModel->getOrderTotals($order['order_id']);
        $order['products'] = $saleOrderModel->getOrderProducts($order['order_id']);

        foreach($order['products'] as $key => $product) {
            $order['products'][$key]['option'] = $saleOrderModel->getOrderOptions($product['order_id'], $product['order_product_id']);
        }

        $response = new \RetailcrmApiResponse(
            201,
            json_encode(
                array(
                    'success' => true,
                    'id' => 1
                )
            )
        );

        $this->apiClientMock->expects($this->any())->method('ordersCreate')->willReturn($response);
        $this->apiClientMock->expects($this->any())->method('customersCreate')->willReturn($response);
        $orderSend = $this->orderModel->uploadOrder($order, $this->apiClientMock);

        $this->assertArrayHasKey('status', $orderSend);
        $this->assertArrayHasKey('externalId', $orderSend);
        $this->assertArrayHasKey('number', $orderSend);
        $this->assertArrayHasKey('firstName', $orderSend);
        $this->assertArrayHasKey('lastName', $orderSend);
        $this->assertArrayHasKey('email', $orderSend);
        $this->assertArrayHasKey('phone', $orderSend);
        $this->assertArrayHasKey('createdAt', $orderSend);
        $this->assertArrayHasKey('delivery', $orderSend);
        $this->assertArrayHasKey('items', $orderSend);
        $this->assertContains('#', $orderSend['items'][0]['offer']['externalId']);
        $this->assertArrayHasKey('payments', $orderSend);
        $this->assertArrayHasKey('customerComment', $orderSend);
        $this->assertArrayHasKey('customer', $orderSend);
        $this->assertArrayNotHasKey('externalId', $orderSend['customer']);
        $this->assertArrayHasKey('id', $orderSend['customer']);
        $this->assertNotEmpty($orderSend['payments']);
    }
}
