<?php

class ModelExtensionRetailcrmReferences extends Model
{
    protected $settings;
    protected $moduleTitle;
    protected $retailcrmApiClient;

    private $opencartApiClient;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
        $this->retailcrmApiClient = $this->retailcrm->getApiClient();
    }

    /**
     * Get opencart delivery methods
     * 
     * @return array
     */
    public function getOpercartDeliveryTypes()
    {
        $this->opencartApiClient = $this->retailcrm->getOcApiClient($this->registry);

        return $this->opencartApiClient->getDeliveryTypes();
    }

    /**
     * Get all delivery types
     * 
     * @return array
     */
    public function getDeliveryTypes()
    {
        $this->load->model('setting/store');

        return array(
            'opencart' => $this->getOpercartDeliveryTypes(),
            'retailcrm' => $this->getApiDeliveryTypes()
        );
    }

    /**
     * Get all statuses
     * 
     * @return array
     */
    public function getOrderStatuses()
    {
        return array(
            'opencart' => $this->getOpercartOrderStatuses(),
            'retailcrm' => $this->getApiOrderStatuses()
        );
    }

    /**
     * Get all payment types
     * 
     * @return array
     */
    public function getPaymentTypes()
    {
        return array(
            'opencart' => $this->getOpercartPaymentTypes(),
            'retailcrm' => $this->getApiPaymentTypes()
        );
    }

    /**
     * Get all custom fields
     * 
     * @return array
     */
    public function getCustomFields()
    {
        return array(
            'opencart' => $this->getOpencartCustomFields(),
            'retailcrm' => $this->getApiCustomFields()
        );
    }

    /**
     * Get opencart order statuses
     * 
     * @return array
     */
    public function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');

        return $this->model_localisation_order_status
            ->getOrderStatuses(array());
    }

    /**
     * Get opencart payment types
     * 
     * @return array
     */
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

    /**
     * Get opencart custom fields
     * 
     * @return array
     */
    public function getOpencartCustomFields()
    {
        $this->load->model('customer/custom_field');
        
        return $this->model_customer_custom_field->getCustomFields();
    }

    /**
     * Get RetailCRM delivery types
     * 
     * @return array
     */
    public function getApiDeliveryTypes()
    {
        $response = $this->retailcrmApiClient->deliveryTypesList();

        return (!$response->isSuccessful()) ? array() : $response->deliveryTypes;
    }

    /**
     * Get RetailCRM order statuses
     * 
     * @return array
     */
    public function getApiOrderStatuses()
    {
        $response = $this->retailcrmApiClient->statusesList();

        return (!$response->isSuccessful()) ? array() : $response->statuses;
    }

    /**
     * Get RetailCRM payment types
     * 
     * @return array
     */
    public function getApiPaymentTypes()
    {
        $response = $this->retailcrmApiClient->paymentTypesList();

        return (!$response->isSuccessful()) ? array() : $response->paymentTypes;
    }

    /**
     * Get RetailCRM custom fields
     * 
     * @return array
     */
    public function getApiCustomFields()
    {
        $customers = $this->retailcrmApiClient->customFieldsList(array('entity' => 'customer'));
        $orders = $this->retailcrmApiClient->customFieldsList(array('entity' => 'order'));

        $customFieldsCustomers = (!$customers->isSuccessful()) ? array() : $customers->customFields;
        $customFieldsOrders = (!$orders->isSuccessful()) ? array() : $orders->customFields;

        if (!$customFieldsCustomers && !$customFieldsOrders) {
            return array();
        }
        
        return array('customers' => $customFieldsCustomers, 'orders' => $customFieldsOrders);
    }

    /**
     * Get RetailCRM price types
     * 
     * @return array
     */
    public function getPriceTypes()
    {
        $response = $this->retailcrmApiClient->priceTypesList();

        return (!$response->isSuccessful()) ? array() : $response->priceTypes;
    }
}
