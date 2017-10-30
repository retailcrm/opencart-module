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
    public function order_create($parameter1, $parameter2 = null)
    {
        $this->load->model('checkout/order');
        $this->load->model('account/order');

        if($parameter2 != null)
            $order_id = $parameter2;
        else
            $order_id = $parameter1;

        $data = $this->model_checkout_order->getOrder($order_id);

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        foreach($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if(!empty($productOptions))
                $data['products'][$key]['option'] = $productOptions;
        }

        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting('retailcrm');
            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];
            }

            $this->load->model('retailcrm/order');
            $this->model_retailcrm_order->sendToCrm($data, $data['order_id']);
        }
    }

    public function order_edit($parameter1, $parameter2 = null, $parameter3 = null, $parameter4 = null) {
        if($parameter4 != null)
            $order_id = $parameter3;
        else
            $order_id = $parameter1;

        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $data = $this->model_checkout_order->getOrder($order_id);

        if($data['order_status_id'] == 0) return;

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);

        foreach($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if(!empty($productOptions))
                $data['products'][$key]['option'] = $productOptions;
        }

        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

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

    /**
     * Create customer on event
     *
     * @param int $customerId customer identificator
     *
     * @return void
     */
    public function customer_create($parameter1, $parameter2 = null) {
        if($parameter2 != null)
            $customerId = $parameter2;
        else
            $customerId = $parameter1;

        $this->load->model('account/customer');
        $customer = $this->model_account_customer->getCustomer($customerId);

        $this->load->model('retailcrm/customer');
        $this->model_retailcrm_customer->sendToCrm($customer);
    }
}
