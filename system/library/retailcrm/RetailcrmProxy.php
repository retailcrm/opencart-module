<?php

/**
 * Class RequestProxy
 * @package RetailCrm\Component
 */
class RetailcrmProxy
{

    private $api;
    private $log;

    public function __construct($url, $key, $log, $version = null)
    {   
        switch ($version) {
            case 'v5':
                $this->api = new RetailcrmApiClient5($url, $key, $version);
                break;
            case 'v4':
                $this->api = new RetailcrmApiClient4($url, $key, $version);
                break;
            case 'v3':
                $this->api = new RetailcrmApiClient3($url, $key, $version);
                break;
            case null:
                $this->api = new RetailcrmApiClient3($url, $key, $version);
                break;
        }

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
