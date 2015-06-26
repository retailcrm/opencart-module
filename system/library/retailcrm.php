<?php

class ApiHelper
{
    private $dir, $fileDate;
    protected $api, $log, $settings;

    public function __construct($settings) {

        $this->settings = $settings;
        $this->domain = $settings['domain'];
        $this->lastRun = $settings['retailcrm_history'];

        $this->api = new Client(
            $settings['retailcrm_url'],
            $settings['retailcrm_apikey']
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
            $customers = $this->api->customers($data['telephone'], $data['email'], $data['lastname'], 200, 0);
        } catch (CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customers:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::customers:' . json_encode($data));
        } catch (InvalidJsonException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customers::Curl:' . $e->getMessage());
        }


        if(count($customers) > 0 && isset($customers[0]['externalId'])) {
            $order['customerId'] = $customers[0]['externalId'];
        } else {
            $order['customerId'] = ($data['customer_id'] != '') ? $data['customer_id'] : (int) substr((microtime(true) * 10000) . mt_rand(1, 1000), 10, -1);
            $customer['externalId'] = $order['customerId'];
            $customer['firstName'] = $data['firstname'];
            $customer['lastName'] = $data['lastname'];
            $customer['email'] = $data['email'];
            $customer['phones'] = array(array('number' => $data['telephone']));

            $customer['address']['country'] = $data['payment_country_id'];
            $customer['address']['region'] = $data['payment_zone_id'];

            $customer['address']['text'] = implode(', ', array(
                $data['payment_postcode'],
                $data['payment_country'],
                $data['payment_city'],
                $data['payment_address_1'],
                $data['payment_address_2']
            ));

            try {
                $this->customer = $this->api->customerEdit($customer);
            } catch (CurlException $e) {
                $this->customer = $e->getMessage();
                $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . $e->getMessage());
                $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . json_encode($order));
            } catch (InvalidJsonException $e) {
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
        $orderTotals = isset($data['totals']) ? $data['totals'] : $data['order_total'] ;

        foreach ($orderTotals as $totals) {
            if ($totals['code'] == 'shipping') {
                $deliveryCost = $totals['value'];
            }
        }

        $order['createdAt'] = date('Y-m-d H:i:s');
        $order['paymentType'] = $settings['intarocrm_payment'][$payment_code];

        $country = (isset($data['shipping_country'])) ? $data['shipping_country'] : '' ;

        $order['delivery'] = array(
            'code' => $settings['intarocrm_delivery'][$delivery_code],
            'cost' => $deliveryCost,
            'address' => array(
                'index' => $data['shipping_postcode'],
                'city' => $data['shipping_city'],
                'country' => $data['shipping_country_id'],
                'region' => $data['shipping_zone_id'],
                'text' => implode(', ', array(
                    $data['shipping_postcode'],
                    $country,
                    $data['shipping_city'],
                    $data['shipping_address_1'],
                    $data['shipping_address_2']
                ))
            )
        );

        $orderProducts = isset($data['products']) ? $data['products'] : $data['order_product'];

        foreach ($orderProducts as $product) {
            $order['items'][] = array(
                'productId' => $product['product_id'],
                'productName' => $product['name'],
                'initialPrice' => $product['price'],
                'quantity' => $product['quantity'],
            );
        }

        if (isset($data['order_status_id'])) {
            $order['status'] = $data['order_status'];
        }

        try {
            $this->api->orderEdit($order);
        } catch (CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderCreate:' . json_encode($order));
        } catch (InvalidJsonException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderCreate::Curl:' . $e->getMessage());
        }
    }

    public function ordersHistory() {

        $orders = array();

        try {
            $orders = $this->api->ordersHistory($this->getDate());
            $this->saveDate($this->api->getGeneratedAt()->format('Y-m-d H:i:s'));
        } catch (CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderHistory:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderHistory:' . json_encode($orders));

            return false;
        } catch (InvalidJsonException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderHistory::Curl:' . $e->getMessage());

            return false;
        }

        return $orders;
    }

