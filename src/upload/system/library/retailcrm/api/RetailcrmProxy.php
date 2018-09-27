<?php

/**
 * Class RequestProxy
 * @package RetailCrm\Component
 */
class RetailcrmProxy
{
    private $api;
    private $log;
    private $debug;

    public function __construct($url, $key, $log, $version = null, $debug = false)
    {
        switch ($version) {
            case 'v5':
                $this->api = new RetailcrmApiClient5($url, $key, $version);
                break;

            default:
                $this->api = new RetailcrmApiClient5($url, $key, $version);
                break;
        }

        $this->log = $log;
        $this->debug = $debug;
    }

    public function __call($method, $arguments)
    {
        $date = date('[Y-m-d H:i:s]');

        try {
            $response = call_user_func_array(array($this->api, $method), $arguments);

            if ($this->debug) {
                $logger = new Log($method);
                $logger->write(
                    array(
                        'data' => $arguments,
                        'response' => $response
                    )
                );
            }

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
