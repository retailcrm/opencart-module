<?php

class ModelExtensionRetailcrmReferences extends Model {

    /**
     * Get opencart delivery methods
     *
     * @return array
     */
    public function getOpercartDeliveryTypes($opencart_api_client)
    {
        return $opencart_api_client->getDeliveryTypes();
    }

    /**
     * Get all delivery types
     *
     * @return array
     */
    public function getDeliveryTypes($opencart_api_client, $retailcrm_api_client)
    {
        return array(
            'opencart' => $this->getOpercartDeliveryTypes($opencart_api_client),
            'retailcrm' => $this->getApiDeliveryTypes($retailcrm_api_client)
        );
    }

    /**
     * Get all statuses
     *
     * @return array
     */
    public function getOrderStatuses($retailcrm_api_client)
    {
        return array(
            'opencart' => $this->getOpercartOrderStatuses(),
            'retailcrm' => $this->getApiOrderStatuses($retailcrm_api_client)
        );
    }

    /**
     * Get all payment types
     *
     * @return array
     */
    public function getPaymentTypes($retailcrm_api_client)
    {
        return array(
            'opencart' => $this->getOpercartPaymentTypes(),
            'retailcrm' => $this->getApiPaymentTypes($retailcrm_api_client)
        );
    }

    /**
     * Get all custom fields
     *
     * @return array
     */
    public function getCustomFields($retailcrm_api_client)
    {
        return array(
            'opencart' => $this->getOpencartCustomFields(),
            'retailcrm' => $this->getApiCustomFields($retailcrm_api_client)
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
    public function getApiDeliveryTypes($retailcrm_api_client)
    {
        $response = $retailcrm_api_client->deliveryTypesList();

        return (!$response->isSuccessful()) ? array() : $response->deliveryTypes;
    }

    /**
     * Get RetailCRM order statuses
     *
     * @return array
     */
    public function getApiOrderStatuses($retailcrm_api_client)
    {
        $response = $retailcrm_api_client->statusesList();

        return (!$response->isSuccessful()) ? array() : $response->statuses;
    }

    /**
     * Get RetailCRM payment types
     *
     * @return array
     */
    public function getApiPaymentTypes($retailcrm_api_client)
    {
        $response = $retailcrm_api_client->paymentTypesList();

        return (!$response->isSuccessful()) ? array() : $response->paymentTypes;
    }

    /**
     * Get RetailCRM custom fields
     *
     * @return array
     */
    public function getApiCustomFields($retailcrm_api_client)
    {
        $customers = $retailcrm_api_client->customFieldsList(array('entity' => 'customer'));
        $orders = $retailcrm_api_client->customFieldsList(array('entity' => 'order'));

        $custom_fields_customers = (!$customers->isSuccessful()) ? array() : $customers->customFields;
        $custom_fields_orders = (!$orders->isSuccessful()) ? array() : $orders->customFields;

        if (!$custom_fields_customers && !$custom_fields_orders) {
            return array();
        }

        return array('customers' => $custom_fields_customers, 'orders' => $custom_fields_orders);
    }

    /**
     * Get RetailCRM price types
     *
     * @return array
     */
    public function getPriceTypes($retailcrm_api_client)
    {
        $response = $retailcrm_api_client->priceTypesList();

        return (!$response->isSuccessful()) ? array() : $response->priceTypes;
    }
}
