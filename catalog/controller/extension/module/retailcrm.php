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
class ControllerExtensionModuleRetailcrm extends Controller
{
    /**
     * Create order on event
     *
     * @param int $order_id order identificator
     *
     * @return void
     */
    public function order_create($parameter1, $parameter2 = null, $parameter3 = null)
    {
        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $order_id = $parameter3;

        $data = $this->model_checkout_order->getOrder($order_id);
        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        foreach($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if(!empty($productOptions))
                $data['products'][$key]['option'] = $productOptions;
        }

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

            $this->load->model('extension/retailcrm/order');
            $this->model_extension_retailcrm_order->sendToCrm($data, $data['order_id']);
        }
    }

    public function order_edit($parameter1, $parameter2 = null) {
        $order_id = $parameter2[0];

        $this->load->model('checkout/order');
        $this->load->model('account/order');

        $data = $this->model_checkout_order->getOrder($order_id);

        if($data['order_status_id'] == 0) return;

        $data['products'] = $this->model_account_order->getOrderProducts($order_id);
        $data['totals'] = $this->model_account_order->getOrderTotals($order_id);

        foreach($data['products'] as $key => $product) {
            $productOptions = $this->model_account_order->getOrderOptions($order_id, $product['order_product_id']);

            if(!empty($productOptions))
                $data['products'][$key]['option'] = $productOptions;
        }

        if (!isset($data['fromApi'])) {
            $this->load->model('setting/setting');
            $status = $this->model_setting_setting->getSetting('retailcrm');

            if ($data['order_status_id'] > 0) {
                $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];
            }

            $this->load->model('extension/retailcrm/order');
            $this->model_extension_retailcrm_order->changeInCrm($data, $data['order_id']);
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
        $customerId = $parameter3;

        $this->load->model('account/customer');
        $customer = $this->model_account_customer->getCustomer($customerId);

        $this->load->model('extension/retailcrm/customer');
        $this->model_extension_retailcrm_customer->sendToCrm($customer);
    }
}
