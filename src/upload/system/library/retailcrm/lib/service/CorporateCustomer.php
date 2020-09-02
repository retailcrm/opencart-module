<?php

namespace retailcrm\service;

use retailcrm\repository\CustomerRepository;
use retailcrm\Utils;

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

    /**
     * @param $order_data
     * @param $customer
     * @return int|null
     */
    public function createCorporateCustomer($order_data, $customer) {
        if (!empty($customer['externalId'])) {
            return $this->createFromExistingCustomer($order_data, $customer);
        }

        if (!empty($customer['id'])) {
            return $this->createFromNotExistingCustomer($order_data, $customer);
        }

        return null;
    }

    public function buildFromExistingCustomer($customer_data, $order_data) {
        $builder = CorporateCustomerBuilder::create()
            ->setCompany($order_data['payment_company'])
            ->setCustomerExternalId($customer_data['customer_id']);

        return $builder->build();
    }

    public function buildCorporateCustomerFromOrder($order_data, $crm_customer_id) {
        $builder = CorporateCustomerBuilder::create()
            ->setCompany($order_data['payment_company'])
            ->setCustomerId($crm_customer_id);

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

        if ($search && $search->isSuccessful()) {
            $customer = false;

            if (!empty($search['customersCorporate'])) {
                if ($returnGroup) {
                    return $search['customersCorporate'];
                } else {
                    $dataCorporateCustomers = $search['customersCorporate'];
                    $customer = reset($dataCorporateCustomers);
                }
            }

            return $customer;
        }

        return false;
    }

    /**
     * @param $order_data
     * @param $customer
     * @return int|null
     */
    private function createFromExistingCustomer($order_data, $customer) {
        $response = $this->api->customersGet($customer['externalId']);
        if (!$response || !$response->isSuccessful() || empty($response['customer'])) {
            return null;
        }

        $customer_data = $this->customer_repository->getCustomer($customer['externalId']);

        $corp_client = $this->searchCorporateCustomer(array(
            'contactIds' => array($response['customer']['id']),
            'companyName' => $order_data['payment_company']
        ));

        if (empty($corp_client)) {
            $corp_client = $this->searchCorporateCustomer(array(
                'companyName' => $order_data['payment_company']
            ));
        }

        if ($corp_client) {
            $this->updateOrCreateAddress($order_data, $corp_client);

            return $corp_client['id'];
        }

        $response = $this->api->customersCorporateCreate($this->buildFromExistingCustomer($customer_data, $order_data));
        if ($response && $response->isSuccessful()) {
            $this->createAddressAndCompany($order_data, $response['id']);

            return $response['id'];
        }

        return null;
    }

    /**
     * @param $order_data
     * @param $customer
     * @return int|null
     */
    private function createFromNotExistingCustomer($order_data, $customer) {
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
            $this->updateOrCreateAddress($order_data, $corp_client);

            return $corp_client['id'];
        }

        $response = $this->api->customersCorporateCreate(
            $this->buildCorporateCustomerFromOrder($order_data, $customer['id'])
        );

        if ($response && $response->isSuccessful()) {
            $this->createAddressAndCompany($order_data, $response['id']);

            return $response['id'];
        }

        return null;
    }

    private function createAddressAndCompany($order_data, $corp_client_id) {
        $corp_address = CorporateCustomerBuilder::create(false)->buildAddress($order_data);
        $address_response = $this->api->customersCorporateAddressesCreate($corp_client_id, $corp_address, 'id');

        if ($address_response && $address_response->isSuccessful()) {
            $company = CorporateCustomerBuilder::create(false)
                ->setCompany($order_data['payment_company'])
                ->setCompanyAddressId($address_response['id'])
                ->buildCompany($order_data);

            $this->api->customersCorporateCompaniesCreate($corp_client_id, $company, 'id');
        }
    }

    /**
     * @param $order_data
     * @param $corp_client
     *
     * @return void
     */
    private function updateOrCreateAddress($order_data, $corp_client) {
        $address_id = null;
        $addresses_response = $this->api->customersCorporateAddresses($corp_client['id'], array(), null, null, 'id');
        $corp_address = CorporateCustomerBuilder::create(false)->buildAddress($order_data);

        if ($addresses_response && $addresses_response->isSuccessful() && !empty($addresses_response['addresses'])) {
            foreach ($addresses_response['addresses'] as $address) {
                if (Utils::addressEquals($corp_address, $address)) {
                    $address_id = $address['id'];
                    $exist_address = $address;

                    break;
                }
            }
        }

        if (!isset($exist_address)) {
            $response = $this->api->customersCorporateAddressesCreate(
                $corp_client['id'],
                $corp_address,
                'id'
            );

            if ($response && $response->isSuccessful() && isset($response['id'])) {
                $address_id = $response['id'];
            }
        }

        $company = CorporateCustomerBuilder::create(false)
            ->setCompany($order_data['payment_company'])
            ->setCompanyAddressId($address_id)
            ->buildCompany($order_data);
        $companies = $this->api->customersCorporateCompanies($corp_client['id'], array(), null, null, 'id');

        if ($companies && $companies->isSuccessful() && !empty($companies['companies'])) {
            foreach ($companies['companies'] as $crm_company) {
                if ($crm_company['name'] === $order_data['payment_company']) {
                    $this->api->customersCorporateCompaniesEdit(
                        $corp_client['id'],
                        $crm_company['id'],
                        $company,
                        'id',
                        'id'
                    );
                }
            }
        }
    }
}
