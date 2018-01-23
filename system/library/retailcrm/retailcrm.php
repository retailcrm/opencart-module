<?php

namespace retailcrm;

require_once 'bootstrap.php';

class Retailcrm {
    protected $apiClient;
    protected $registry;

    public function __construct($registry)
    {
        $this->registry = $registry;
    }
    public function __get($name) {
        return $this->registry->get($name);
    }

    /**
     * Get api client object
     * 
     * @param string $apiUrl (default = null)
     * @param string $apiKey (default = null)
     * @param string $apiVersion (default = null)
     * 
     * @return mixed object | boolean 
     */
    public function getApiClient($apiUrl = null, $apiKey = null, $apiVersion = null)
    {
        $this->load->model('setting/setting');
        
        $setting = $this->model_setting_setting->getSetting($this->getModuleTitle());

        if ($apiUrl === null && $apiKey === null) {
            $apiUrl = $setting[$this->getModuleTitle() . '_url'];
            $apiKey = $setting[$this->getModuleTitle() . '_apikey'];
            $apiVersion = $setting[$this->getModuleTitle() . '_apiversion'];
        }

        if ($apiUrl && $apiKey) {
            $this->apiClient = new \RetailcrmProxy($apiUrl, $apiKey, DIR_LOGS . 'retailcrm.log', $apiVersion);

            return $this->apiClient;
        }

        return false;
    }

    /**
     * Get opencart api client
     * 
     * @param object $registry
     * 
     * @return \OpencartApiClient
     */
    public function getOcApiClient($registry)
    {
        return new \OpencartApiClient($registry);
    }

    /**
     * Get module title for this version
     * 
     * @return string $title
     */
    public function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }

    /**
     * Get token param name
     * 
     * @return string $token
     */
    public function getTokenTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $token = 'token';
        } else {
            $token = 'user_token';
        }

        return $token;
    }
}
