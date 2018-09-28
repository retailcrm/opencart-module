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
        $data = $args[0];
        $data['order_id'] = $output;
        $retailcrm_order = $this->retailcrm->createObject(\Retailcrm\Order::class);

        $retailcrm_order->prepare($data);
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
        $data = $args[1];

        $this->load->library('retailcrm/retailcrm');
        $this->load->model('extension/module/retailcrm');

        $order_status_id = $this->model_extension_module_retailcrm->getOrderStatusId($order_id);
        $data['order_status_id'] = $order_status_id;
        $retailcrm_order = $this->retailcrm->createObject(\Retailcrm\Order::class);

        $retailcrm_order->prepare($data);
        $retailcrm_order->setField('externalId', $order_id);
        $retailcrm_order->edit($this->retailcrm->getApiClient());

        return true;
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
        $retailcrm_customer = $this->retailcrm->createObject(\Retailcrm\Customer::class);

        $retailcrm_customer->prepare($args[0]);
        $retailcrm_customer->setField('externalId', $output);
        $retailcrm_customer->create($this->retailcrm->getApiClient());

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

        $this->load->library('retailcrm/retailcrm');
        $retailcrm_customer = $this->retailcrm->createObject(\Retailcrm\Customer::class);

        $retailcrm_customer->process($data);
        $retailcrm_customer->setField('externalId', $customer_id);
        $retailcrm_customer->edit($this->retailcrm->getApiClient());

        return true;
    }
}