    public function orderFixExternalIds($data)
    {
        try {
            return $this->api->orderFixExternalIds($data);
        } catch (CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . json_encode($data));

            return false;
        } catch (InvalidJsonException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds::Curl:' . $e->getMessage());

            return false;
        }
    }

    public function customerFixExternalIds($data)
    {
        try {
            return $this->api->customerFixExternalIds($data);
        } catch (CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customerFixExternalIds:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::customerFixExternalIds:' . json_encode($data));

            return false;
        } catch (InvalidJsonException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::customerFixExternalIds::Curl:' . $e->getMessage());

            return false;
        }
    }

    public function getOrder($order_id)
    {
        try {
            return $this->api->orderGet($order_id);
        } catch (CurlException $e) {
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . $e->getMessage());
            $this->log->addError('['.$this->domain.'] RestApi::orderFixExternalIds:' . json_encode($order));

            return false;
        } catch (InvalidJsonException $e) {
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


/**
 * HTTP client
 */
class Client
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    protected $url;
    protected $defaultParameters;
    protected $retry;

    public function __construct($url, array $defaultParameters = array())
    {
        if (false === stripos($url, 'https://')) {
            throw new InvalidArgumentException('API schema requires HTTPS protocol');
        }

        $this->url = $url;
        $this->defaultParameters = $defaultParameters;
        $this->retry = 0;
    }

    /**
     * Make HTTP request
     *
     * @param string $path
     * @param string $method (default: 'GET')
     * @param array $parameters (default: array())
     * @param int $timeout
     * @param bool $verify
     * @param bool $debug
     * @return ApiResponse
     */
    public function makeRequest(
        $path,
        $method,
        array $parameters = array(),
        $timeout = 30,
        $verify = false,
        $debug = false
    ) {
        $allowedMethods = array(self::METHOD_GET, self::METHOD_POST);
        if (!in_array($method, $allowedMethods)) {
            throw new InvalidArgumentException(sprintf(
                'Method "%s" is not valid. Allowed methods are %s',
                $method,
                implode(', ', $allowedMethods)
            ));
        }

        $parameters = array_merge($this->defaultParameters, $parameters);

        $url = $this->url . $path;

        if (self::METHOD_GET === $method && sizeof($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verify);

        if (!$debug) {
            curl_setopt($ch, CURLOPT_TIMEOUT, (int) $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
        } else {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, (int) $timeout + ($this->retry * 2000));
        }

        if (self::METHOD_POST === $method) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        }

        $responseBody = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        curl_close($ch);

        if ($errno && in_array($errno, array(6, 7, 28, 34, 35)) && $this->retry < 3) {
            $errno = null;
            $error = null;
            $this->retry += 1;
            $this->makeRequest(
                $path,
                $method,
                $parameters,
                $timeout,
                $verify,
                $debug
            );
        }

        if ($errno) {
            throw new CurlException($error, $errno);
        }

        return new ApiResponse($statusCode, $responseBody);
    }

    public function getRetry()
    {
        return $this->retry;
    }
}

/**
 * Response from retailCRM API
 */
class ApiResponse implements ArrayAccess
{
    // HTTP response status code
    protected $statusCode;

    // response assoc array
    protected $response;

    public function __construct($statusCode, $responseBody = null)
    {
        $this->statusCode = (int) $statusCode;

        if (!empty($responseBody)) {
            $response = json_decode($responseBody, true);

            if (!$response && JSON_ERROR_NONE !== ($error = json_last_error())) {
                throw new InvalidJsonException(
                    "Invalid JSON in the API response body. Error code #$error",
                    $error
                );
            }

            $this->response = $response;
        }
    }

