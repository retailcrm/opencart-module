<?php

/**
 * Class ControllerModule
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion5
 */
class ControllerExtensionModuleRetailcrm extends Controller {

    private $retailcrmApiClient;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->library('retailcrm/retailcrm');
        $this->retailcrmApiClient = $this->retailcrm->getApiClient();
    }

    /**
     * Create order on event
     *
     * @param string $trigger
     * @param array $data
     * @param int $order_id order identificator
     *
     * @return void
     */
    public function order_create($trigger, $data, $order_id = null) {
        $this->load->model('checkout/order');
        $this->load->model('account/order');
        $this->load->library('retailcrm/retailcrm');

        $data = $this->model_checkout_order->getOrder($order_id);;
        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);
        $moduleTitle = $this->retailcrm->getModuleTitle();

        foreach ($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $data['products'][$key]['option'] = $productOptions;
            }
        }

        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSetting($moduleTitle);

        if (isset($data['order_status_id']) && $data['order_status_id'] > 0) {
            $data['order_status'] = $status[$moduleTitle . '_status'][$data['order_status_id']];
        }

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/order.php')) {
            $this->load->model('extension/retailcrm/custom/order');
            $order = $this->model_extension_retailcrm_custom_order->processOrder($data);
            $this->model_extension_retailcrm_custom_order->sendToCrm($order, $this->retailcrmApiClient, $data);
        } else {
            $this->load->model('extension/retailcrm/order');
            $order = $this->model_extension_retailcrm_order->processOrder($data);
            $this->model_extension_retailcrm_order->sendToCrm($order, $this->retailcrmApiClient, $data);
        }
    }

    /**
     * Update order on event
     *
     * @param string $trigger
     * @param array $parameter2
     *
     * @return void
     */
    public function order_edit($trigger, $parameter2 = null) {
        $order_id = $parameter2[0];

        $this->load->model('checkout/order');
        $this->load->model('account/order');
        $this->load->library('retailcrm/retailcrm');

        $moduleTitle = $this->retailcrm->getModuleTitle();
        $data = $this->model_checkout_order->getOrder($order_id);

        if ($data['order_status_id'] == 0) {
            return;
        }

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

        foreach ($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if (!empty($productOptions)) {
                $data['products'][$key]['option'] = $productOptions;
            }
        }

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting($moduleTitle);

            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status[$moduleTitle . '_status'][$data['order_status_id']];
            }

            if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/order.php')) {
                $this->load->model('extension/retailcrm/custom/order');
                $order = $this->model_extension_retailcrm_custom_order->processOrder($data, false);
                $this->model_extension_retailcrm_custom_order->sendToCrm($order, $this->retailcrmApiClient, $data, false);
            } else {
                $this->load->model('extension/retailcrm/order');
                $order = $this->model_extension_retailcrm_order->processOrder($data, false);
                $this->model_extension_retailcrm_order->sendToCrm($order, $this->retailcrmApiClient, $data, false);
            }
        }
    }

    /**
     * Create customer on event
     *
     * @param int $customerId customer identificator
     *
     * @return void
     */
    public function customer_create($parameter1, $parameter2 = null, $parameter3 = null) {
        $this->load->model('account/customer');
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');

        $customerId = $parameter3;
        $customer = $this->model_account_customer->getCustomer($customerId);

        if ($this->request->post) {
            $country = $this->model_localisation_country->getCountry($this->request->post['country_id']);
            $zone = $this->model_localisation_zone->getZone($this->request->post['zone_id']);

            $customer['address'] = array(
                'address_1' => $this->request->post['address_1'],
                'address_2' => $this->request->post['address_2'],
                'city' => $this->request->post['city'],
                'postcode' => $this->request->post['postcode'],
                'iso_code_2' => $country['iso_code_2'],
                'zone' => $zone['name']
            );
        }

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/customer.php')) {
            $this->load->model('extension/retailcrm/custom/customer');
            $this->model_extension_retailcrm_custom_customer->sendToCrm($customer, $this->retailcrmApiClient);
        } else {
            $this->load->model('extension/retailcrm/customer');
            $this->model_extension_retailcrm_customer->sendToCrm($customer, $this->retailcrmApiClient);
        }
    }

    /**
     * Update customer on event
     *
     * @param int $customerId customer identificator
     *
     * @return void
     */
    public function customer_edit($parameter1, $parameter2, $parameter3) {
        $customerId = $this->customer->getId();

        $this->load->model('account/customer');
        $customer = $this->model_account_customer->getCustomer($customerId);

        $this->load->model('account/address');
        $customer['address'] = $this->model_account_address->getAddress($customer['address_id']);

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/customer.php')) {
            $this->load->model('extension/retailcrm/custom/customer');
            $this->model_extension_retailcrm_custom_customer->changeInCrm($customer, $this->retailcrmApiClient);
        } else {
            $this->load->model('extension/retailcrm/customer');
            $this->model_extension_retailcrm_customer->changeInCrm($customer, $this->retailcrmApiClient);
        }
    }
}
