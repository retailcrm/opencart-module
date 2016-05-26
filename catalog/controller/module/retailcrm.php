<?php

/**
 * Class ControllerModule
 *
 * @category RetailCrm
 * @package  RetailCrm
 * @author   RetailCrm <integration@retailcrm.ru>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.retailcrm.ru/docs/Developers/ApiVersion3
 */
class ControllerModuleRetailcrm extends Controller
{
    /**
     * Create order on event
     *
     * @param int $order_id order identificator
     *
     * @return void
     */
    public function order_create($order_id)
    {
        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $data = $this->model_checkout_order->getOrder($order_id);
        $data['products'] = $this->model_account_order->getOrderProducts($order_id);

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting('retailcrm');
            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];
            }

            $data['totals'][] = array(
                'code' => 'shipping',
                'value' => $this->session->data['shipping_method']['cost']
            );

            $this->load->model('retailcrm/order');
            $this->model_retailcrm_order->sendToCrm($data, $data['order_id']);
        }
    }

    public function order_edit($order_id) {
        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $data = $this->model_checkout_order->getOrder($order_id);

        if($data['order_status_id'] == 0) return;

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting('retailcrm');

            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];
            }

            $data['totals'][] = array(
                'code' => 'shipping',
                'value' => isset($this->session->data['shipping_method']) ? $this->session->data['shipping_method']['cost'] : ''
            );

            $this->load->model('retailcrm/order');
            $this->model_retailcrm_order->changeInCrm($data, $data['order_id']);
        }
    }
}
