<?php

/**
 * Class RequestProxy
 * @package RetailCrm\Component
 */
class RetailcrmProxy
{

    private $api;
    private $log;

    public function __construct($url, $key, $log)
    {
        $this->api = new RetailcrmApiClient($url, $key);
        $this->log = $log;
    }

    public function __call($method, $arguments)
    {
        try {
            $response = call_user_func_array(array($this->api, $method), $arguments);
            $date = date('[Y-m-d H:i:s]');

            if (!$response->isSuccessful()) {
                error_log($date . " [$method] " . $response->getErrorMsg() . "\n", 3, $this->log);
                if (isset($response['errors'])) {
                    $error = implode("\n", $response['errors']);
                    error_log($date .' '. $error . "\n", 3, $this->log);
                }
                $response = false;
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
