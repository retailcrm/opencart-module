<?php

require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

class ModelRetailcrmReferences extends Model
{
    protected $retailcrm;

    public function getDeliveryTypes()
    {
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

    protected function getOpercartDeliveryTypes()
    {
        $deliveryMethods = array();
        $files = glob(DIR_APPLICATION . 'controller/shipping/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('shipping/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $deliveryMethods[$extension.'.'.$extension] = strip_tags(
                        $this->language->get('heading_title')
                    );
                }
            }
        }

        return $deliveryMethods;
    }

    protected function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');

        return $this->model_localisation_order_status
            ->getOrderStatuses(array());
    }

    protected function getOpercartPaymentTypes()
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

    protected function getApiDeliveryTypes()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                DIR_SYSTEM . 'logs/retailcrm.log'
            );

            $response = $this->retailcrm->deliveryTypesList();

            return ($response === false) ? array() : $response->deliveryTypes;
        }
    }

    protected function getApiOrderStatuses()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                DIR_SYSTEM . 'logs/retailcrm.log'
            );

            $response = $this->retailcrm->statusesList();

            return ($response === false) ? array() : $response->statuses;
        }
    }

    protected function getApiPaymentTypes()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings['retailcrm_url'],
                $settings['retailcrm_apikey'],
                DIR_SYSTEM . 'logs/retailcrm.log'
            );

            $response = $this->retailcrm->paymentTypesList();

            return ($response === false) ? array() : $response->paymentTypes;
        }
    }
}
