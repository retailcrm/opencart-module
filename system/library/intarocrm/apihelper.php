<?php

require_once __DIR__ . '/vendor/autoload.php';

class ApiHelper
{
    private $dir, $fileDate;
    protected $intaroApi, $log, $settings;

    public function __construct($settings) {
        $this->dir = __DIR__ . '/../../logs/';
        $this->fileDate = $this->dir . 'intarocrm_history.log';
        $this->settings = $settings;
        $this->domain = $settings['domain'];

        $this->log = new Monolog\Logger('intarocrm');
        $this->log->pushHandler(
            new Monolog\Handler\StreamHandler($this->dir . 'intarocrm_module.log', Monolog\Logger::INFO)
        );

        $this->intaroApi = new IntaroCrm\RestApi(
            $settings['intarocrm_url'],
            $settings['intarocrm_apikey']
        );
    }

    public function processOrder($data) {

        $order = array();
        $customer = array();
        $customers = array();

        $payment_code = $data['payment_code'];
        $delivery_code = $data['shipping_code'];
        $settings = $this->settings;

        try {
            $customers = $this->intaroApi->customers($data['telephone'], $data['email'], $data['lastname'], 200, 0);
        } catch (IntaroCrm\Exception\ApiException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customers:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::customers:' . json_encode($data));
        } catch (IntaroCrm\Exception\CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customers::Curl:' . $e->getMessage());
        }


        if(count($customers) > 0 && isset($customers[0]['externalId'])) {
            $order['customerId'] = $customers[0]['externalId'];
        } else {
            $order['customerId'] = $data['customer_id'];
            $customer['externalId'] = $data['customer_id'];
            $customer['firstName'] = $data['firstname'];
            $customer['lastName'] = $data['lastname'];
            $customer['email'] = $data['email'];
            $customer['phones'] = array(array('number' => $data['telephone']));

            $customer['address']['text'] = implode(', ', array(
                $data['payment_postcode'],
                $data['payment_country'],
                $data['payment_city'],
                $data['payment_address_1'],
                $data['payment_address_2']
            ));

            try {
                $this->customer = $this->intaroApi->customerEdit($customer);
            } catch (IntaroCrm\Exception\ApiException $e) {
                $this->customer = $e->getMessage();
                $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . $e->getMessage());
                $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . json_encode($order));
            } catch (IntaroCrm\Exception\CurlException $e) {
                $this->customer = $e->getMessage();
                $this->log->addError('['.$this->domain.'] RestApi::orderCreate::Curl:' . $e->getMessage());
            }
        }

        unset($customer);
        unset($customers);

        $order['externalId'] = $data['order_id'];
        $order['firstName'] = $data['firstname'];
        $order['lastName'] = $data['lastname'];
        $order['email'] = $data['email'];
        $order['phone'] = $data['telephone'];
        $order['customerComment'] = $data['comment'];

        $deliveryCost = 0;
        foreach ($data['totals'] as $totals) {
            if ($totals['code'] == 'shipping') {
                $deliveryCost = $totals['value'];
            }
        }

        $order['createdAt'] = date('Y-m-d H:i:s');
        $order['paymentType'] = $settings['intarocrm_payment'][$payment_code];

        $order['delivery'] = array(
            'code' => $settings['intarocrm_delivery'][$delivery_code],
            'cost' => $deliveryCost,
            'address' => array('text' => implode(', ', array(
                $data['shipping_postcode'],
                $data['shipping_country'],
                $data['shipping_city'],
                $data['shipping_address_1'],
                $data['shipping_address_2']
            )))
        );


        foreach ($data['products'] as $product) {
            $order['items'][] = array(
                'productId' => $product['product_id'],
                'productName' => $product['name'] . ' '. $product['model'],
                'initialPrice' => $product['price'],
                'quantity' => $product['quantity'],
            );
        }

        try {
            $this->intaroApi->orderEdit($order);
        } catch (IntaroCrm\Exception\ApiException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . json_encode($order));
        } catch (IntaroCrm\Exception\CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderCreate::Curl:' . $e->getMessage());
        }
    }

    public function orderHistory() {

        $orders = array();

        try {
            $orders = $this->intaroApi->orderHistory($this->getDate());
            $this->saveDate($this->intaroApi->getGeneratedAt()->format('Y-m-d H:i:s'));
        } catch (IntaroCrm\Exception\ApiException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderHistory:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderHistory:' . json_encode($orders));

            return false;
        } catch (IntaroCrm\Exception\CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderHistory::Curl:' . $e->getMessage());

            return false;
        }

        return $orders;
    }

    public function orderFixExternalIds($data)
    {
        try {
            return $this->intaroApi->orderFixExternalIds($data);
        } catch (IntaroCrm\Exception\ApiException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . json_encode($data));

            return false;
        } catch (IntaroCrm\Exception\CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds::Curl:' . $e->getMessage());

            return false;
        }
    }

    public function customerFixExternalIds($data)
    {
        try {
            return $this->intaroApi->customerFixExternalIds($data);
        } catch (IntaroCrm\Exception\ApiException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customerFixExternalIds:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::customerFixExternalIds:' . json_encode($data));

            return false;
        } catch (IntaroCrm\Exception\CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customerFixExternalIds::Curl:' . $e->getMessage());

            return false;
        }
    }

    public function getOrderItems($order_id)
    {
        $order = '';
        try {
            $order = $this->intaroApi->orderGet($order_id);

            return $order['items'];
        } catch (IntaroCrm\Exception\ApiException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . json_encode($order));

            return false;
        } catch (IntaroCrm\Exception\CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds::Curl:' . $e->getMessage());

            return false;
        }

    }

    private function saveDate($date) {
        file_put_contents($this->fileDate, $date, LOCK_EX);
    }

    private function getDate() {
        if (file_exists($this->fileDate)) {
            $result = file_get_contents($this->fileDate);
        } else {
            $result = date('Y-m-d H:i:s', strtotime('-2 days', strtotime(date('Y-m-d H:i:s'))));
        }

        return $result;
    }

}
