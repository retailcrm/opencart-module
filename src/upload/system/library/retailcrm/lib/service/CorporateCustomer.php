<?php

namespace retailcrm\service;

use retailcrm\repository\CustomerRepository;

class CorporateCustomer {
    private $api;
    private $customer_repository;

    public function __construct(
        \RetailcrmProxy $api,
        CustomerRepository $customer_repository
    ) {
        $this->api = $api;
        $this->customer_repository = $customer_repository;
    }

    public function createCorporateCustomer($order_data, $customer) {
        if (!empty($customer['externalId'])) {
            $response = $this->api->customersGet($customer['externalId']);
            if ($response && $response->isSuccessful() && !empty($response['customer'])) {
                $customer_data = $this->customer_repository->getCustomer($customer['externalId']);

                $corp_client = $this->searchCorporateCustomer(array(
                    'contactIds' => array($response['customer']['id']),
                    'companyName' => $order_data['payment_company']
                ));

                if ($corp_client) {
                    $addresses_response = $this->api->customersCorporateAddresses($corp_client['id']);
                    if ($addresses_response && $addresses_response->isSuccessful() && !empty($addresses_response['addresses'])) {
                        foreach ($addresses_response['addresses'] as $address) {
                            if ($customer_data['address_id'] == $address['externalId']) {
                                $exist_address = $address;
                                break;
                            }
                        }

                        $address = $this->customer_repository->getAddress($customer_data['address_id']);
                        $corp_address = CorporateCustomerBuilder::create(false)->buildAddress($address);

                        if (isset($exist_address)) {
                            $this->api->customersCorporateAddressesEdit(
                                $corp_client['id'],
                                $customer_data['address_id'],
                                $corp_address,
                                'id'
                            );
                        } else {
                            $this->api->customersCorporateAddressesCreate(
                                $corp_client['id'],
                                $corp_address,
                                'id'
                            );
                        }
                    }

                    return $corp_client['id'];
                }

                $response = $this->api->customersCorporateCreate($this->buildFromExistingCustomer($customer_data));

                return $response['id'];
            }
        }

        if (!empty($customer['id'])) {
            $corp_client = $this->searchCorporateCustomer(array(
                'contactIds' => array($customer['id']),
                'companyName' => $order_data['payment_company']
            ));

            if (empty($corp_client)) {
                $corp_client = $this->searchCorporateCustomer(array(
                    'companyName' => $order_data['payment_company']
                ));
            }

            if ($corp_client) {
                $addresses_response = $this->api->customersCorporateAddresses($corp_client['id']);
                $corp_address = CorporateCustomerBuilder::create(false)->buildAddress($order_data);
                if ($addresses_response && $addresses_response->isSuccessful() && !empty($addresses_response['addresses'])) {
                    foreach ($addresses_response['addresses'] as $address) {
                        foreach ($corp_address as $field => $value) {
                            if (isset($address[$field]) && $address[$field] != $value) {
                                continue 2;
                            }
                        }

                        $exist_address = $address;

                        break;
                    }
                }

                if (!isset($exist_address)) {
                    $this->api->customersCorporateAddressesCreate(
                        $corp_client['id'],
                        $corp_address,
                        'id'
                    );
                }

                return $corp_client['id'];
            }

            $response = $this->api->customersCorporateCreate(
                $this->buildCorporateCustomerFromOrder($order_data, $customer['id'])
            );

            return $response['id'];
        }

        return null;
    }

    public function buildFromExistingCustomer($customer_data) {
        $address = $this->customer_repository->getAddress($customer_data['customer_id']);
        $builder = CorporateCustomerBuilder::create()
            ->setCompany($address['company'])
            ->setCustomerExternalId($customer_data['customer_id'])
            ->addAddress($address)
            ->addCompany($address);

        return $builder->build();
    }

    public function buildCorporateCustomerFromOrder($order_data, $crm_customer_id) {
        $builder = CorporateCustomerBuilder::create()
            ->setCompany($order_data['payment_company'])
            ->setCustomerId($crm_customer_id)
            ->addAddress($order_data)
            ->addCompany($order_data);

        return $builder->build();
    }

    /**
     * Search by provided filter, returns first found customer
     *
     * @param array $filter
     * @param bool  $returnGroup Return all customers for group filter instead of first
     *
     * @return bool|array
     */
    public function searchCorporateCustomer($filter, $returnGroup = false)
    {
        $search = $this->api->customersCorporateList($filter);

        if (isset($search) && $search->isSuccessful()) {
            if (isset($search['customersCorporate'])) {
                if (empty($search['customersCorporate'])) {
                    return false;
                }

                if ($returnGroup) {
                    return $search['customersCorporate'];
                } else {
                    $dataCorporateCustomers = $search['customersCorporate'];
                    $customer = reset($dataCorporateCustomers);
                }
            } else {
                $customer = false;
            }

            return $customer;
        }

        return false;
    }
}
