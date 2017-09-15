<?php
class ControllerApiRetailcrm extends Controller 
{    
    public function getDeliveryTypes()
    {
        $this->load->model('localisation/country');
        $this->load->model('setting/setting');
        $moduleTitle = $this->getModuleTitle();
        $countries = $this->model_setting_setting->getSetting($moduleTitle)[$moduleTitle . '_country'];
        $deliveryTypes = array();

        foreach ($countries as $country) {
            $deliveryTypes = array_merge($deliveryTypes, $this->getDeliveryTypesByZones($country));
        }

        if (isset($this->request->server['HTTP_ORIGIN'])) {
            $this->response->addHeader('Access-Control-Allow-Origin: ' . $this->request->server['HTTP_ORIGIN']);
            $this->response->addHeader('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
            $this->response->addHeader('Access-Control-Max-Age: 1000');
            $this->response->addHeader('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($deliveryTypes));
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
                    
                    if($this->{'model_extension_shipping_' . $shippingModule['code']}->getQuote($address)) {
                        $quote_data[] = $this->{'model_extension_shipping_' . $shippingModule['code']}->getQuote($address);
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

    private function getModuleTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }
}
