<?php

namespace retailcrm\service;

class RetailcrmCustomerConverter {
    protected $data;
    protected $customer_data = array();
    protected $address = array();

    protected $settingsManager;

    public function __construct(
        SettingsManager $settingsManager
    ) {
        $this->settingsManager = $settingsManager;
    }

    public function getCustomer() {
        return $this->data;
    }

    public function initCustomerData($customer_data, $address) {
        $this->data = array();
        $this->customer_data = $customer_data;
        $this->address = $address;

        return $this;
    }

    public function setCustomerData() {
        $this->data['externalId'] = $this->customer_data['customer_id'];
        $this->data['firstName'] = $this->customer_data['firstname'];
        $this->data['lastName'] = $this->customer_data['lastname'];
        $this->data['email'] = $this->customer_data['email'];
        $this->data['createdAt'] = $this->customer_data['date_added'];

        if (!empty($this->customer_data['telephone'])) {
            $this->data['phones'] = array(
                array(
                    'number' => $this->customer_data['telephone']
                )
            );
        }

        return $this;
    }

    public function setAddress() {
        if (!empty($this->address)) {
            $this->data['address'] = array(
                'index' => $this->address['postcode'],
                'countryIso' => $this->address['iso_code_2'],
                'region' => $this->address['zone'],
                'city' => $this->address['city'],
                'text' => $this->address['address_1'] . ' ' . $this->address['address_2']
            );
        }

        return $this;
    }

    public function setCustomFields() {
        $settings = $this->settingsManager->getSetting('custom_field');
        if (!empty($settings) && $this->customer_data['custom_field']) {
            $customFields = json_decode($this->customer_data['custom_field']);

            foreach ($customFields as $key => $value) {
                if (isset($settings['c_' . $key])) {
                    $customFieldsToCrm[$settings['c_' . $key]] = $value;
                }
            }

            if (isset($customFieldsToCrm)) {
                $this->data['customFields'] = $customFieldsToCrm;
            }
        }

        return $this;
    }
}