    /**
     * Return HTTP response status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * HTTP request was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->statusCode < 400;
    }

    /**
     * Allow to access for the property throw class method
     *
     * @param  string $name
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // convert getSomeProperty to someProperty
        $propertyName = strtolower(substr($name, 3, 1)) . substr($name, 4);

        if (!isset($this->response[$propertyName])) {
            throw new InvalidArgumentException("Method \"$name\" not found");
        }

        return $this->response[$propertyName];
    }

    /**
     * Allow to access for the property throw object property
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->response[$name])) {
            throw new InvalidArgumentException("Property \"$name\" not found");
        }

        return $this->response[$name];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('This activity not allowed');
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('This call not allowed');
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->response[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if (!isset($this->response[$offset])) {
            throw new InvalidArgumentException("Property \"$offset\" not found");
        }

        return $this->response[$offset];
    }
}


/**
 *  retailCRM API client class
 */
class ApiClient
{
    const VERSION = 'v3';

    protected $client;

    /**
     * Site code
     */
    protected $siteCode;

    /**
     * Client creating
     *
     * @param  string $url
     * @param  string $apiKey
     * @param  string $siteCode
     * @return mixed
     */
    public function __construct($url, $apiKey, $site = null)
    {
        if ('/' != substr($url, strlen($url) - 1, 1)) {
            $url .= '/';
        }

        $url = $url . 'api/' . self::VERSION;

        $this->client = new Client($url, array('apiKey' => $apiKey));
        $this->siteCode = $site;
    }

    /**
     * Create a order
     *
     * @param  array       $order
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function ordersCreate(array $order, $site = null)
    {
        if (!sizeof($order)) {
            throw new InvalidArgumentException('Parameter `order` must contains a data');
        }

        return $this->client->makeRequest("/orders/create", Client::METHOD_POST, $this->fillSite($site, array(
            'order' => json_encode($order)
        )));
    }

    /**
     * Edit a order
     *
     * @param  array       $order
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function ordersEdit(array $order, $by = 'externalId', $site = null)
    {
        if (!sizeof($order)) {
            throw new InvalidArgumentException('Parameter `order` must contains a data');
        }

        $this->checkIdParameter($by);

        if (!isset($order[$by])) {
            throw new InvalidArgumentException(sprintf('Order array must contain the "%s" parameter.', $by));
        }

        return $this->client->makeRequest(
            "/orders/" . $order[$by] . "/edit",
            Client::METHOD_POST,
            $this->fillSite($site, array(
                'order' => json_encode($order),
                'by' => $by,
            ))
        );
    }

    /**
     * Upload array of the orders
     *
     * @param  array       $orders
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function ordersUpload(array $orders, $site = null)
    {
        if (!sizeof($orders)) {
            throw new InvalidArgumentException('Parameter `orders` must contains array of the orders');
        }

        return $this->client->makeRequest("/orders/upload", Client::METHOD_POST, $this->fillSite($site, array(
            'orders' => json_encode($orders),
        )));
    }

    /**
     * Get order by id or externalId
     *
     * @param  string      $id
     * @param  string      $by (default: 'externalId')
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function ordersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest("/orders/$id", Client::METHOD_GET, $this->fillSite($site, array(
            'by' => $by
        )));
    }

    /**
     * Returns a orders history
     *
     * @param  DateTime   $startDate (default: null)
     * @param  DateTime   $endDate (default: null)
     * @param  int         $limit (default: 100)
     * @param  int         $offset (default: 0)
     * @param  bool        $skipMyChanges (default: true)
     * @return ApiResponse
     */
    public function ordersHistory(
        DateTime $startDate = null,
        DateTime $endDate = null,
        $limit = 100,
        $offset = 0,
        $skipMyChanges = true
    ) {
        $parameters = array();

        if ($startDate) {
            $parameters['startDate'] = $startDate->format('Y-m-d H:i:s');
        }
        if ($endDate) {
            $parameters['endDate'] = $endDate->format('Y-m-d H:i:s');
        }
        if ($limit) {
            $parameters['limit'] = (int) $limit;
        }
        if ($offset) {
            $parameters['offset'] = (int) $offset;
        }
        if ($skipMyChanges) {
            $parameters['skipMyChanges'] = (bool) $skipMyChanges;
        }

        return $this->client->makeRequest('/orders/history', Client::METHOD_GET, $parameters);
    }

