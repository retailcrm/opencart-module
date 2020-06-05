<?php

namespace retailcrm\service;

class CorporateCustomerBuilder {
    private $data = array();
    private $company;

    public static function create($init = true) {
        $self = new self();

        if ($init) {
            $self->initData();
        }

        return $self;
    }

    public function initData() {
        $this->data = array(
            'customerContacts' => array(
                array(
                    'isMain' => true,
                    'customer' => array()
                )
            ),
            'addresses' => array(),
            'companies' => array()
        );

        return $this;
    }

    public function build() {
        $this->data['nickName'] = $this->company;

        return $this->data;
    }

    public function setCompany($company) {
        $this->company = $company;

        return $this;
    }

    public function setCustomerId($customer_id) {
        $this->data['customerContacts']['customer']['id'] = $customer_id;

        return $this;
    }

    public function setCustomerExternalId($customer_external_id) {
        $this->data['customerContacts']['customer']['externalId'] = $customer_external_id;

        return $this;
    }

    public function buildAddress($data) {
        if (!empty($data['address_id'])) {
            $address = array(
                'externalId' => $data['address_id'],
                'index' => $data['postcode'],
                'countryIso' => $data['iso_code_2'],
                'region' => $data['zone'],
                'city' => $data['city'],
                'name' => $data['company'],
                'text' => $data['address_1'] . ' ' . $data['address_2']
            );
        } else {
            $address = array(
                'index' => $data['payment_postcode'],
                'countryIso' => $data['payment_iso_code_2'],
                'region' => $data['payment_zone'],
                'city' => $data['payment_city'],
                'name' => $data['payment_company'],
                'text' => $data['payment_address_1'] . ' ' . $data['payment_address_2']
            );
        }

        return $address;
    }

    public function addAddress($data) {
        $this->data['addresses'][] = $this->buildAddress($data);

        return $this;
    }

    public function addCompany($data) {
        if (!empty($data['address_id'])) {
            $legalAddress = sprintf(
                "%s %s %s %s %s",
                $data['postcode'],
                $data['zone'],
                $data['city'],
                $data['address_1'],
                $data['address_2']
            );
        } else {
            $legalAddress = sprintf(
                "%s %s %s %s %s",
                $data['payment_postcode'],
                $data['payment_zone'],
                $data['payment_city'],
                $data['payment_address_1'],
                $data['payment_address_2']
            );
        }

        $this->data['companies'][] = array(
            'isMain' => true,
            'name' => $this->company,
            'contragent' => array(
                'legalAddress' => $legalAddress
            )
        );

        return $this;
    }
}
