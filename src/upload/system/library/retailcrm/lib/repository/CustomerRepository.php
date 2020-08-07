<?php

namespace retailcrm\repository;

class CustomerRepository extends \retailcrm\Base {
    public function getCustomer($customer_id) {
        if (null !== $this->model_account_customer) {
            return $this->model_account_customer->getCustomer($customer_id);
        }

        if (null !== $this->model_customer_customer) {
            return $this->model_customer_customer->getCustomer($customer_id);
        }

        return array();
    }

    public function getAddress($address_id) {
        if (null !== $this->model_customer_customer) {
            $address_model = $this->model_customer_customer;
        } elseif (null !== $this->model_account_address) {
            $address_model = $this->model_account_address;
        } else {
            return array();
        }

        return $address_model->getAddress($address_id);
    }
}
