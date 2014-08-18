<?php

require_once __DIR__ . '/../../../system/library/intarocrm/vendor/autoload.php';

class ControllerModuleIntarocrm extends Controller {
    private $error = array();

    public function install() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('intarocrm', array('intarocrm_status'=>1));
    }

    public function uninstall() {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('intarocrm', array('intarocrm_status'=>0));
    }

    public function index() {

        $this->log = new Monolog\Logger('opencart-module');
        $this->log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/../../../system/logs/intarocrm_module.log', Monolog\Logger::INFO));

        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->load->language('module/intarocrm');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('/admin/view/stylesheet/intarocrm.css');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('intarocrm', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
        }

        $text_strings = array(
            'heading_title',
            'text_enabled',
            'text_disabled',
            'button_save',
            'button_cancel',
            'text_notice',
            'intarocrm_url',
            'intarocrm_apikey',
            'intarocrm_base_settings',
            'intarocrm_dict_settings',
            'intarocrm_dict_delivery',
            'intarocrm_dict_status',
            'intarocrm_dict_payment',
        );

        foreach ($text_strings as $text) {
            $this->data[$text] = $this->language->get($text);
        }

        $this->data['intarocrm_errors'] = array();
        $this->data['saved_settings'] = $this->model_setting_setting->getSetting('intarocrm');

        if ($this->data['saved_settings']['intarocrm_url'] != '' && $this->data['saved_settings']['intarocrm_apikey'] != '') {

            $this->intarocrm = new \IntaroCrm\RestApi(
                $this->data['saved_settings']['intarocrm_url'],
                $this->data['saved_settings']['intarocrm_apikey']
            );

            /*
             * Delivery
             */

            try {
                $this->deliveryTypes = $this->intarocrm->deliveryTypesList();
            }
            catch (ApiException $e)
            {
                $this->data['intarocrm_error'][] = $e->getMessage();
                $this->log->addError('['.$this->config->get('store_name').'] RestApi::deliveryTypesList::Api:' . $e->getMessage());
            }
            catch (CurlException $e)
            {
                $this->data['intarocrm_error'][] = $e->getMessage();
                $this->log->addError('['.$this->config->get('store_name').'] RestApi::deliveryTypesList::Curl:' . $e->getMessage());
            }

            $this->data['delivery'] = array(
                'opencart' => $this->getOpercartDeliveryMethods(),
                'intarocrm' => $this->deliveryTypes
            );

            /*
             * Statuses
             */
            try {
                $this->statuses = $this->intarocrm->orderStatusesList();
            }
            catch (ApiException $e)
            {
                $this->data['intarocrm_error'][] = $e->getMessage();
                $this->log->addError('['.$this->config->get('store_name').'] RestApi::orderStatusesList::Api:' . $e->getMessage());
            }
            catch (CurlException $e)
            {
                $this->data['intarocrm_error'][] = $e->getMessage();
                $this->log->addError('['.$this->config->get('store_name').'] RestApi::orderStatusesList::Curl:' . $e->getMessage());
            }

            $this->data['statuses'] = array(
                'opencart' => $this->getOpercartOrderStatuses(),
                'intarocrm' => $this->statuses
            );

            /*
             * Payment
             */

            try {
                $this->payments = $this->intarocrm->paymentTypesList();
            }
            catch (ApiException $e)
            {
                $this->data['intarocrm_error'][] = $e->getMessage();
                $this->log->addError('['.$this->config->get('store_name').'] RestApi::paymentTypesList::Api:' . $e->getMessage());
            }
            catch (CurlException $e)
            {
                $this->data['intarocrm_error'][] = $e->getMessage();
                $this->log->addError('['.$this->config->get('store_name').'] RestApi::paymentTypesList::Curl:' . $e->getMessage());
            }

            $this->data['payments'] = array(
                'opencart' => $this->getOpercartPaymentTypes(),
                'intarocrm' => $this->payments
            );

        }

        $config_data = array(
            'intarocrm_status'
        );

        foreach ($config_data as $conf) {
            if (isset($this->request->post[$conf])) {
                $this->data[$conf] = $this->request->post[$conf];
            } else {
                $this->data[$conf] = $this->config->get($conf);
            }
        }

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }

        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text'      => $this->language->get('heading_title'),
            'href'      => $this->url->link('module/intarocrm', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['action'] = $this->url->link('module/intarocrm', 'token=' . $this->session->data['token'], 'SSL');

        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');


        $this->data['modules'] = array();

        if (isset($this->request->post['intarocrm_module'])) {
            $this->data['modules'] = $this->request->post['intarocrm_module'];
        } elseif ($this->config->get('intarocrm_module')) {
            $this->data['modules'] = $this->config->get('intarocrm_module');
        }

        $this->load->model('design/layout');

        $this->data['layouts'] = $this->model_design_layout->getLayouts();

        $this->template = 'module/intarocrm.tpl';
        $this->children = array(
            'common/header',
            'common/footer',
        );

        $this->response->setOutput($this->render());
    }

    public function order_history()
    {
        $this->log = new Monolog\Logger('opencart-module');
        $this->log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/../../../system/logs/intarocrm_module.log', Monolog\Logger::INFO));

        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('sale/order');
        $settings = $this->model_setting_setting->getSetting('intarocrm');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        if (isset($settings['intarocrm_url']) && $settings['intarocrm_url'] != '' && isset($settings['intarocrm_apikey']) && $settings['intarocrm_apikey'] != '') {
            include_once __DIR__ . '/../../../system/library/intarocrm/apihelper.php';
            $crm = new ApiHelper($settings);
            $orders = $crm->orderHistory();
            $forFix = array();

            foreach ($orders as $order)
            {
                $data = array();

                $delivery = array_flip($settings['intarocrm_delivery']);
                $payment = array_flip($settings['intarocrm_payment']);
                $status = array_flip($settings['intarocrm_status']);

                $ocPayment = $this->getOpercartPaymentTypes();
                $ocDelivery = $this->getOpercartDeliveryMethods();

                $data['store_id'] = ($this->config->get('config_store_id') == null) ? 0 : $this->config->get('config_store_id');
                $data['customer'] = $order['customer']['firstName'];
                $data['customer_id'] = (isset($order['customer']['externalId'])) ? $order['customer']['externalId']: '';
                $data['customer_group_id'] = '1';
                $data['firstname'] = $order['customer']['firstName'];
                $data['lastname'] = (isset($order['customer']['lastName'])) ? $order['customer']['lastName'] : ' ';
                $data['email'] = $order['customer']['email'];
                $data['telephone'] = (isset($order['customer']['phones'][0]['number'])) ? $order['customer']['phones'][0]['number'] : ' ';
                $data['comment'] = $order['customerComment'];

                $data['payment_address'] = '0';
                $data['payment_firstname'] = $order['firstName'];
                $data['payment_lastname'] = (isset($order['lastName'])) ? $order['lastName'] : ' ';
                $data['payment_address_1'] = $order['customer']['address']['text'];
                $data['payment_city'] = $order['customer']['address']['city'];
                $data['payment_postcode'] = $order['customer']['address']['index'];

                /*
                 * TODO: add country & zone id detection
                 */
                //$data['payment_country_id'] = '176';
                //$data['payment_zone_id'] = '2778';
                //$data['shipping_country_id'] = '176';
                //$data['shipping_zone_id'] = '2778';

                $data['shipping_address'] = '0';
                $data['shipping_firstname'] = $order['customer']['firstName'];
                $data['shipping_lastname'] = (isset($order['customer']['lastName'])) ? $order['customer']['lastName'] : ' ';
                $data['shipping_address_1'] = $order['delivery']['address']['text'];
                $data['shipping_city'] = $order['delivery']['address']['city'];
                $data['shipping_postcode'] = $order['delivery']['address']['index'];

                $data['shipping'] = $delivery[$order['delivery']['code']];
                $data['shipping_method'] = $ocDelivery[$data['shipping']];
                $data['shipping_code'] = $delivery[$order['delivery']['code']];
                $data['payment'] = $payment[$order['paymentType']];
                $data['payment_method'] = $ocPayment[$data['payment']];
                $data['payment_code'] = $payment[$order['paymentType']];
                $data['order_status_id'] = $status[$order['status']];
                
                $data['order_product'] = array();

                $subtotal = 0;
                $shipping = isset($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

                foreach($order['items'] as $item) {
                    $data['order_product'][] = array(
                        'product_id' => $item['offer']['externalId'],
                        'name' => $item['offer']['name'],
                        'quantity' => $item['quantity'],
                        'price' => $item['initialPrice'],
                        'total' => $item['initialPrice'] * $item['quantity'],
                    );

                    $subtotal += $item['initialPrice'] * $item['quantity'];
                }

                $subtotalSettings = $this->model_setting_setting->getSetting('sub_total');
                $totalSettings = $this->model_setting_setting->getSetting('total');
                $shippingSettings = $this->model_setting_setting->getSetting('shipping');

                $data['order_total'] = array(
                    array(
                        'order_total_id' => '',
                        'code' => 'sub_total',
                        'value' => $subtotal,
                        'sort_order' => $subtotalSettings['sub_total_sort_order']
                    ),
                    array(
                        'order_total_id' => '',
                        'code' => 'shipping',
                        'value' => $shipping,
                        'sort_order' => $shippingSettings['shipping_sort_order']
                    ),
                    array(
                        'order_total_id' => '',
                        'code' => 'total',
                        'value' => $subtotal + $shipping,
                        'sort_order' => $totalSettings['total_sort_order']
                    )
                );

                if (isset($order['externalId'])) {
                    $this->model_sale_order->editOrder($order['externalId'], $data);
                } else {
                    $this->model_sale_order->addOrder($data);
                    $last = $this->model_sale_order->getOrders($data = array('order' => 'DESC', 'limit' => 1));
                    $forFix[] = array('id' => $order['id'], 'externalId' => (int) $last[0]['order_id']);
                }
            }

            if (!empty($forFix)) {
                $crm->orderFixExternalIds($forFix);
            }

        } else {
            $this->log->addNotice('['.$this->config->get('store_name').'] RestApi::orderHistory: you need to configure Intarocrm module first.');
        }
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'module/intarocrm')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    protected function getOpercartDeliveryMethods()
    {
        $deliveryMethods = array();
        $files = glob(DIR_APPLICATION . 'controller/shipping/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('shipping/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $deliveryMethods[$extension.'.'.$extension] = strip_tags($this->language->get('heading_title'));
                }
            }
        }

        return $deliveryMethods;
    }

    protected function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');
        return $this->model_localisation_order_status->getOrderStatuses(array());
    }

    protected function getOpercartPaymentTypes()
    {
        $paymentTypes = array();
        $files = glob(DIR_APPLICATION . 'controller/payment/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('payment/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $paymentTypes[$extension] = strip_tags($this->language->get('heading_title'));
                }
            }
        }

        return $paymentTypes;
    }
}
?>