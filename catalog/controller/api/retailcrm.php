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
            $setting = $this->model_setting_setting->getSetting('retailcrm');
            $response = array();

            if (isset($setting['retailcrm_country']) && $setting['retailcrm_country']) {
                foreach ($setting['retailcrm_country'] as $country) {
                    $response = $this->mergeDeliveryTypes($country, $response);
                }
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
        $this->load->model('extension/extension');
        $this->load->model('localisation/zone');
        $this->load->model('localisation/country');

        $shippingModules = $this->model_extension_extension->getExtensions('shipping');
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
                $this->load->model('shipping/' . $shippingModule['code']);

                if ($this->config->get($shippingModule['code'] . '_status')) {
                    if ($shippingModule['code'] == 'free') {
                        $free_total = $this->config->get('free_total');

                        if ($free_total > 0) {
                            $this->config->set('free_total', 0);
                        }
                    }

                    if($this->{'model_shipping_' . $shippingModule['code']}->getQuote($address)) {
                        $quote_data[] = $this->{'model_shipping_' . $shippingModule['code']}->getQuote($address);
                    } else {
                        $this->load->language('shipping/' . $shippingModule['code']);

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

    private function mergeDeliveryTypes($country, $result) {
        $delivery_types = $this->getDeliveryTypesByZones($country);
        foreach ($delivery_types as $shipping_module => $shipping_type) {
            if (isset($result[$shipping_module])) {
                $result[$shipping_module] = array_merge($result[$shipping_module], $shipping_type);
            } else {
                $result[$shipping_module] = $shipping_type;
            }
        }
        return $result;
    }

    private function auth()
    {
        if (version_compare(VERSION, '2.1.0', '>=')) {
            if (!isset($this->request->get['key'])
                || !$this->request->get['key']
            ) {
                return array('error' => 'Not found api key');
            }

            if (isset($this->request->get['key'])
                && !empty($this->request->get['key'])
            ) {
                $this->load->model('account/api');
                $api = $this->model_account_api->getApiByKey($this->request->get['key']);

                if (!empty($api)) {
                    return $api;
                }

                return array('error' => 'Invalid api key');
            }
        } else {
            if (!isset($this->request->get['username'])
                || !$this->request->get['username']
                || !isset($this->request->get['password'])
                || !$this->request->get['password']
            ) {
                return array('error' => 'Not found api user');
            }

            if (isset($this->request->get['username'])
                && !empty($this->request->get['username'])
                && isset($this->request->get['password'])
                && !empty($this->request->get['password'])
            ) {
                $this->load->model('account/api');
                $api = $this->model_account_api->login($this->request->get['username'], $this->request->get['password']);

                if (!empty($api)) {
                    return $api;
                }

                return array('error' => 'Invalid api user');
            }
        }
    }
}
