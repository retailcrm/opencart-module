<?php

namespace retailcrm\history;

use retailcrm\repository\CustomerRepository;
use retailcrm\repository\DataRepository;
use retailcrm\service\SettingsManager;

class Customer {
    private $data_repository;
    private $customer_repository;
    private $settings_manager;

    public function __construct(
        DataRepository $data_repository,
        CustomerRepository $customer_repository,
        SettingsManager $settings_manager
    ) {
        $this->data_repository = $data_repository;
        $this->customer_repository = $customer_repository;
        $this->settings_manager = $settings_manager;
    }

    public function handleCustomer(&$customer_data, $customer) {
        // is new customer
        if (!$customer_data) {
            $customer_data['store_id'] = 0;
            $customer_data['customer_group_id'] = '1';
            $customer_data['fax'] = '';
            $customer_data['newsletter'] = 0;
            $customer_data['password'] = 'tmppass';
            $customer_data['status'] = 1;
            $customer_data['approved'] = 1;
            $customer_data['safe'] = 0;
            $customer_data['affiliate'] = '';
        } else {
            $customer_data['password'] = false;
        }

        $customer_data['firstname'] = $customer['firstName'];
        $customer_data['lastname'] = $customer['lastName'] ?? '';
        $customer_data['email'] = $customer['email'];
        $customer_data['telephone'] = $customer['phones'] ? $customer['phones'][0]['number'] : '';

        if (!empty($customer['emailMarketingUnsubscribedAt'])) {
            $customer_data['newsletter'] = 0;
        }

        $customer_data['affiliate'] = false;
    }

    public function handleAddress($customer, $order, $address_id = 0) {
        if (empty($customer['address']) && !empty($order)) {
            $customer['address'] = $order['delivery']['address'];
        }

        if ($address_id) {
            $customer_address = $this->customer_repository->getAddress($address_id);
        } else {
            $customer_address = array(
                'address_id' => ''
            );
        }

        if (isset($customer['address']['countryIso'])) {
            $customer_country = $this->data_repository->getCountryByIsoCode($customer['address']['countryIso']);
        }

        if (isset($customer['address']['region'])) {
            $customer_zone = $this->data_repository->getZoneByName($customer['address']['region']);
        }

        $customer_address['firstname'] = isset($customer['patronymic'])
            ? $customer['firstName'] . ' ' . $customer['patronymic']
            : $customer['firstName'];
        $customer_address['lastname'] = isset($customer['lastName']) ? $customer['lastName'] : '';
        $customer_address['address_2'] = !empty($customer_address['address_2']) ? $customer_address['address_2'] : '';
        $customer_address['company'] = !empty($customer_address['company']) ? $customer_address['company'] : '';

        if (!empty($customer['address'])) {
            $customer_address['address_1'] = !empty($customer['address']['text']) ? $customer['address']['text'] : '';
            $customer_address['city'] = !empty($customer['address']['city']) ? $customer['address']['city'] : '';
            $customer_address['postcode'] = isset($customer['address']['index']) ? $customer['address']['index'] : '';
        }

        $customer_address['zone_id'] = 0;
        $customer_address['country_id'] = 0;

        if (isset($customer_country)) {
            $customer_address['country_id'] = $customer_country['country_id'];
        }

        if (isset($customer_zone) && isset($customer_zone['zone_id'])) {
            $customer_address['zone_id'] = $customer_zone['zone_id'];
        }

        $customer_address['default'] = true;

        return $customer_address;
    }

    public function handleCustomFields(&$customer_data, $customer) {
        $settings = $this->settings_manager->getSetting('custom_field');
        if (!empty($settings)) {
            $custom_field_setting = array_flip($settings);
        }

        if (isset($custom_field_setting) && $customer['customFields']) {
            foreach ($customer['customFields'] as $code => $value) {
                if (array_key_exists($code, $custom_field_setting)) {
                    $field_code = str_replace('c_', '', $custom_field_setting[$code]);
                    $custom_fields[$field_code] = $value;
                }
            }

            $customer_data['custom_field'] = $custom_fields ?? [];
        }
    }
}
