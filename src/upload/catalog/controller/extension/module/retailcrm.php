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

    /**
     * Create order on event
     *
     * @param string $route
     * @param array $args
     * @param int $output
     *
     * @return boolean
     */
    public function orderCreate($route, $args, $output) {
        if ($route != 'checkout/order/addOrder') {
            return false;
        }

        $this->load->library('retailcrm/retailcrm');

        $retailcrm_order = $this->retailcrm->getObject('Order');
        $retailcrm_order->prepare($args[0]);
        $retailcrm_order->setField('externalId', $output);
        $retailcrm_order->create($this->retailcrm->getApiClient());

        return true;
    }

    /**
     * Update order on event
     *
     * @param string $route
     * @param array $args
     *
     * @return boolean
     */
    public function orderEdit($route, $args) {
        if ($route != 'checkout/order/editOrder') {
            return false;
        }

        $order_id = $args[0];

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
                $this->model_extension_retailcrm_custom_order->sendToCrm($order, $this->retailcrmApiClient, false);
            } else {
                $this->load->model('extension/retailcrm/order');
                $order = $this->model_extension_retailcrm_order->processOrder($data, false);
                $this->model_extension_retailcrm_order->sendToCrm($order, $this->retailcrmApiClient, false);
            }
        }
    }

    /**
     * Create customer on event
     *
     * @param string $route
     * @param array $args
     * @param int $output
     *
     * @return boolean
     */
    public function customerCreate($route, $args, $output) {
        if ($route != 'account/customer/addCustomer') {
            return false;
        }

        $this->load->library('retailcrm/retailcrm');
        $retailcrm_customer = $this->retailcrm->createObject('Customer');
        $retailcrm_customer->prepare($args[0]);
        $retailcrm_customer->setField('externalId', $output);
        $retailcrm_customer->create();

        return true;
    }

    /**
     * Update customer on event
     *
     * @param string $route
     * @param array $args
     * @return boolean
     */
    public function customerEdit($route, $args) {
        if ($route != 'account/customer/editCustomer') {
            return false;
        }

        $customer_id = $args[0];
        $data = $args[1];

        $this->load->library('retailcrm/customer');
        $this->retailcrm->process($data);
        $this->retailcrm->setField('externalId', $customer_id);
        $this->retailcrm->edit();

        return true;
    }
}
