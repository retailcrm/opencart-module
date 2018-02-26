<?php

require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

class ModelRetailcrmReferences extends Model
{
    protected $retailcrm;
    private $opencartApiClient;

    public function getOpercartDeliveryTypes()
    {
        $this->load->model('user/api');
        $this->opencartApiClient = new OpencartApiClient($this->registry);

        return $this->opencartApiClient->getDeliveryTypes(); 
    }

    public function getDeliveryTypes()
    {
        $this->load->model('setting/store');

        return array(
            'opencart' => $this->getOpercartDeliveryTypes(),
            'retailcrm' => $this->getApiDeliveryTypes()
        );
    }

    public function getOrderStatuses()
    {
        return array(
            'opencart' => $this->getOpercartOrderStatuses(),
            'retailcrm' => $this->getApiOrderStatuses()
        );
    }

    public function getPaymentTypes()
    {
        return array(
            'opencart' => $this->getOpercartPaymentTypes(),
            'retailcrm' => $this->getApiPaymentTypes()
        );
    }

    public function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');

        return $this->model_localisation_order_status
            ->getOrderStatuses(array());
    }

    public function getOpercartPaymentTypes()
    {
        $paymentTypes = array();
        $files = glob(DIR_APPLICATION . 'controller/payment/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('payment/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $paymentTypes[$extension] = strip_tags(
                        $this->language->get('heading_title')
                    );
                }
            }
        }

        return $paymentTypes;
    }

    public function getApiDeliveryTypes()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                $this->setLogs()
            );

            $response = $this->retailcrm->deliveryTypesList();

            return ($response === false) ? array() : $response->deliveryTypes;
        }
    }

    public function getApiOrderStatuses()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                $this->setLogs()
            );

            $response = $this->retailcrm->statusesList();

            return ($response === false) ? array() : $response->statuses;
        }
    }

    public function getApiPaymentTypes()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                $this->setLogs()
            );

            $response = $this->retailcrm->paymentTypesList();

            return ($response === false) ? array() : $response->paymentTypes;
        }
    }

    private function setLogs()
    {
        if (version_compare(VERSION, '2.1', '>')) {
            $logs = DIR_SYSTEM . 'storage/logs/retailcrm.log';
        } else {
            $logs = DIR_SYSTEM . 'logs/retailcrm.log';
        }

        return $logs;
    }
}
