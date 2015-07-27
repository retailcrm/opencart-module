<?php

require_once DIR_SYSTEM . 'library/retailcrm.php';

class ModelRetailcrmReferences extends Model
{
    protected $retailcrm;

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
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new ApiHelper($settings);

            try {
                $response = $this->retailcrm->api->deliveryTypesList();
                if ($response->isSuccessful() && $response->getStatusCode() == 200) {
                    return $response->deliveryTypes;
                } else {
                    $this->log->write(
                        sprintf(
                            "RestApi::deliveryTypesList::Errors: [HTTP-status %s] %s",
                            $response->getStatusCode(),
                            $response->getErrorMsg()
                        )
                    );

                    if (isset($response['errors'])) {
                        foreach ($response['errors'] as $error) {
                            $this->log->write(
                                sprintf(
                                    "RestApi::deliveryTypesList::Errors: %s", $error
                                )
                            );
                        }
                    }

                    return array();
                }
            } catch (CurlException $e) {
                $this->data['retailcrm_error'][] = $e->getMessage();
                $this->log->write('RestApi::deliveryTypesList::Curl:' . $e->getMessage());
            } catch (InvalidJsonException $e) {
                $this->data['retailcrm_error'][] = $e->getMessage();
                $this->log->write('RestApi::deliveryTypesList::JSON:' . $e->getMessage());
            }
        } else {
            return array();
        }
    }

    protected function getApiOrderStatuses()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new ApiHelper($settings);

            try {
                $response = $this->retailcrm->api->statusesList();
                if ($response->isSuccessful() && $response->getStatusCode() == 200) {
                    return $response->statuses;
                } else {
                    $this->log->write(
                        sprintf(
                            "RestApi::statusesList::Errors: [HTTP-status %s] %s",
                            $response->getStatusCode(),
                            $response->getErrorMsg()
                        )
                    );

                    if (isset($response['errors'])) {
                        foreach ($response['errors'] as $error) {
                            $this->log->write(
                                sprintf(
                                    "RestApi::statusesList::Errors: %s", $error
                                )
                            );
                        }
                    }

                    return array();
                }
            } catch (CurlException $e) {
                $this->data['retailcrm_error'][] = $e->getMessage();
                $this->log->write('RestApi::orderStatusesList::Curl:' . $e->getMessage());
            } catch (InvalidJsonException $e) {
                $this->data['retailcrm_error'][] = $e->getMessage();
                $this->log->write('RestApi::orderStatusesList::JSON:' . $e->getMessage());
            }
        } else {
            return array();
        }
    }

    protected function getApiPaymentTypes()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            $this->retailcrm = new ApiHelper($settings);

            try {
                $response = $this->retailcrm->api->paymentTypesList();
                if ($response->isSuccessful() && $response->getStatusCode() == 200) {
                    return $response->paymentTypes;
                } else {
                    $this->log->write(
                        sprintf(
                            "RestApi::paymentTypesList::Errors: [HTTP-status %s] %s",
                            $response->getStatusCode(),
                            $response->getErrorMsg()
                        )
                    );

                    if (isset($response['errors'])) {
                        foreach ($response['errors'] as $error) {
                            $this->log->write(
                                sprintf(
                                    "RestApi::paymentTypesList::Errors: %s", $error
                                )
                            );
                        }
                    }

                    return array();
                }
            } catch (CurlException $e) {
                $this->data['retailcrm_error'][] = $e->getMessage();
                $this->log->write('RestApi::paymentTypesList::Curl:' . $e->getMessage());
            } catch (InvalidJsonException $e) {
                $this->data['retailcrm_error'][] = $e->getMessage();
                $this->log->write('RestApi::paymentTypesList::JSON:' . $e->getMessage());
            }
        } else {
            return array();
        }
    }
}
