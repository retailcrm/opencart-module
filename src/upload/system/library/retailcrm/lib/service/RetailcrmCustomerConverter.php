<?php

namespace retailcrm\service;

class RetailcrmCustomerConverter {
    protected $data;
    protected $customer_data = [];
    protected $address = [];
    protected $isSubscribed;
    protected $settingsManager;

    public function __construct(
        SettingsManager $settingsManager
    ) {
        $this->settingsManager = $settingsManager;
    }

    public function getCustomer() {
        return $this->data;
    }

    public function initCustomerData($customer_data, $address, $isSubscribed) {
        $this->data = [];
        $this->customer_data = $customer_data;
        $this->address = $address;
        $this->isSubscribed = $isSubscribed;

        return $this;
    }

    public function setCustomerData() {
        $this->data['externalId'] = $this->customer_data['customer_id'];
        $this->data['firstName'] = $this->customer_data['firstname'];
        $this->data['lastName'] = $this->customer_data['lastname'];
        $this->data['email'] = $this->customer_data['email'];
        $this->data['createdAt'] = $this->customer_data['date_added'];

        if ($this->isSubscribed !== null) {
            $this->data['subscribed'] = $this->isSubscribed;
        }

        if (!empty($this->customer_data['telephone'])) {
            $this->data['phones'] = [['number' => $this->customer_data['telephone']]];
        }

        return $this;
    }

    public function setAddress() {
        if (!empty($this->address)) {
            $this->data['address'] = [
                'index' => $this->address['postcode'],
                'countryIso' => $this->address['iso_code_2'],
                'region' => $this->address['zone'],
                'city' => $this->address['city'],
                'text' => $this->address['address_1'] . ' ' . $this->address['address_2']
            ];
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
