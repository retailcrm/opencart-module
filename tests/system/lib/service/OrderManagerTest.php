<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class OrderManagerTest extends TestCase {
    const CUSTOMER_ID = 1;
    const ORDER_WITH_CUST_ID = 1;
    const ORDER_ID = 2;

    public function testCreateOrderWithCustomer() {
        $proxy = $this->getMockBuilder(\RetailcrmProxy::class)
            ->disableOriginalConstructor()
            ->setMethods(['ordersCreate'])
            ->getMock();

        $proxy->expects($this->once())->method('ordersCreate');

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer(
            $proxy, new \retailcrm\repository\CustomerRepository(static::$registry)
        );
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);

        $order_manager = new \retailcrm\service\OrderManager($proxy, $customer_manager, $converter, $corporate_customer, $settings_manager);

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

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer(
            $proxy, new \retailcrm\repository\CustomerRepository(static::$registry)
        );
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);
        $order_manager = new \retailcrm\service\OrderManager($proxy, $customer_manager, $converter, $corporate_customer, $settings_manager);

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

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer(
            $proxy, new \retailcrm\repository\CustomerRepository(static::$registry)
        );
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);
        $order_manager = new \retailcrm\service\OrderManager($proxy, $customer_manager, $converter, $corporate_customer, $settings_manager);

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

        $customer_manager = new \retailcrm\service\CustomerManager(
            $proxy,
            \retailcrm\factory\CustomerConverterFactory::create(static::$registry)
        );

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);
        $settings_manager = new \retailcrm\service\SettingsManager(static::$registry);
        $corporate_customer = new \retailcrm\service\CorporateCustomer(
            $proxy, new \retailcrm\repository\CustomerRepository(static::$registry)
        );
        $order_manager = new \retailcrm\service\OrderManager($proxy, $customer_manager, $converter, $corporate_customer, $settings_manager);

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
