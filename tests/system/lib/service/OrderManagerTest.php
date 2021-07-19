<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class OrderManagerTest extends TestCase {
    const CUSTOMER_ID = 1;
    const ORDER_WITH_CUST_ID = 1;
    const ORDER_ID = 2;

    public function testCreateOrderWithCustomer() {
        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['ordersCreate','customersGet'])
            ->getMock();

        $proxy->expects($this->once())->method('ordersCreate');

        $proxy->expects($this->once())
            ->method('customersGet')
            ->willReturn(new \RetailcrmApiResponse(
                200,
                json_encode(
                    [
                        'success' => true,
                        'pagination' => [
                            'limit'=> 20,
                            'totalCount' => 0,
                            'currentPage' => 1,
                            'totalPageCount' => 0
                        ],
                        'customer' => [
                            'id' => 1,
                            'externalId' => 1
                        ]
                    ]
                )
            ));

        $order_manager = $this->getOrderManager($proxy);

        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_WITH_CUST_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $products = $orderAccountModel->getOrderProducts($order_id);
        $totals = $orderAccountModel->getOrderTotals($order_id);

        foreach ($products as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $order_manager->createOrder($order, $products, $totals);
    }

    public function testCreateOrderWithoutExistingCustomer() {
        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['ordersCreate', 'customersList'])
            ->getMock();

        $proxy->expects($this->once())->method('ordersCreate');
        $proxy->expects($this->once())
            ->method('customersList')
            ->willReturn(new \RetailcrmApiResponse(
                200,
                json_encode(
                    array(
                        'success' => true,
                        'pagination' => [
                            'limit'=> 20,
                            'totalCount' => 0,
                            'currentPage' => 1,
                            'totalPageCount' => 0
                        ],
                        'customers' => [
                            [
                                'id' => 1,
                                'externalId' => 1
                            ]
                        ]
                    )
                )
            ));

        $order_manager = $this->getOrderManager($proxy);

        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $products = $orderAccountModel->getOrderProducts($order_id);
        $totals = $orderAccountModel->getOrderTotals($order_id);

        foreach ($products as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $order_manager->createOrder($order, $products, $totals);
    }

    public function testCreateOrderWithoutNotExistingCustomer() {
        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['ordersCreate', 'customersList', 'customersCreate'])
            ->getMock();

        $proxy->expects($this->once())->method('ordersCreate');
        $proxy->expects($this->once())
            ->method('customersList')
            ->willReturn(new \RetailcrmApiResponse(
                200,
                json_encode(
                    array(
                        'success' => true,
                        'pagination' => [
                            'limit'=> 20,
                            'totalCount' => 0,
                            'currentPage' => 1,
                            'totalPageCount' => 0
                        ],
                        'customers' => []
                    )
                )
            ));

        $proxy->expects($this->once())
            ->method('customersCreate')
            ->willReturn(new \RetailcrmApiResponse(
                201,
                json_encode(
                    array(
                        'success' => true,
                        'id' => 1
                    )
                )
            ));

        $order_manager = $this->getOrderManager($proxy);

        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $products = $orderAccountModel->getOrderProducts($order_id);
        $totals = $orderAccountModel->getOrderTotals($order_id);

        foreach ($products as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $order_manager->createOrder($order, $products, $totals);
    }

    public function testEditOrderWithCustomer() {
        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['ordersEdit', 'ordersGet', 'ordersPaymentEdit'])
            ->getMock();

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
                    'order' => $this->getOrder(self::ORDER_WITH_CUST_ID)
                )
            )
        );

        $proxy->expects($this->once())->method('ordersEdit')->willReturn($orderEditResponse);
        $proxy->expects($this->once())->method('ordersGet')->willReturn($ordersGetResponse);
        $proxy->expects($this->once())->method('ordersPaymentEdit');

        $order_manager = $this->getOrderManager($proxy);

        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_WITH_CUST_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $products = $orderAccountModel->getOrderProducts($order_id);
        $totals = $orderAccountModel->getOrderTotals($order_id);

        foreach ($products as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $order_manager->editOrder($order, $products, $totals);
    }

    public function testPrepareOrderTotals() {
        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->getMock();

        $order_manager = $this->getOrderManager($proxy);

        $orderCheckoutModel = $this->loadModel('checkout/order');
        $orderAccountModel = $this->loadModel('account/order');
        $order_id = self::ORDER_WITH_CUST_ID;
        $order = $orderCheckoutModel->getOrder($order_id);
        $products = $orderAccountModel->getOrderProducts($order_id);
        $totals = $this->getOrderTotals($order_id);

        foreach ($products as $key => $product) {
            $productOptions = $orderAccountModel->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $data = $order_manager->prepareOrder($order, $products, $totals);

        $this->assertEquals(15, $data['discountManualAmount']);
        $this->assertEquals(115, $data['payments'][0]['amount']);
    }

    private function getOrderManager($proxy)
    {
        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer(
            $proxy, new \retailcrm\repository\CustomerRepository(static::$registry)
        );
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        return new \retailcrm\service\OrderManager($proxy, $customer_manager, $converter, $corporate_customer, $settings_manager);
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

    private function getOrderTotals($id)
    {
        return array(
            array(
                "order_total_id" => "1",
                "order_id" => $id,
                "code" => "total",
                "title" => "Total",
                "value" => "115",
                "sort_order" => "9",
            ),
            array(
                "order_total_id" => "2",
                "order_id" => $id,
                "code" => "sub_total",
                "title" => "Sum",
                "value" => "110",
                "sort_order" => "1",
            ),
            array(
                "order_total_id" => "3",
                "order_id" => $id,
                "code" => "voucher",
                "title" => "Voucher",
                "value" => "15",
                "sort_order" => "9",
            ),
            array(
                "order_total_id" => "4",
                "order_id" => $id,
                "code" => "shipping",
                "title" => "Flat Shipping Rate",
                "value" => "5",
                "sort_order" => "3",
            ),
        );
    }
}
