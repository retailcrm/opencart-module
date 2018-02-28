<?php

class OpencartApiClient {

    private $opencartStoreId = 0;
    private $cookieFileName;
    private $registry;
    private $apiToken;

    /* Совместимость с объектами ОС, например $this->model_module_name */
    public function __get($name) {
        return $this->registry->get($name);
    }

    public function __construct(Registry &$registry) {
        $this->registry = $registry;

        $settings = $this->model_setting_setting->getSetting('retailcrm');
        $this->cookieFileName = $settings['retailcrm_apikey'];

        $this->auth();
    }

    private function getCookieValue($cookieName) {
        $cookieFile = file_get_contents(DIR_APPLICATION . '/' . $this->cookieFileName . '.txt');
        $cookieFile = explode("\n", $cookieFile);

        $cookies = array();
        foreach($cookieFile as $line) {
            if(empty($line) OR $line{0} == '#')
                continue;

            $params = explode("\t", $line);
            $cookies[$params[5]] = $params[6];
        }

        if(isset($cookies[$cookieName]))
            return $cookies[$cookieName];

        return false;
    }

    private function request($method, $getParams, $postParams) {
        $opencartStoreInfo = $this->model_setting_store->getStore($this->opencartStoreId);

        if (version_compare(VERSION, '2.1.0', '>=') && !empty($this->apiToken)) {
            $getParams['key'] = $this->apiToken;
        } elseif (is_array($this->apiToken) && isset($this->apiToken['username'])) {
            $getParams = $this->apiToken;
        }

        $postParams['fromApi'] = true;

        if ($opencartStoreInfo) {
            $url = $opencartStoreInfo['ssl'];
        } else {
            $url = HTTPS_CATALOG;
        }

        $curl = curl_init();

        // Set SSL if required
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($curl, CURLOPT_PORT, 443);
        }

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url . 'index.php?route=api/' . $method . (!empty($getParams) ? '&' . http_build_query($getParams) : ''));

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postParams));

        curl_setopt($curl, CURLOPT_COOKIEFILE, DIR_APPLICATION . '/' . $this->cookieFileName . '.txt');
        curl_setopt($curl, CURLOPT_COOKIEJAR, DIR_APPLICATION . '/' . $this->cookieFileName . '.txt');

        $json = json_decode(curl_exec($curl), true);

        curl_close($curl);

        return $json;
    }

    private function auth() {
        $apiUsers = $this->model_user_api->getApis();

        $api = array();
        foreach ($apiUsers as $apiUser) {
            if($apiUser['status'] == 1) {
                if (version_compare(VERSION, '2.1.0', '>=')) {
                    $api = array(
                        'api_id' => $apiUser['api_id'],
                        'key' => $apiUser['key']
                    );
                } else {
                    $api = array(
                        'api_id' => $apiUser['api_id'],
                        'username' => $apiUser['username'],
                        'password' => $apiUser['password']
                    );
                }

                break;
            }
        }

        if(!isset($api['api_id'])) {
            return false;
        }

        if (isset($api['key'])) {
            $this->apiToken = $api['key'];
        } elseif (isset($api['username'])) {
            $this->apiToken = $api;
        } else {
            $this->apiToken = false;
        }
    }

    /**
     * Add history order
     * 
     * @param int $order_id
     * @param int $order_status_id
     * 
     * @return void
     */
    public function addHistory($order_id, $order_status_id)
    {
        $this->request('retailcrm/addOrderHistory', array(), array('order_id' => $order_id, 'order_status_id' => $order_status_id));
    }

    public function getDeliveryTypes() {
        return $this->request('retailcrm/getDeliveryTypes', array(), array());
    }

    private function getInnerIpAddr() {
        $opencartStoreInfo = $this->model_setting_store->getStore($this->opencartStoreId);

        if ($opencartStoreInfo) {
            $url = $opencartStoreInfo['ssl'];
        } else {
            $url = HTTPS_CATALOG;
        }

        $curl = curl_init();

        // Set SSL if required
        if (substr($url, 0, 5) == 'https') {
            curl_setopt($curl, CURLOPT_PORT, 443);
        }

        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $url . 'system/cron/getmyip.php');

        return curl_exec($curl);
    }
}
