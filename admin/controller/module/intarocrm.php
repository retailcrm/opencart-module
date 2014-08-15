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
        $this->log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/../../../system/logs/module.log', Monolog\Logger::INFO));

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
        $extensions = $this->model_setting_extension->getInstalled('shipping');

        foreach ($extensions as $key => $value) {
            if (!file_exists(DIR_APPLICATION . 'controller/shipping/' . $value . '.php')) {
                $this->model_setting_extension->uninstall('shipping', $value);

                unset($extensions[$key]);
            }
        }

        $deliveryMethods = array();

        $files = glob(DIR_APPLICATION . 'controller/shipping/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('shipping/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $deliveryMethods[] = strip_tags($this->language->get('heading_title'));
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
        $extensions = $this->model_setting_extension->getInstalled('payment');

        foreach ($extensions as $key => $value) {
            if (!file_exists(DIR_APPLICATION . 'controller/payment/' . $value . '.php')) {
                $this->model_setting_extension->uninstall('payment', $value);

                unset($extensions[$key]);
            }
        }

        $paymentTypes = array();

        $files = glob(DIR_APPLICATION . 'controller/payment/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('payment/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $paymentTypes[] = strip_tags($this->language->get('heading_title'));
                }
            }
        }

        return $paymentTypes;
    }
}
?>