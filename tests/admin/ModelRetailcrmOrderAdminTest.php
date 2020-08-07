<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmOrderAdminTest extends TestCase
{
    private $orderModel;
    private $apiClientMock;
    private $settingModel;

    const CUSTOMER_ID = 1;
    const ORDER_WITH_CUST_ID = 1;
    const ORDER_ID = 2;

    public function setUp()
    {
        parent::setUp();

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

        self::$registry->set(\RetailcrmProxy::class, $this->apiClientMock);
        $this->orderModel = $this->loadModel('extension/retailcrm/order');

        $this->settingModel = $this->loadModel('setting/setting');
    }

    public function testUploadToCrm()
    {
        $saleOrderModel = $this->loadModel('sale/order');
        $orders = $saleOrderModel->getOrders();
        $fullOrders = array();

        foreach ($orders as $order) {
            $fullOrder = $saleOrderModel->getOrder($order['order_id']);

            $fullOrder['totals'] = $saleOrderModel->getOrderTotals($order['order_id']);
            $fullOrder['products'] = $saleOrderModel->getOrderProducts($order['order_id']);

            foreach($fullOrder['products'] as $key => $product) {
                $fullOrder['products'][$key]['option'] = $saleOrderModel->getOrderOptions($product['order_id'], $product['order_product_id']);
            }

            $fullOrders[] = $fullOrder;
        }

        $chunkedOrders = $this->orderModel->uploadToCrm($fullOrders);

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
}
