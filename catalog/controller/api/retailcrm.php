<?php
class ControllerApiRetailcrm extends Controller 
{	
	public function getDeliveryTypes()
	{
		$this->load->model('localisation/country');
		$this->load->model('setting/setting');

		$countries = $this->model_setting_setting->getSetting('retailcrm')['retailcrm_country'];
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
		$this->load->model('localisation/zone');
		$this->load->model('localisation/country');
		$this->load->model('extension/extension');

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

				$this->load->model('extension/shipping/' . $shippingModule['code']);
				
				if ($this->config->get($shippingModule['code'] . '_status')) {
					if($this->{'model_extension_shipping_' . $shippingModule['code']}->getQuote($address)) {
						$method_data = $this->{'model_extension_shipping_' . $shippingModule['code']}->getQuote($address);
						if($method_data['quote']) {
							$quote_data[] = $method_data;
						} else {
							$this->load->language('extension/shipping/' . $shippingModule['code']);
							$quote_data[] = array(
								'code' => $shippingModule['code'],
								'title' => $this->language->get('text_description')
							);
						}
					} else {
						$this->load->language('extension/shipping/' . $shippingModule['code']);
							$quote_data[] = array(
								'code' => $shippingModule['code'],
								'title' => $this->language->get('text_description')
							);
					}
				}
			}
		}

		$deliveryTypes = array();

		foreach ($quote_data as $shipping) {
			if(isset($shipping['quote']) && !empty($shipping['quote'])){
				foreach ($shipping['quote'] as $shippingMethod) {
					$deliveryTypes[$shipping['code']]['title'] = $shipping['title'];
					$deliveryTypes[$shipping['code']][$shippingMethod['code']] = $shippingMethod;
				}
			} else {
				$deliveryTypes[$shipping['code']]['title'] = $shipping['title'];
				$deliveryTypes[$shipping['code']][$shipping['code']] = $shipping;
			}
		}

		return $deliveryTypes;
	}
}
