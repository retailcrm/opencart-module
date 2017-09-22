<?php

require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

class ModelExtensionRetailcrmReferences extends Model
{
    protected $retailcrm;
    private $opencartApiClient;

    public function getOpercartDeliveryTypes()
    {
        $this->load->model('user/api');
        $this->opencartApiClient = new OpencartApiClient($this->registry);

        return $this->opencartApiClient->request('retailcrm/getDeliveryTypes', array(), array()); 
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

    public function getCustomFields()
    {
        return array(
            'opencart' => $this->getOpencartCustomFields(),
            'retailcrm' => $this->getApiCustomFields()
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
        $files = glob(DIR_APPLICATION . 'controller/extension/payment/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('extension/payment/' . $extension);
                
                if (version_compare(VERSION, '3.0', '<')) {
                    $configStatus = $extension . '_status';
                } else {
                    $configStatus = 'payment_' . $extension . '_status';
                }

                if ($this->config->get($configStatus)) {
                    $paymentTypes[$extension] = strip_tags(
                        $this->language->get('heading_title')
                    );
                }
            }
        }

        return $paymentTypes;
    }
    
    public function getOpencartCustomFields()
    {
        $this->load->model('customer/custom_field');
        
        return $this->model_customer_custom_field->getCustomFields();
    }
    
    public function getApiDeliveryTypes()
    {   
        $this->initApi();
        
        $response = $this->retailcrm->deliveryTypesList();

        return (!$response->isSuccessful()) ? array() : $response->deliveryTypes;
    }

    public function getApiOrderStatuses()
    {
        $this->initApi();

        $response = $this->retailcrm->statusesList();

        return (!$response->isSuccessful()) ? array() : $response->statuses;
    }

    public function getApiPaymentTypes()
    {   
        $this->initApi();

        $response = $this->retailcrm->paymentTypesList();

        return (!$response->isSuccessful()) ? array() : $response->paymentTypes;
    }
    
    public function getApiCustomFields()
    {
        $this->initApi();

        $customers = $this->retailcrm->customFieldsList(array('entity' => 'customer'));
        $orders = $this->retailcrm->customFieldsList(array('entity' => 'order'));

        $customFieldsCustomers = (!$customers->isSuccessful()) ? array() : $customers->customFields;
        $customFieldsOrders = (!$orders->isSuccessful()) ? array() : $orders->customFields;

        if (!$customFieldsCustomers && !$customFieldsOrders) {
            return array();
        }
        
        return array('customers' => $customFieldsCustomers, 'orders' => $customFieldsOrders);
    }

    protected function initApi()
    {
        $moduleTitle = $this->getModuleTitle();
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting($moduleTitle);

        if(!empty($settings[$moduleTitle . '_url']) && !empty($settings[$moduleTitle . '_apikey'])) {
            $this->retailcrm = new RetailcrmProxy(
                $settings[$moduleTitle . '_url'],
                $settings[$moduleTitle . '_apikey'],
                DIR_SYSTEM . 'storage/logs/retailcrm.log',
                $settings[$moduleTitle . '_apiversion']
            );
        }
    }

    private function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }
}
