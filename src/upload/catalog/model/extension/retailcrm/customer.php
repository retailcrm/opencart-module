<?php

class ModelExtensionRetailcrmCustomer extends Model {
    protected $settings;
    protected $moduleTitle;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
    }

    /**
     * Create customer
     * 
     * @param array $customer
     * 
     * @return mixed
     */
    public function sendToCrm($customer, $retailcrmApiClient)
    {
        if (empty($customer) || $retailcrmApiClient === false) {
            return false;
        }

        $customerToCrm = $this->process($customer);

        $retailcrmApiClient->customersCreate($customerToCrm);

        return $customerToCrm;
    }

    /**
     * Edit customer
     * 
     * @param array $customer
     * 
     * @return mixed
     */
    public function changeInCrm($customer, $retailcrmApiClient)
    {
        if (empty($customer) || $retailcrmApiClient === false) {
            return false;
        }

        $customerToCrm = $this->process($customer);
        
        $retailcrmApiClient->customersEdit($customerToCrm);

        return $customerToCrm;
    }

    /**
     * Process customer
     *
     * @param array $customer
     *
     * @return array $customerToCrm
     */
    private function process($customer) {
        $customerToCrm = array(
            'externalId' => $customer['customer_id'],
            'firstName' => $customer['firstname'],
            'lastName' => $customer['lastname'],
            'email' => $customer['email'],
            'phones' => array(
                array(
                    'number' => $customer['telephone']
                )
            ),
            'createdAt' => $customer['date_added']
        );

        if (isset($customer['address'])) {
            $customerToCrm['address'] = array(
                'index' => $customer['address']['postcode'],
                'countryIso' => $customer['address']['iso_code_2'],
                'region' => $customer['address']['zone'],
                'city' => $customer['address']['city'],
                'text' => $customer['address']['address_1'] . ' ' . $customer['address']['address_2'] 
            );
        }
        
        if (isset($this->settings[$this->moduleTitle . '_custom_field']) && $customer['custom_field']) {
            $customFields = json_decode($customer['custom_field']);
            
            foreach ($customFields as $key => $value) {
                if (isset($this->settings[$this->moduleTitle . '_custom_field']['c_' . $key])) {
                    $customFieldsToCrm[$this->settings[$this->moduleTitle . '_custom_field']['c_' . $key]] = $value;
                }
            }
            
            if (isset($customFieldsToCrm)) {
                $customerToCrm['customFields'] = $customFieldsToCrm;
            }
        }

        return $customerToCrm;
    }
}