    /**
     * Returns filtered orders list
     *
     * @param  array       $filter (default: array())
     * @param  int         $page (default: null)
     * @param  int         $limit (default: null)
     * @return ApiResponse
     */
    public function ordersList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (sizeof($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest('/orders', Client::METHOD_GET, $parameters);
    }

    /**
     * Returns statuses of the orders
     *
     * @param  array       $ids (default: array())
     * @param  array       $externalIds (default: array())
     * @return ApiResponse
     */
    public function ordersStatuses(array $ids = array(), array $externalIds = array())
    {
        $parameters = array();

        if (sizeof($ids)) {
            $parameters['ids'] = $ids;
        }
        if (sizeof($externalIds)) {
            $parameters['externalIds'] = $externalIds;
        }

        return $this->client->makeRequest('/orders/statuses', Client::METHOD_GET, $parameters);
    }

    /**
     * Save order IDs' (id and externalId) association in the CRM
     *
     * @param  array       $ids
     * @return ApiResponse
     */
    public function ordersFixExternalIds(array $ids)
    {
        if (!sizeof($ids)) {
            throw new InvalidArgumentException('Method parameter must contains at least one IDs pair');
        }

        return $this->client->makeRequest("/orders/fix-external-ids", Client::METHOD_POST, array(
            'orders' => json_encode($ids),
        ));
    }

    /**
     * Get orders assembly history
     *
     * @param  array       $filter (default: array())
     * @param  int         $page (default: null)
     * @param  int         $limit (default: null)
     * @return ApiResponse
     */
    public function ordersPacksHistory(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (sizeof($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest('/orders/packs/history', Client::METHOD_GET, $parameters);
    }

    /**
     * Create a customer
     *
     * @param  array       $customer
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function customersCreate(array $customer, $site = null)
    {
        if (!sizeof($customer)) {
            throw new InvalidArgumentException('Parameter `customer` must contains a data');
        }

        return $this->client->makeRequest("/customers/create", Client::METHOD_POST, $this->fillSite($site, array(
            'customer' => json_encode($customer)
        )));
    }

    /**
     * Edit a customer
     *
     * @param  array       $customer
     * @param  string      $by (default: 'externalId')
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function customersEdit(array $customer, $by = 'externalId', $site = null)
    {
        if (!sizeof($customer)) {
            throw new InvalidArgumentException('Parameter `customer` must contains a data');
        }

        $this->checkIdParameter($by);

        if (!isset($customer[$by])) {
            throw new InvalidArgumentException(sprintf('Customer array must contain the "%s" parameter.', $by));
        }

        return $this->client->makeRequest(
            "/customers/" . $customer[$by] . "/edit",
            Client::METHOD_POST,
            $this->fillSite(
                $site,
                array(
                    'customer' => json_encode($customer),
                    'by' => $by
                )
            )
        );
    }

    /**
     * Upload array of the customers
     *
     * @param  array       $customers
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function customersUpload(array $customers, $site = null)
    {
        if (!sizeof($customers)) {
            throw new InvalidArgumentException('Parameter `customers` must contains array of the customers');
        }

        return $this->client->makeRequest("/customers/upload", Client::METHOD_POST, $this->fillSite($site, array(
            'customers' => json_encode($customers),
        )));
    }

    /**
     * Get customer by id or externalId
     *
     * @param  string      $id
     * @param  string      $by (default: 'externalId')
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function customersGet($id, $by = 'externalId', $site = null)
    {
        $this->checkIdParameter($by);

        return $this->client->makeRequest("/customers/$id", Client::METHOD_GET, $this->fillSite($site, array(
            'by' => $by
        )));
    }

    /**
     * Returns filtered customers list
     *
     * @param  array       $filter (default: array())
     * @param  int         $page (default: null)
     * @param  int         $limit (default: null)
     * @return ApiResponse
     */
    public function customersList(array $filter = array(), $page = null, $limit = null)
    {
        $parameters = array();

        if (sizeof($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest('/customers', Client::METHOD_GET, $parameters);
    }

    /**
     * Save customer IDs' (id and externalId) association in the CRM
     *
     * @param  array       $ids
     * @return ApiResponse
     */
    public function customersFixExternalIds(array $ids)
    {
        if (!sizeof($ids)) {
            throw new InvalidArgumentException('Method parameter must contains at least one IDs pair');
        }

        return $this->client->makeRequest("/customers/fix-external-ids", Client::METHOD_POST, array(
            'customers' => json_encode($ids),
        ));
    }

    /**
     * Get purchace prices & stock balance
     *
     * @param  array  $filter (default: array())
     * @param  int    $page (default: null)
     * @param  int    $limit (default: null)
     * @param  string $site (default: null)
     * @return ApiResponse
     */
    public function storeInventories(array $filter = array(), $page = null, $limit = null, $site = null)
    {
        $parameters = array();

        if (sizeof($filter)) {
            $parameters['filter'] = $filter;
        }
        if (null !== $page) {
            $parameters['page'] = (int) $page;
        }
        if (null !== $limit) {
            $parameters['limit'] = (int) $limit;
        }

        return $this->client->makeRequest('/store/inventories', Client::METHOD_GET, $this->fillSite($site, $parameters));
    }

    /**
     * Upload store inventories
     *
     * @param  array       $offers
     * @param  string      $site (default: null)
     * @return ApiResponse
     */
    public function storeInventoriesUpload(array $offers, $site = null)
    {
        if (!sizeof($offers)) {
            throw new InvalidArgumentException('Parameter `offers` must contains array of the customers');
        }

        return $this->client->makeRequest(
            "/store/inventories/upload",
            Client::METHOD_POST,
            $this->fillSite($site, array('offers' => json_encode($offers)))
        );
    }

    /**
     * Returns deliveryServices list
     *
     * @return ApiResponse
     */
    public function deliveryServicesList()
    {
        return $this->client->makeRequest('/reference/delivery-services', Client::METHOD_GET);
    }

    /**
     * Returns deliveryTypes list
     *
     * @return ApiResponse
     */
    public function deliveryTypesList()
    {
        return $this->client->makeRequest('/reference/delivery-types', Client::METHOD_GET);
    }

    /**
     * Returns orderMethods list
     *
     * @return ApiResponse
     */
    public function orderMethodsList()
    {
        return $this->client->makeRequest('/reference/order-methods', Client::METHOD_GET);
    }

    /**
     * Returns orderTypes list
     *
     * @return ApiResponse
     */
    public function orderTypesList()
    {
        return $this->client->makeRequest('/reference/order-types', Client::METHOD_GET);
    }

    /**
     * Returns paymentStatuses list
     *
     * @return ApiResponse
     */
    public function paymentStatusesList()
    {
        return $this->client->makeRequest('/reference/payment-statuses', Client::METHOD_GET);
    }

    /**
     * Returns paymentTypes list
     *
     * @return ApiResponse
     */
    public function paymentTypesList()
    {
        return $this->client->makeRequest('/reference/payment-types', Client::METHOD_GET);
    }

    /**
     * Returns productStatuses list
     *
     * @return ApiResponse
     */
    public function productStatusesList()
    {
        return $this->client->makeRequest('/reference/product-statuses', Client::METHOD_GET);
    }

    /**
     * Returns statusGroups list
     *
     * @return ApiResponse
     */
    public function statusGroupsList()
    {
        return $this->client->makeRequest('/reference/status-groups', Client::METHOD_GET);
    }

    /**
     * Returns statuses list
     *
     * @return ApiResponse
     */
    public function statusesList()
    {
        return $this->client->makeRequest('/reference/statuses', Client::METHOD_GET);
    }

    /**
     * Returns sites list
     *
     * @return ApiResponse
     */
    public function sitesList()
    {
        return $this->client->makeRequest('/reference/sites', Client::METHOD_GET);
    }

    /**
     * Returns stores list
     *
     * @return ApiResponse
     */
    public function storesList()
    {
        return $this->client->makeRequest('/reference/stores', Client::METHOD_GET);
    }

    /**
     * Edit deliveryService
     *
     * @param array $data delivery service data
     * @return ApiResponse
     */
    public function deliveryServicesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/delivery-services/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'deliveryService' => json_encode($data)
            )
        );
    }

    /**
     * Edit deliveryType
     *
     * @param array $data delivery type data
     * @return ApiResponse
     */
    public function deliveryTypesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/delivery-types/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'deliveryType' => json_encode($data)
            )
        );
    }

