<?php

class ModelExtensionRetailcrmCustomer extends Model {
    /**
     * Upload customers
     *
     * @param array $customers
     *
     * @return mixed
     */
    public function uploadToCrm($customers)
    {
        $customersToCrm = array();
        /** @var CustomerManager $customer_manager */
        $customer_manager = $this->retailcrm->getCustomerManager();

        foreach($customers as $customer) {
            $customersToCrm[] = $customer_manager->prepareCustomer($customer, array());
        }

        $chunkedCustomers = array_chunk($customersToCrm, 50);

        foreach($chunkedCustomers as $customersPart) {
            $customer_manager->uploadCustomers($customersPart);
        }

        return $chunkedCustomers;
    }
}
