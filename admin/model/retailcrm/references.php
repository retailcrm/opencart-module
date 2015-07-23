<?php

class ModelRetailcrmReferences extends Model
{

    public function getDeliveryTypes()
    {
        return array(
            'opencart' => $this->getOpercartDeliveryTypes(),
            'retailcrm' => $this->getApiDeliveryTypes()
        );
    }

    public function getOrderStatuses()
    {
        return array(
            'opencart' => $this->getOpercartOrderStatuses(),
            'retailcrm' => $this->getApiOrderStatuses()
        );
    }

    public function getPaymentTypes()
    {
        return array(
            'opencart' => $this->getOpercartPaymentTypes(),
            'retailcrm' => $this->getApiPaymentTypes()
        );
    }

    protected function getOpercartDeliveryTypes()
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

    protected function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');

        return $this->model_localisation_order_status
            ->getOrderStatuses(array());
    }

    protected function getOpercartPaymentTypes()
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

    protected function getApiDeliveryTypes()
    {
        try {
            return $this->retailcrm->deliveryTypesList();
        } catch (CurlException $e) {
            $this->data['retailcrm_error'][] = $e->getMessage();
            $this->log->addError('RestApi::deliveryTypesList::Curl:' . $e->getMessage());
        } catch (InvalidJsonException $e) {
            $this->data['retailcrm_error'][] = $e->getMessage();
            $this->log->addError('RestApi::deliveryTypesList::JSON:' . $e->getMessage());
        }
    }

    protected function getApiOrderStatuses()
    {
        try {
            return $this->retailcrm->orderStatusesList();
        } catch (CurlException $e) {
            $this->data['retailcrm_error'][] = $e->getMessage();
            $this->log->addError('RestApi::orderStatusesList::Curl:' . $e->getMessage());
        } catch (InvalidJsonException $e) {
            $this->data['retailcrm_error'][] = $e->getMessage();
            $this->log->addError('RestApi::orderStatusesList::JSON:' . $e->getMessage());
        }
    }

    protected function getApiPaymentTypes()
    {
        try {
            return $this->retailcrm->paymentTypesList();
        } catch (CurlException $e) {
            $this->data['retailcrm_error'][] = $e->getMessage();
            $this->log->addError('RestApi::paymentTypesList::Curl:' . $e->getMessage());
        } catch (InvalidJsonException $e) {
            $this->data['retailcrm_error'][] = $e->getMessage();
            $this->log->addError('RestApi::paymentTypesList::JSON:' . $e->getMessage());
        }
    }
}
