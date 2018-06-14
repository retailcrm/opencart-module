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
        $moduleTitle = $this->getModuleTitle();
        $settings = $this->model_setting_setting->getSetting($moduleTitle);
        $this->cookieFileName = $settings[$moduleTitle . '_apikey'];

        $this->auth();
    }

    private function getCookieValue($cookieName) {
        if (!file_exists(DIR_APPLICATION . $this->cookieFileName . '.txt')) {
            return false;
        }

        $cookieFile = file_get_contents(DIR_APPLICATION . $this->cookieFileName . '.txt');
        $cookieFile = explode("\n", $cookieFile);

        $cookies = array();
        foreach ($cookieFile as $line) {
            if (empty($line) OR $line{0} == '#') {
                continue;
            }

            $params = explode("\t", $line);
            $cookies[$params[5]] = $params[6];
        }

        if (isset($cookies[$cookieName])) {
            return $cookies[$cookieName];
        }

        return false;
    }

    private function request($method, $getParams, $postParams) {
        $opencartStoreInfo = $this->model_setting_store->getStore($this->opencartStoreId);

        if ($this->apiToken !== false) {
            if (version_compare(VERSION, '3.0', '<')) {
                $getParams['key'] = $this->apiToken['key'];
            } else {
                $getParams['key'] = $this->apiToken['key'];
                $getParams['username'] = $this->apiToken['username'];

                if (isset($this->session->data['user_token'])) {
                    $getParams['api_token'] = $this->session->data['user_token'];
                } else {
                    $session = $this->registry->get('session');
                    $session->start();
                    $getParams['api_token'] = $session->getId();
                }
            }
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

        curl_setopt($curl, CURLOPT_COOKIEFILE, DIR_APPLICATION . $this->cookieFileName . '.txt');
        curl_setopt($curl, CURLOPT_COOKIEJAR, DIR_APPLICATION . $this->cookieFileName . '.txt');

        $json = json_decode(curl_exec($curl), true);

        curl_close($curl);

        if (!$json && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (isset($json['error'])) {
            if (is_array($json['error'])) {
                foreach ($json['error'] as $error) {
                    error_log(date('Y-m-d H:i:s') . " @ " . "[$method]" . ' - ' . $error . "\n", 3, DIR_LOGS . "opencartapi.log");
                }
            } else {
                error_log(date('Y-m-d H:i:s') . " @ " . "[$method]" . ' - ' . $json['error'] . "\n", 3, DIR_LOGS . "opencartapi.log");
            }
        } else {
            return $json;
        }
    }

    private function auth() {
        $this->load->model('user/api');

        $apiUsers = $this->model_user_api->getApis();

        $api = array();

        foreach ($apiUsers as $apiUser) {
            if($apiUser['status'] == 1) {
                $api = $apiUser;
                break;
            }
        }

        if (!isset($api['api_id'])) {
            return false;
        }

        if (isset($api) && !empty($api)) {
            $this->apiToken = $api;
        } else {
            $this->apiToken = false;
        }
    }

    /**
     * Get delivery types
     * 
     * @return array
     */
    public function getDeliveryTypes()
    {
        return $this->request('retailcrm/getDeliveryTypes', array(), array());
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

    /**
     * Get module name
     * 
     * @return string
     */
    private function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')){
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }
}
