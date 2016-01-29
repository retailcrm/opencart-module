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

            if (!$response->isSuccessful()) {
                error_log("[$method] " . $response->getErrorMsg() . "\n", 3, $this->log);
                if (isset($response['errors'])) {
                    $error = implode("\n", $response['errors']);
                    error_log($error . "\n", 3, $this->log);
                }
                $response = false;
            }

            return $response;
        } catch (CurlException $e) {
            error_log("[$method] " . $e->getMessage() . "\n", 3, $this->log);
            return false;
        } catch (InvalidJsonException $e) {
            error_log("[$method] " . $e->getMessage() . "\n", 3, $this->log);
            return false;
        }
    }

}
