<?php

class ControllerApiRetailcrm extends Controller 
{
    public function getDeliveryTypes()
    {
        $api = $this->auth();

        if (isset($api['error'])) {
            $response = $api;
        } else {
            $this->load->model('localisation/country');
            $this->load->model('setting/setting');
            $this->load->library('retailcrm/retailcrm');
            $moduleTitle = $this->retailcrm->getModuleTitle();
            $countries = $this->model_setting_setting->getSetting($moduleTitle)[$moduleTitle . '_country'];
            $response = array();

            foreach ($countries as $country) {
                $response = array_merge($response, $this->getDeliveryTypesByZones($country));
            }
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    public function addOrderHistory()
    {
        $api = $this->auth();

        if (isset($api['error'])) {
            $response = $api;
        } elseif (!isset($this->request->post['order_id']) || !isset($this->request->post['order_status_id'])) {
            $response = array('error' => 'Not found data');
        } else {
            $this->load->model('checkout/order');
            $this->model_checkout_order->addOrderHistory($this->request->post['order_id'], $this->request->post['order_status_id']);
            $response = array('success' => true);
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($response));
    }

    protected function getDeliveryTypesByZones($country_id)
    {    
        $this->loadModels();
        $this->load->model('localisation/zone');
        $this->load->model('localisation/country');

        $shippingModules = $this->{'model_' . $this->modelExtension}->getExtensions('shipping');
        $zones = $this->model_localisation_zone->getZonesByCountryId($country_id);
        $country = $this->model_localisation_country->getCountry($country_id);
        $quote_data = array();

        foreach ($zones as $zone) {
            $address = array(
                'country_id' => $country_id,
                'zone_id' => $zone['zone_id'],
                'iso_code_2' => $country['iso_code_2'],
                'iso_code_3' => $country['iso_code_3'],
                'zone_code' => $zone['code'],
                'postcode' => '',
                'city' => ''
            );
            
            foreach ($shippingModules as $shippingModule) {
                $this->load->model('extension/shipping/' . $shippingModule['code']);

                if (version_compare(VERSION, '3.0', '<')) {
                    $shippingCode = $shippingModule['code'];
                } else {
                    $shippingCode = 'shipping_' . $shippingModule['code'];
                }

                if ($this->config->get($shippingCode . '_status')) {
                    if ($shippingCode == 'free') {
                        $free_total = $this->config->get('free_total');

                        if ($free_total > 0) {
                            $this->config->set('free_total', 0);
                        }
                    }
                    
                    if ($this->{'model_extension_shipping_' . $shippingModule['code']}->getQuote($address)) {
                        $quote_data[] = $this->{'model_extension_shipping_' . $shippingModule['code']}->getQuote($address);
                    } else {
                        $this->load->language('extension/shipping/' . $shippingModule['code']);

                        $quote_data[] = array(
                            'code' => $shippingModule['code'],
                            'title' => $this->language->get('text_title'),
                            'quote' => array(
                                array(
                                    'code' => $shippingModule['code'],
                                    'title' => $this->language->get('text_title')
                                )
                            )
                        );
                    }
                }
            }
        }

        $deliveryTypes = array();

        foreach ($quote_data as $shipping) {
            foreach ($shipping['quote'] as $shippingMethod) {
                $deliveryTypes[$shipping['code']]['title'] = $shipping['title'];
                $deliveryTypes[$shipping['code']][$shippingMethod['code']] = $shippingMethod;
            }
        }

        return $deliveryTypes;
    }

    private function auth()
    {
        if (!isset($this->request->get['token'])
            || !$this->request->get['token']
        ) {
            return array('error' => 'Not found api key');
        }

        if (isset($this->request->get['token'])
            && !empty($this->request->get['token'])
        ) {
            $this->load->model('account/api');
            $api = $this->model_account_api->getApiByKey($this->request->get['token']);

            if (!empty($api)) {
                return $api;
            }

            return array('error' => 'Invalid api key');
        }
    }

    private function loadModels()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $this->load->model('extension/extension');

            $this->modelExtension = 'extension_extension';
        } else {
            $this->load->model('setting/extension');

            $this->modelExtension = 'setting_extension';
        }
    }
}
