<?php

class ModelRetailcrmCustomer extends Model {

    public function uploadToCrm($customers) {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(empty($customers))
            return false;
        if(empty($settings['retailcrm_url']) || empty($settings['retailcrm_apikey']))
            return false;

        require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

        $this->retailcrmApi = new RetailcrmProxy(
            $settings['retailcrm_url'],
            $settings['retailcrm_apikey'],
            $this->setLogs()
        );

        $customersToCrm = array();

        foreach($customers as $customer) {
            $customersToCrm[] = $this->process($customer);
        }

        $chunkedCustomers = array_chunk($customersToCrm, 50);

        foreach($chunkedCustomers as $customersPart) {
            $this->retailcrmApi->customersUpload($customersPart);
        }
    }

    private function process($customer) {
        $customerToCrm = array(
            'externalId' => $customer['customer_id'],
            'firstName' => $customer['firstname'],
            'lastName' => $customer['lastname'],
            'email' => $customer['email'],
            'phones' => array(
                array(
                    'number' => $customer['telephone']
                )
            ),
            'createdAt' => $customer['date_added']
        );

        return $customerToCrm;
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
