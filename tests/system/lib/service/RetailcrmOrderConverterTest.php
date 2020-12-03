<?php

require_once __DIR__ . '/../../../' . getenv('TEST_SUITE') . '/TestCase.php';

class RetailcrmOrderConverterTest extends TestCase {
    const CUSTOMER_ID = 1;
    const ORDER_WITH_CUST_ID = 1;
    const ORDER_ID = 2;

    public function testSetOrderData() {
        $order_checkout_model = $this->loadModel('checkout/order');
        $order_account_model = $this->loadModel('account/order');

        $order_data = $order_checkout_model->getOrder(static::ORDER_WITH_CUST_ID);
        $products = $order_account_model->getOrderProducts(static::ORDER_WITH_CUST_ID);
        $totals = $order_account_model->getOrderTotals(static::ORDER_WITH_CUST_ID);

        foreach ($products as $key => $product) {
            $productOptions = $order_account_model->getOrderOptions(static::ORDER_WITH_CUST_ID, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);

        $order = $converter->initOrderData(
            $order_data,
            $products,
            $totals
        )->setOrderData()->getOrder();

        $this->assertEquals('new', $order['status']);
        $this->assertEquals(static::ORDER_WITH_CUST_ID, $order['externalId']);
        $this->assertEquals(static::ORDER_WITH_CUST_ID, $order['number']);
        $this->assertEquals('Test', $order['firstName']);
        $this->assertEquals('Test', $order['lastName']);
        $this->assertEquals('test@mail.ru', $order['email']);
        $this->assertEquals('+7 (000) 000-00-00', $order['phone']);
        $this->assertEquals($order_data['date_added'], $order['createdAt']);
        $this->assertEquals($order_data['comment'], $order['customerComment']);
        $this->assertEquals(self::CUSTOMER_ID, $order['customer']['externalId']);
    }

    public function testSetPayment() {
        $order_checkout_model = $this->loadModel('checkout/order');
        $order_account_model = $this->loadModel('account/order');

        $order_data = $order_checkout_model->getOrder(static::ORDER_WITH_CUST_ID);
        $products = $order_account_model->getOrderProducts(static::ORDER_WITH_CUST_ID);
        $totals = $order_account_model->getOrderTotals(static::ORDER_WITH_CUST_ID);

        foreach ($products as $key => $product) {
            $productOptions = $order_account_model->getOrderOptions(static::ORDER_WITH_CUST_ID, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);

        $order = $converter->initOrderData(
            $order_data,
            $products,
            $totals
        )->setPayment()->getOrder();

        $this->assertArrayHasKey('payments', $order);
        $this->assertNotEmpty($order['payments']);
        $this->assertEquals('cod', $order['payments'][0]['type']);
    }

    public function testSetEmptyPayment() {
        $order_checkout_model = $this->loadModel('checkout/order');
        $order_account_model = $this->loadModel('account/order');

        $order_data = $order_checkout_model->getOrder(static::ORDER_WITH_CUST_ID);
        $products = $order_account_model->getOrderProducts(static::ORDER_WITH_CUST_ID);
        $totals = $order_account_model->getOrderTotals(static::ORDER_WITH_CUST_ID);

        foreach ($products as $key => $product) {
            $productOptions = $order_account_model->getOrderOptions(static::ORDER_WITH_CUST_ID, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);

        unset($order_data['payment_code']);

        $order = $converter->initOrderData(
            $order_data,
            $products,
            $totals
        )->setPayment()->getOrder();

        $this->assertArrayNotHasKey('payments', $order);
    }

    public function testSetDelivery() {
        $order_checkout_model = $this->loadModel('checkout/order');
        $order_account_model = $this->loadModel('account/order');

        $order_data = $order_checkout_model->getOrder(static::ORDER_WITH_CUST_ID);
        $products = $order_account_model->getOrderProducts(static::ORDER_WITH_CUST_ID);
        $totals = $order_account_model->getOrderTotals(static::ORDER_WITH_CUST_ID);

        foreach ($products as $key => $product) {
            $productOptions = $order_account_model->getOrderOptions(static::ORDER_WITH_CUST_ID, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);

        $order = $converter->initOrderData(
            $order_data,
            $products,
            $totals
        )->setDelivery()->getOrder();

        $this->assertArrayHasKey('delivery', $order);
        $this->assertNotEmpty($order['delivery']);
        $this->assertEquals('flat', $order['delivery']['code']);
        $this->assertEquals('Test', $order['delivery']['address']['city']);
        $this->assertEquals('Rostov-na-Donu', $order['delivery']['address']['region']);
        $this->assertEquals('111111', $order['delivery']['address']['index']);
    }

    public function testSetItems() {
        $order_checkout_model = $this->loadModel('checkout/order');
        $order_account_model = $this->loadModel('account/order');

        $order_data = $order_checkout_model->getOrder(static::ORDER_WITH_CUST_ID);
        $products = $order_account_model->getOrderProducts(static::ORDER_WITH_CUST_ID);
        $totals = $order_account_model->getOrderTotals(static::ORDER_WITH_CUST_ID);

        foreach ($products as $key => $product) {
            $productOptions = $order_account_model->getOrderOptions(static::ORDER_WITH_CUST_ID, $product['order_product_id']);

            if (!empty($productOptions)) {
                $products[$key]['option'] = $productOptions;
            }
        }

        $converter = \retailcrm\factory\OrderConverterFactory::create(static::$registry);

        $order = $converter->initOrderData(
            $order_data,
            $products,
            $totals
        )->setItems()->getOrder();

        $this->assertArrayHasKey('items', $order);
        $this->assertNotEmpty($order['items']);
    }
}