    /**
     * Edit orderMethod
     *
     * @param array $data order method data
     * @return ApiResponse
     */
    public function orderMethodsEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/order-methods/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'orderMethod' => json_encode($data)
            )
        );
    }

    /**
     * Edit orderType
     *
     * @param array $data order type data
     * @return ApiResponse
     */
    public function orderTypesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/order-types/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'orderType' => json_encode($data)
            )
        );
    }

    /**
     * Edit paymentStatus
     *
     * @param array $data payment status data
     * @return ApiResponse
     */
    public function paymentStatusesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/payment-statuses/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'paymentStatus' => json_encode($data)
            )
        );
    }

    /**
     * Edit paymentType
     *
     * @param array $data payment type data
     * @return ApiResponse
     */
    public function paymentTypesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/payment-types/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'paymentType' => json_encode($data)
            )
        );
    }

    /**
     * Edit productStatus
     *
     * @param array $data product status data
     * @return ApiResponse
     */
    public function productStatusesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/product-statuses/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'productStatus' => json_encode($data)
            )
        );
    }

    /**
     * Edit order status
     *
     * @param array $data status data
     * @return ApiResponse
     */
    public function statusesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/statuses/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'status' => json_encode($data)
            )
        );
    }

    /**
     * Edit site
     *
     * @param array $data site data
     * @return ApiResponse
     */
    public function sitesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/sites/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'site' => json_encode($data)
            )
        );
    }

    /**
     * Edit store
     *
     * @param array $data site data
     * @return ApiResponse
     */
    public function storesEdit(array $data)
    {
        if (!isset($data['code'])) {
            throw new InvalidArgumentException('Data must contain "code" parameter.');
        }

        if (!isset($data['name'])) {
            throw new InvalidArgumentException('Data must contain "name" parameter.');
        }

        return $this->client->makeRequest(
            '/reference/stores/' . $data['code'] . '/edit',
            Client::METHOD_POST,
            array(
                'store' => json_encode($data)
            )
        );
    }

    /**
     * Update CRM basic statistic
     *
     * @return ApiResponse
     */
    public function statisticUpdate()
    {
        return $this->client->makeRequest('/statistic/update', Client::METHOD_GET);
    }

    /**
     * Return current site
     *
     * @return string
     */
    public function getSite()
    {
        return $this->siteCode;
    }

    /**
     * Set site
     *
     * @param  string $site
     * @return void
     */
    public function setSite($site)
    {
        $this->siteCode = $site;
    }

    /**
     * Check ID parameter
     *
     * @param  string $by
     * @return bool
     */
    protected function checkIdParameter($by)
    {
        $allowedForBy = array('externalId', 'id');
        if (!in_array($by, $allowedForBy)) {
            throw new InvalidArgumentException(sprintf(
                'Value "%s" for parameter "by" is not valid. Allowed values are %s.',
                $by,
                implode(', ', $allowedForBy)
            ));
        }

        return true;
    }

    /**
     * Fill params by site value
     *
     * @param  string $site
     * @param  array $params
     * @return array
     */
    protected function fillSite($site, array $params)
    {
        if ($site) {
            $params['site'] = $site;
        } elseif ($this->siteCode) {
            $params['site'] = $this->siteCode;
        }

        return $params;
    }
}

class InvalidJsonException extends DomainException
{
}

class CurlException extends RuntimeException
{
}
