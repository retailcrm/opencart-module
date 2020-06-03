<?php

/**
 * Class RequestProxy
 * @package RetailCrm\Component
 *
 * @method ordersCreate($order, $site = null)
 * @method ordersEdit($order, $by = 'externalId', $site = null)
 * @method ordersGet($order, $by = 'externalId', $site = null)
 * @method ordersList($filter, $page, $limit)
 * @method customersCreate($customer, $site = null)
 * @method customersEdit($customer, $by = 'externalId', $site = null)
 * @method customersList($filter, $page, $limit)
 */
class RetailcrmProxy
{
    private $api;
    private $log;

    public function __construct($url, $key, $log, $version = null)
    {
        $this->api = new RetailcrmApiClient5($url, $key, $version);

        $this->log = $log;
    }

    public function __call($method, $arguments)
    {
        $date = date('[Y-m-d H:i:s]');

        try {
            $response = call_user_func_array(array($this->api, $method), $arguments);

            if (!$response->isSuccessful()) {
                error_log($date . " [$method] " . $response->getErrorMsg() . "\n", 3, $this->log);
                if (isset($response['errors'])) {
                    $error = implode("\n", $response['errors']);
                    error_log($date .' '. $error . "\n", 3, $this->log);
                }
            }

            return $response;
        } catch (CurlException $e) {
            error_log($date . " [$method] " . $e->getMessage() . "\n", 3, $this->log);
            return false;
        } catch (InvalidJsonException $e) {
            error_log($date . " [$method] " . $e->getMessage() . "\n", 3, $this->log);
            return false;
        }
    }

}
