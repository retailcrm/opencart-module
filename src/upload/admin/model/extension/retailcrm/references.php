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

        return [
            'opencart' => $this->getOpercartDeliveryTypes(),
            'retailcrm' => $this->getApiDeliveryTypes()
        ];
    }

    /**
     * Get all statuses
     *
     * @return array
     */
    public function getOrderStatuses()
    {
        return [
            'opencart' => $this->getOpercartOrderStatuses(),
            'retailcrm' => $this->getApiOrderStatuses()
        ];
    }

    /**
     * Get all payment types
     *
     * @return array
     */
    public function getPaymentTypes()
    {
        return [
            'opencart' => $this->getOpercartPaymentTypes(),
            'retailcrm' => $this->getApiPaymentTypes()
        ];
    }

    /**
     * Get all custom fields
     *
     * @return array
     */
    public function getCustomFields()
    {
        return [
            'opencart' => $this->getOpencartCustomFields(),
            'retailcrm' => $this->getApiCustomFields()
        ];
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
            ->getOrderStatuses([]);
    }

    /**
     * Get opencart payment types
     *
     * @return array
     */
    public function getOpercartPaymentTypes()
    {
        $paymentTypes = [];
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

        if (!$response) {
            return [];
        }

        return (!$response->isSuccessful()) ? [] : $response->deliveryTypes;
    }

    /**
     * Get RetailCRM available sites list
     */
    public function getApiSite()
    {
        $response = $this->retailcrmApiClient->sitesList();

        if (!$response || !$response->isSuccessful()) {
            return [];
        }

        $sites = $response->sites;

        return end($sites);
    }

    /**
     * Get RetailCRM order statuses
     *
     * @return array
     */
    public function getApiOrderStatuses()
    {
        $response = $this->retailcrmApiClient->statusesList();

        if (!$response) {
            return [];
        }

        return (!$response->isSuccessful()) ? [] : $response->statuses;
    }

    /**
     * Get RetailCRM payment types
     *
     * @return array
     */
    public function getApiPaymentTypes()
    {
        $response = $this->retailcrmApiClient->paymentTypesList();

        if (!$response) {
            return [];
        }

        return (!$response->isSuccessful()) ? [] : $response->paymentTypes;
    }

    /**
     * Get RetailCRM stores
     *
     * @return array
     */
    public function getApiStores()
    {
        $response = $this->retailcrmApiClient->StoresList();

        if (!$response) {
            return [];
        }

        return (!$response->isSuccessful()) ? [] : $response->stores;
    }

    /**
     * Get RetailCRM custom fields
     *
     * @return array
     */
    public function getApiCustomFields()
    {
        $customers = $this->retailcrmApiClient->customFieldsList(['entity' => 'customer']);
        $orders = $this->retailcrmApiClient->customFieldsList(['entity' => 'order']);

        if (!$customers || !$orders) {
            return [];
        }

        $customFieldsCustomers = (!$customers->isSuccessful()) ? [] : $customers->customFields;
        $customFieldsOrders = (!$orders->isSuccessful()) ? [] : $orders->customFields;

        if (!$customFieldsCustomers && !$customFieldsOrders) {
            return [];
        }

        return ['customers' => $customFieldsCustomers, 'orders' => $customFieldsOrders];
    }

    /**
     * Get RetailCRM price types
     *
     * @return array
     */
    public function getPriceTypes()
    {
        $response = $this->retailcrmApiClient->priceTypesList();
        
        if (!$response) {
            return [];
        }

        return (!$response->isSuccessful()) ? [] : $response->priceTypes;
    }
}
