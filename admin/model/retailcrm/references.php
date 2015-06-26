<?php

class ModelRetailcrmReferences extends Model {

    public function getOpercartDeliveryMethods()
    {
        $deliveryMethods = array();
        $files = glob(DIR_APPLICATION . 'controller/shipping/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('shipping/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $deliveryMethods[$extension.'.'.$extension] = strip_tags(
                        $this->language->get('heading_title')
                    );
                }
            }
        }

        return $deliveryMethods;
    }

    public function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');

        return $this->model_localisation_order_status
            ->getOrderStatuses(array());
    }

    public function getOpercartPaymentTypes()
    {
        $paymentTypes = array();
        $files = glob(DIR_APPLICATION . 'controller/payment/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('payment/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $paymentTypes[$extension] = strip_tags(
                        $this->language->get('heading_title')
                    );
                }
            }
        }

        return $paymentTypes;
    }
}
