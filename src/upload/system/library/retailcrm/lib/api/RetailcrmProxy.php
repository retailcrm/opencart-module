<?php

/**
 * Class RequestProxy
 *
 * @method ordersCreate($order, $site = null)
 * @method ordersEdit($order, $by = 'externalId', $site = null)
 * @method ordersGet($order, $by = 'externalId', $site = null)
 * @method ordersList(array $filter = [], $page = null, $limit = null)
 * @method customersCreate($customer, $site = null)
 * @method customersEdit($customer, $by = 'externalId', $site = null)
 * @method customersList(array $filter = [], $page = null, $limit = null)
 * @method customersGet($id, $by = 'externalId', $site = null)
 * @method customersCorporateList(array $filter = [], $page = null, $limit = null)
 * @method customersCorporateCreate(array $customerCorporate, $site = null)
 * @method customersCorporateAddresses($id, array $filter = [], $page = null, $limit = null, $by = 'externalId', $site = null)
 * @method customersCorporateAddressesCreate($id, array $address = [], $by = 'externalId', $site = null)
 * @method customersCorporateAddressesEdit($customerId, $addressId, array $address = [], $customerBy = 'externalId', $addressBy = 'externalId', $site = null)
 * @method customersCorporateCompanies($id, array $filter = [], $page = null, $limit = null, $by = 'externalId', $site = null)
 * @method customersCorporateCompaniesCreate($id, array $company = [], $by = 'externalId', $site = null)
 * @method customersCorporateCompaniesEdit($customerId, $companyId, array $company = [], $customerBy = 'externalId', $companyBy = 'externalId', $site = null)
 * @method customersCorporateContacts($id, array $filter = [], $page = null, $limit = null, $by = 'externalId', $site = null)
 */
class RetailcrmProxy {
    private $api;
    private $log;

    public function __construct($url, $key) {
        $this->api = new RetailcrmApiClient5($url, $key);
        $this->log = new \Log('retailcrm.log');
    }

    public function __call($method, $arguments) {
        try {
            $response = call_user_func_array([$this->api, $method], $arguments);

            if (!$response->isSuccessful()) {
                $this->log->write(sprintf("[%s] %s", $method, $response->getErrorMsg()));

                if (isset($response['errors'])) {
                    $error = implode("\n", $response['errors']);
                    $this->log->write($error . "\n");
                }
            }

            return $response;
        } catch (CurlException $e) {
            $this->log->write(sprintf("[%s] %s", $method, $e->getMessage()));
            return false;
        } catch (InvalidJsonException $e) {
            $this->log->write(sprintf("[%s] %s", $method, $e->getMessage()));
            return false;
        }
    }
}
