<?php

require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

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
    private $_error = array();
    protected $log, $statuses, $payments, $deliveryTypes, $retailcrm;
    public $children, $template;

    /**
     * Install method
     *
     * @return void
     */
    public function install()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting
            ->editSetting('retailcrm', array(
                'retailcrm_status' => 1,
                'retailcrm_country' => array($this->config->get('config_country_id'))
            ));

        $this->load->model('extension/event');

        $this->model_extension_event
            ->addEvent(
                'retailcrm',
                version_compare(VERSION, '2.2', '>=') ? 'catalog/model/checkout/order/addOrder/after' : 'post.order.add',
                'module/retailcrm/order_create'
            );

        $this->model_extension_event
            ->addEvent(
                'retailcrm',
                version_compare(VERSION, '2.2', '>=') ? 'catalog/model/checkout/order/addOrderHistory/after' : 'post.order.history.add',
                'module/retailcrm/order_edit'
            );

        $this->model_extension_event
            ->addEvent(
                'retailcrm',
                version_compare(VERSION, '2.2', '>=') ? 'catalog/model/account/customer/addCustomer/after' : 'post.customer.add',
                'module/retailcrm/customer_create'
            );
    }

    /**
     * Uninstall method
     *
     * @return void
     */
    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting
            ->editSetting('retailcrm', array('retailcrm_status' => 0));

        $this->load->model('extension/event');
        $this->model_extension_event->deleteEvent('retailcrm');
    }

    /**
     * Setup page
     *
     * @return void
     */
    public function index()
    {

        $this->load->model('setting/setting');
        $this->load->model('extension/module');
        $this->load->model('retailcrm/references');
        $this->load->model('localisation/country');
        $this->load->language('module/retailcrm');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('/admin/view/stylesheet/retailcrm.css');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->model_setting_setting->editSetting(
                'retailcrm',
                $this->request->post
            );

            $this->session->data['success'] = $this->language->get('text_success');
            $redirect = $this->url->link(
                'module/retailcrm', 'token=' . $this->session->data['token'],
                'SSL'
            );

            $this->response->redirect($redirect);
        }

        $text_strings = array(
            'heading_title',
            'text_enabled',
            'text_disabled',
            'button_save',
            'button_cancel',
            'text_notice',
            'retailcrm_title',
            'retailcrm_url',
            'retailcrm_apikey',
            'retailcrm_base_settings',
            'retailcrm_dict_settings',
            'retailcrm_dict_delivery',
            'retailcrm_dict_status',
            'retailcrm_dict_payment',
            'retailcrm_countries_settings'
        );

        $this->load->model('extension/extension');
        $_data = &$data;

        foreach ($text_strings as $text) {
            $_data[$text] = $this->language->get($text);
        }

        $_data['retailcrm_errors'] = array();
        $_data['saved_settings'] = $this->model_setting_setting
            ->getSetting('retailcrm');

        $url = isset($_data['saved_settings']['retailcrm_url'])
            ? $_data['saved_settings']['retailcrm_url']
            : null;
        $key = isset($_data['saved_settings']['retailcrm_apikey'])
            ? $_data['saved_settings']['retailcrm_apikey']
            : null;

        if (!empty($url) && !empty($key)) {

            $this->retailcrm = new RetailcrmProxy(
                $url,
                $key,
                $this->setLogs()
            );

            $_data['delivery'] = $this->model_retailcrm_references
                ->getDeliveryTypes();
            $_data['statuses'] = $this->model_retailcrm_references
                ->getOrderStatuses();
            $_data['payments'] = $this->model_retailcrm_references
                ->getPaymentTypes();

        }

        $config_data = array(
            'retailcrm_status'
        );

        foreach ($config_data as $conf) {
            if (isset($this->request->post[$conf])) {
                $_data[$conf] = $this->request->post[$conf];
            } else {
                $_data[$conf] = $this->config->get($conf);
            }
        }

        if (isset($this->error['warning'])) {
            $_data['error_warning'] = $this->error['warning'];
        } else {
            $_data['error_warning'] = '';
        }

        $_data['breadcrumbs'] = array();

        $_data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link(
                'common/home',
                'token=' . $this->session->data['token'], 'SSL'
            ),
            'separator' => false
        );

        $_data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link(
                'extension/module',
                'token=' . $this->session->data['token'], 'SSL'
            ),
            'separator' => ' :: '
        );

        $_data['breadcrumbs'][] = array(
            'text'      => $this->language->get('retailcrm_title'),
            'href'      => $this->url->link(
                'module/retailcrm',
                'token=' . $this->session->data['token'], 'SSL'
            ),
            'separator' => ' :: '
        );

        $_data['action'] = $this->url->link(
            'module/retailcrm',
            'token=' . $this->session->data['token'], 'SSL'
        );

        $_data['cancel'] = $this->url->link(
            'extension/module',
            'token=' . $this->session->data['token'], 'SSL'
        );

        $_data['modules'] = array();

        if (isset($this->request->post['retailcrm_module'])) {
            $_data['modules'] = $this->request->post['retailcrm_module'];
        } elseif ($this->config->get('retailcrm_module')) {
            $_data['modules'] = $this->config->get('retailcrm_module');
        }

        $this->load->model('design/layout');
        $_data['layouts'] = $this->model_design_layout->getLayouts();
        $_data['countries'] = $this->model_localisation_country->getCountries();
        $_data['header'] = $this->load->controller('common/header');
        $_data['column_left'] = $this->load->controller('common/column_left');
        $_data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput(
            $this->load->view('module/retailcrm.tpl', $_data)
        );
    }

    /**
     * History method
     *
     * @return void
     */
    public function history()
    {
        if (file_exists(DIR_APPLICATION . 'model/retailcrm/custom/history.php')) {
            $this->load->model('retailcrm/custom/history');
            $this->model_retailcrm_custom_history->request();
        } else {
            $this->load->model('retailcrm/history');
            $this->model_retailcrm_history->request();
        }
    }

    /**
     * ICML generation
     *
     * @return void
     */
    public function icml()
    {
        if (file_exists(DIR_APPLICATION . 'model/retailcrm/custom/icml.php')) {
            $this->load->model('retailcrm/custom/icml');
            $this->model_retailcrm_custom_icml->generateICML();
        } else {
            $this->load->model('retailcrm/icml');
            $this->model_retailcrm_icml->generateICML();
        }
    }

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
            $data['order_status'] = $status['retailcrm_status'][$data['order_status_id']];

            $this->load->model('retailcrm/order');
            $this->model_retailcrm_order->sendToCrm($data, $data['order_id']);
        }
    }

    /**
     * Export orders
     *
     *
     */
    public function export() {
        if(version_compare(VERSION, '2.1', '<')) {
            $this->load->model('sale/customer');
            $customers = $this->model_sale_customer->getCustomers();
        } else {
            $this->load->model('customer/customer');
            $customers = $this->model_customer_customer->getCustomers();
        }

        $this->load->model('retailcrm/customer');
        $this->model_retailcrm_customer->uploadToCrm($customers);

        $this->load->model('sale/order');
        $orders = $this->model_sale_order->getOrders();

        $fullOrders = array();
        foreach($orders as $order) {
            $fullOrder = $this->model_sale_order->getOrder($order['order_id']);

            $fullOrder['order_total'] = $this->model_sale_order->getOrderTotals($order['order_id']);

            $fullOrder['products'] = $this->model_sale_order->getOrderProducts($order['order_id']);
            foreach($fullOrder['products'] as $key=>$product) {
                $fullOrder['products'][$key]['option'] = $this->model_sale_order->getOrderOptions($product['order_id'], $product['order_product_id']);
            }

            $fullOrders[] = $fullOrder;
        }

        $this->load->model('retailcrm/order');
        $this->model_retailcrm_order->uploadToCrm($fullOrders);
    }

    /**
     * Validate
     *
     * @return bool
     */
    private function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/retailcrm')) {
            $this->_error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->_error) {
            return true;
        } else {
            return false;
        }
    }

    private function setLogs()
    {
        if (version_compare(VERSION, '2.1', '>')) {
            $logs = DIR_SYSTEM . 'storage/logs/retailcrm.log';
        } else {
            $logs = DIR_SYSTEM . 'logs/retailcrm.log';
        }

        return $logs;
    }
}
