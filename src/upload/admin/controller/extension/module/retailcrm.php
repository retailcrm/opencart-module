<?php

use retailcrm\Retailcrm;

class ControllerExtensionModuleRetailcrm extends Controller
{
    private $_error = [];
    protected $log, $statuses, $payments, $deliveryTypes, $retailcrmApiClient, $moduleTitle, $tokenTitle;
    public $children, $template;

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->library('retailcrm/retailcrm');
        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->tokenTitle = $this->retailcrm->getTokenTitle();
    }

    /**
     * Install method
     *
     * @return void
     */
    public function install()
    {
        $this->load->model('setting/setting');

        $this->model_setting_setting->editSetting(
            $this->moduleTitle,
            [
                $this->moduleTitle . '_status' => 1,
                $this->moduleTitle . '_country' => [$this->config->get('config_country_id')]
            ]
        );

        $this->addEvents();
    }

    /**
     * Uninstall method
     *
     * @return void
     */
    public function uninstall()
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm_setting');

        if (!empty($settings)) {
            $clientId = $settings['retailcrm_setting_client_id'];

            $this->integrationModule(
                $this->retailcrm->getApiClient(
                    $settings['retailcrm_setting_url'],
                    $settings['retailcrm_setting_key']
                ),
                $clientId,
                false
            );

            $this->uninstall_collector();
            $this->model_setting_setting->editSetting(
                $this->moduleTitle,
                [$this->moduleTitle . '_status' => 0]
            );
        }

        $this->model_setting_setting->deleteSetting('retailcrm_history');
        $this->model_setting_setting->deleteSetting('retailcrm_setting');
        $this->deleteEvents();
    }

    /**
     * Install Demon Collector method
     *
     * @return void
     */
    public function install_collector()
    {
        $collector = $this->getCollectorTitle();
        $this->loadModels();
        $this->load->model('setting/setting');
        $this->{'model_' . $this->modelExtension}->install('analytics', 'daemon_collector');
        $this->model_setting_setting->editSetting($collector, [$collector . '_status' => 1]);
    }

    /**
     * Uninstall Demon Collector method
     *
     * @return void
     */
    public function uninstall_collector()
    {
        $collector = $this->getCollectorTitle();
        $this->loadModels();
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting($collector, [$collector . '_status' => 0]);
        $this->{'model_' . $this->modelExtension}->uninstall('analytics', 'daemon_collector');
    }

    /**
     * Install OnlineConsultant method
     *
     * @return void
     */
    public function install_consultant()
    {
        $consultant = $this->getConsultantTitle();
        $this->loadModels();
        $this->load->model('setting/setting');
        $this->{'model_' . $this->modelExtension}->install('analytics', 'online_consultant');
        $this->model_setting_setting->editSetting($consultant, [$consultant . '_status' => 1]);
    }

    /**
     * Uninstall OnlineConsultant method
     *
     * @return void
     */
    public function uninstall_consultant()
    {
        $consultant = $this->getConsultantTitle();
        $this->loadModels();
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting($consultant, [$consultant . '_status' => 0]);
        $this->{'model_' . $this->modelExtension}->uninstall('analytics', 'online_consultant');
    }

    /**
     * Setup page
     *
     * @return void
     */
    public function index()
    {
        $this->loadModels();
        $this->load->model('localisation/country');
        $this->load->model('setting/setting');
        $this->load->model('extension/retailcrm/references');
        $this->load->model('localisation/currency');
        $this->load->model('customer/customer_group');
        $this->load->model('localisation/length_class');
        $this->load->language('extension/module/retailcrm');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('/admin/view/stylesheet/retailcrm.css');

        $collector = $this->getCollectorTitle();
        $consultant = $this->getConsultantTitle();
        $history_setting = $this->model_setting_setting->getSetting('retailcrm_history');

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            if ($this->checkEvents() === false) {
                $this->deleteEvents();
                $this->addEvents();
            }

            $analytics = $this->{'model_' . $this->modelExtension}->getInstalled('analytics');

            if ($this->request->post[$this->moduleTitle . '_collector_active'] == 1
                && !in_array($collector, $analytics)
            ) {
                $this->install_collector();
            } elseif ($this->request->post[$this->moduleTitle . '_collector_active'] == 0
                && in_array($collector, $analytics)
            ) {
                $this->uninstall_collector();
            }

            if ($this->request->post[$this->moduleTitle . '_online_consultant_active'] == 1) {
                $this->install_consultant();
            } elseif ($this->request->post[$this->moduleTitle . '_online_consultant_active'] == 0) {
                $this->uninstall_consultant();
            }

            if (parse_url($this->request->post[$this->moduleTitle . '_url'])) {
                $crm_url = parse_url($this->request->post[$this->moduleTitle . '_url'], PHP_URL_HOST);
                $this->request->post[$this->moduleTitle . '_url'] = 'https://' . $crm_url;
            }

            if (isset($this->request->post[$this->moduleTitle . '_custom_field_active'])
                && $this->request->post[$this->moduleTitle . '_custom_field_active'] == 0
            ) {
                unset($this->request->post[$this->moduleTitle . '_custom_field']);
            }

            $this->model_setting_setting->editSetting(
                $this->moduleTitle,
                $this->request->post
            );

            if (
                !isset($history_setting['retailcrm_history_orders'])
                && !isset($history_setting['retailcrm_history_customers'])
            ) {
                $api = $this->retailcrm->getApiClient(
                    $this->request->post[$this->moduleTitle . '_url'],
                    $this->request->post[$this->moduleTitle . '_apikey']
                );

                $this->model_setting_setting->editSetting(
                    'retailcrm_history',
                    [
                        'retailcrm_history_orders' => $this->getHistorySinceId($api, 'ordersHistory'),
                        'retailcrm_history_customers' => $this->getHistorySinceId($api, 'customersHistory'),
                    ]
                );
            }

            $retailcrm_setting = $this->model_setting_setting->getSetting('retailcrm_setting');

            if (!$retailcrm_setting) {
                $clientId = uniqid();
                $api = $this->retailcrm->getApiClient(
                    $this->request->post[$this->moduleTitle . '_url'],
                    $this->request->post[$this->moduleTitle . '_apikey']
                );

                $result = $this->integrationModule(
                    $api,
                    $clientId
                );

                if ($result === true) {
                    $this->model_setting_setting->editSetting(
                        'retailcrm_setting',
                        [
                            'retailcrm_setting_active_in_crm' => true,
                            'retailcrm_setting_client_id' => $clientId,
                            'retailcrm_setting_url' => $this->request->post[$this->moduleTitle . '_url'],
                            'retailcrm_setting_key' => $this->request->post[$this->moduleTitle . '_apikey']
                        ]
                    );
                }
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $redirect = $this->url->link(
                'extension/module/retailcrm', $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle],
                'SSL'
            );

            $this->response->redirect($redirect);
        }

        $text_strings = [
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
            'retailcrm_countries_settings',
            'text_success_export',
            'text_success_export_order',
            'text_button_export',
            'text_button_export_order',
            'text_button_catalog',
            'text_success_catalog',
            'retailcrm_upload_order',
            'text_error_order',
            'text_error_order_id',
            'daemon_collector',
            'general_tab_text',
            'references_tab_text',
            'collector_tab_text',
            'consultant_tab_text',
            'logs_tab_text',
            'text_yes',
            'text_no',
            'collector_site_key',
            'text_collector_activity',
            'text_collector_form_capture',
            'text_collector_period',
            'text_label_promo',
            'text_label_send',
            'collector_custom_text',
            'text_require',
            'custom_fields_tab_text',
            'text_error_custom_field',
            'text_error_cf_opencart',
            'text_error_cf_retailcrm',
            'retailcrm_dict_custom_fields',
            'text_payment',
            'text_shipping',
            'retailcrm_dict_default',
            'text_custom_field_activity',
            'text_orders_custom_fields',
            'text_customers_custom_fields',
            'text_confirm_log',
            'text_error_delivery',
            'retailcrm_missing_status',
            'special_price_settings',
            'special_price',
            'order_number',
            'text_order_number',
            'summ_around',
            'text_summ_around',
            'stock_upload',
            'text_stock_upload',
            'store_select',
            'icml_settings',
            'icml_service_enabled_label',
            'icml_service_description',
            'text_currency_label',
            'status_changes',
            'text_status_changes',
            'text_lenght',
            'text_lenght_label',
            'corporate_enabled_label',
            'entry_code',
            'entry_status',
            'text_retailcrm_discount',
            'text_retailcrm_label_discount',
            'default_retailcrm_label_discount',
            'sum_payment',
            'text_sum_payment',
        ];

        $_data = &$data;

        $_data['module_version'] = Retailcrm::VERSION_MODULE;

        foreach ($text_strings as $text) {
            $_data[$text] = $this->language->get($text);
        }

        $_data['currencies'] = $this->model_localisation_currency->getCurrencies(0);
        $_data['retailcrm_errors'] = [];
        $_data['saved_settings'] = $this->model_setting_setting
            ->getSetting($this->moduleTitle);

        $url = $_data['saved_settings'][$this->moduleTitle . '_url'] ?? null;
        $key = $_data['saved_settings'][$this->moduleTitle . '_apikey'] ?? null;

        if (!empty($url) && !empty($key)) {
            $this->validate($url, $key);

            $site = $this->model_extension_retailcrm_references->getApiSite();
            $_data['delivery'] = $this->getAvailableTypes(
                $site,
                $this->model_extension_retailcrm_references->getDeliveryTypes()
            );
            $_data['payments'] = $this->getAvailableTypes(
                $site,
                $this->model_extension_retailcrm_references->getPaymentTypes()
            );
            $_data['statuses'] = $this->model_extension_retailcrm_references
                ->getOrderStatuses();
            $_data['customFields'] = $this->model_extension_retailcrm_references
                ->getCustomFields();

            $_data['lenghts'] = $this->model_localisation_length_class->getLengthClasses();
            $_data['priceTypes'] = $this->model_extension_retailcrm_references
                ->getPriceTypes();
            $_data['customerGroups'] = $this->model_customer_customer_group->getCustomerGroups();
            $_data['crmStocks'] = $this->model_extension_retailcrm_references->getApiStores();
        }

        $config_data = [$this->moduleTitle . '_status'];

        foreach ($config_data as $conf) {
            if (isset($this->request->post[$conf])) {
                $_data[$conf] = $this->request->post[$conf];
            } else {
                $_data[$conf] = $this->config->get($conf);
            }
        }

        $_data['error_warning'] = $this->_error['warning'] ?? '';
        $_data['breadcrumbs'] = [];

        $_data['breadcrumbs'][] = [
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link(
                'common/dashboard',
                $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], 'SSL'
            ),
            'separator' => false
        ];

        $_data['breadcrumbs'][] = [
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link(
                'extension/extension',
                $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], 'SSL'
            ),
            'separator' => ' :: '
        ];

        $_data['breadcrumbs'][] = [
            'text'      => $this->language->get('retailcrm_title'),
            'href'      => $this->url->link(
                'extension/module/retailcrm',
                $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], 'SSL'
            ),
            'separator' => ' :: '
        ];

        $_data['action'] = $this->url->link(
            'extension/module/retailcrm',
            $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], 'SSL'
        );

        $_data['cancel'] = $this->url->link(
            version_compare(VERSION, '3.0', '<') ? 'extension/extension' : 'marketplace/extension',
            $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], 'SSL'
        );

        $_data['modules'] = [];

        if (isset($this->request->post['retailcrm_module'])) {
            $_data['modules'] = $this->request->post['retailcrm_module'];
        } elseif ($this->config->get('retailcrm_module')) {
            $_data['modules'] = $this->config->get('retailcrm_module');
        }

        $this->load->model('design/layout');
        $_data['layouts'] = $this->model_design_layout->getLayouts();

        $_data['header'] = $this->load->controller('common/header');
        $_data['column_left'] = $this->load->controller('common/column_left');
        $_data['footer'] = $this->load->controller('common/footer');
        $_data['countries'] = $this->model_localisation_country->getCountries();
        $_data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        $_data[$this->tokenTitle] = $this->request->get[$this->tokenTitle];

        if(file_exists(DIR_SYSTEM . '/cron/export_done')) {
            $_data['export_file'] = false;
        } else {
            $_data['export_file'] = true;
        }

        $collectorFields = [
            'name' => $this->language->get('field_name'),
            'email' => $this->language->get('field_email'),
            'phone' => $this->language->get('field_phone')
        ];

        $_data['collectorFields'] = $collectorFields;

        $retailcrmLog = file_exists(DIR_SYSTEM . 'storage/logs/retailcrm.log') ? DIR_SYSTEM . 'storage/logs/retailcrm.log' : false;
        $ocApiLog = file_exists(DIR_SYSTEM . 'storage/logs/opencartapi.log') ? DIR_SYSTEM . 'storage/logs/opencartapi.log' : false;

        if ($this->checkLogFile($retailcrmLog) !== false) {
            $_data['logs']['retailcrm_log'] = $this->checkLogFile($retailcrmLog);
        } else {
            $_data['logs']['retailcrm_error'] = $this->language->get('text_error_log');
        }

        if ($this->checkLogFile($ocApiLog) !== false) {
            $_data['logs']['oc_api_log'] = $this->checkLogFile($ocApiLog);
        } else {
            $_data['logs']['oc_error'] = $this->language->get('text_error_log');
        }

        $_data['clear_retailcrm'] = $this->url->link('extension/module/retailcrm/clear_retailcrm', $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], true);
        $_data['clear_opencart'] = $this->url->link('extension/module/retailcrm/clear_opencart', $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], true);
        $_data['button_clear'] = $this->language->get('button_clear');

        $this->response->setOutput(
            $this->load->view('extension/module/retailcrm', $_data)
        );
    }

    /**
     * History method
     *
     * @return void
     */
    public function history()
    {
        $this->load->model('setting/setting');

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/history/v4_5.php')) {
            $this->load->model('extension/retailcrm/custom/history/v4_5');
            $this->model_extension_retailcrm_custom_history_v4_5->request($this->retailcrm->getApiClient());
        } else {
            $this->load->model('extension/retailcrm/history');
            $this->model_extension_retailcrm_history->request($this->retailcrm->getApiClient());
        }
    }

    /**
     * Inventories upload
     *
     * @return void
     */
    public function inventories()
    {
        $this->load->model('extension/retailcrm/inventories');
        $this->model_extension_retailcrm_inventories->uploadInventories();
    }

    /**
     * ICML generation
     *
     * @return void
     */
    public function icml()
    {
        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/icml.php')) {
            $this->load->model('extension/retailcrm/custom/icml');
            $this->model_extension_retailcrm_custom_icml->generateICML();
        } else {
            $this->load->model('extension/retailcrm/icml');
            $this->model_extension_retailcrm_icml->generateICML();
        }
    }

    /**
     * Update customer on event
     *
     * @param string $route
     * @param int $customer customer identificator
     *
     * @return void
     */
    public function customer_edit($route, $customer)
    {
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->model('customer/customer');

        $customerId = $customer[0];
        $customer = $customer[1];
        unset($customer);

        $customer = $this->model_customer_customer->getCustomer($customerId);
        $address = $this->model_customer_customer->getAddress($customer['address_id']);

        $customer_manager = $this->retailcrm->getCustomerManager();
        $customer_manager->editCustomer($customer, $address);
    }

    /**
     * Export single order
     *
     * @return void
     */
    public function exportOrder() {
        $order_id = isset($this->request->get['order_id']) ? $this->request->get['order_id'] : '';
        $data = $this->model_sale_order->getOrder($order_id);
        $products = $this->model_sale_order->getOrderProducts($order_id);
        $totals = $this->model_sale_order->getOrderTotals($order_id);

        foreach ($products as $key => $product) {
            $products[$key]['option'] = $this->model_sale_order->getOrderOptions($product['order_id'], $product['order_product_id']);
        }

        if (!isset($data['fromApi'])) {
            $status = $this->model_setting_setting->getSetting($this->moduleTitle);
            $data['order_status'] = $status[$this->moduleTitle . '_status'][$data['order_status_id']];

            $order_manager = $this->retailcrm->getOrderManager();
            $response = $order_manager->createOrder($data, $products, $totals);

            if ($response) {
                if (!$response->isSuccessful()) {
                    if (isset($response['errors'])) {
                        $error = implode("\n", $response['errors']);
                    } else {
                        $error = $response->getErrorMsg();
                    }

                    $this->response->setOutput(
                        json_encode(
                            ['status_code' => $response->getStatusCode(), 'error_msg' => $error],
                            JSON_THROW_ON_ERROR
                        )
                    );
                } else {
                    $this->response->setOutput(json_encode(['status_code' => $response->getStatusCode()]));
                }
            }
        }
    }

    /**
     * Export orders
     *
     * @return void
     */
    public function export()
    {
        $this->load->model('extension/retailcrm/customer');
        $this->load->model('extension/retailcrm/order');

        $customers = $this->model_customer_customer->getCustomers();
        $this->model_extension_retailcrm_customer->uploadToCrm($customers, $this->retailcrm->getApiClient());
        $orders = $this->model_sale_order->getOrders();

        $fullOrders = [];

        foreach ($orders as $order) {
            $fullOrder = $this->model_sale_order->getOrder($order['order_id']);

            $fullOrder['totals'] = $this->model_sale_order->getOrderTotals($order['order_id']);
            $fullOrder['products'] = $this->model_sale_order->getOrderProducts($order['order_id']);

            foreach($fullOrder['products'] as $key => $product) {
                $fullOrder['products'][$key]['option'] = $this->model_sale_order->getOrderOptions($product['order_id'], $product['order_product_id']);
            }

            $fullOrders[] = $fullOrder;
        }

        $this->model_extension_retailcrm_order->uploadToCrm($fullOrders, $this->retailcrm->getApiClient());
        fopen(DIR_SYSTEM . '/cron/export_done', "x");
    }

    /**
     * Promotional price upload
     *
     * @return void
     */
    public function prices()
    {
        $this->load->model('catalog/product');
        $products = $this->model_catalog_product->getProducts();

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/prices.php')) {
            $this->load->model('extension/retailcrm/custom/prices');
            $this->model_extension_retailcrm_custom_prices->uploadPrices($products, $this->retailcrm->getApiClient());
        } else {
            $this->load->model('extension/retailcrm/prices');
            $this->model_extension_retailcrm_prices->uploadPrices($products, $this->retailcrm->getApiClient());
        }
    }

    /**
     * Validate
     *
     * @return bool
     */
    private function validate($apiUrl = null, $apiKey = null)
    {
        $warningMessage = '';

        $apiUrl = $this->request->post[$this->moduleTitle . '_url'] ?? $apiUrl;
        $apiKey = $this->request->post[$this->moduleTitle . '_apikey'] ?? $apiKey;

        try {
            if ( !empty($apiUrl) && !empty($apiKey)) {
                $apiClient = $this->retailcrm->getApiClient($apiUrl, $apiKey);
                $response = $apiClient->sitesList();

                if (empty($response['sites']) || !$response->isSuccessful()) {
                    $warningMessage = 'text_error_api_key';
                } elseif (count($response['sites']) > 1)  {
                    $warningMessage = 'text_error_api_key_site';
                } else {
                    $site = current($response['sites']);

                    if ($this->config->get('config_currency') !== $site['currency']) {
                        $warningMessage = 'text_error_api_key_currency';
                    }
                }

                if (!$this->user->hasPermission('modify', 'extension/module/retailcrm')) {
                    $this->_error['warning'] = $this->language->get('error_permission');
                }

                if (isset($this->request->post[$this->moduleTitle . '_collector']['custom']) &&
                    $this->request->post[$this->moduleTitle . '_collector']['custom_form'] == 1) {
                    $customField = $this->request->post[$this->moduleTitle . '_collector']['custom'];

                    if (empty($customField['name']) && empty($customField['email']) && empty($customField['phone'])) {
                        $this->_error['fields'] = $this->language->get('text_error_collector_fields');
                    }
                }
            } else {
                $warningMessage = 'text_error_api_empty';
            }
        } catch (Throwable $exception) {
            $warningMessage = sprintf(
                'An error has occurred! In file: %s, on line: %s. Error message: %s',
                $exception->getFile(), $exception->getLine(), $exception->getMessage()
            );
        }

        if ('' !== $warningMessage) {
            $this->_error['warning'] = $this->language->get($warningMessage);
        }

        return empty($this->_error);
    }

    /**
     * Clear retailcrm log file
     *
     * @return void
     */
    public function clear_retailcrm()
    {
        if ($this->user->hasPermission('modify', 'extension/module/retailcrm')) {
            $file = DIR_LOGS . 'retailcrm.log';

            $handle = fopen($file, 'w+');

            fclose($handle);
        }

        $this->response->redirect($this->url->link('extension/module/retailcrm', $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], true));
    }

    /**
     * Clear opencart API log file
     *
     * @return void
     */
    public function clear_opencart()
    {
        if ($this->user->hasPermission('modify', 'extension/module/retailcrm')) {
            $file = DIR_LOGS . 'opencartapi.log';

            $handle = fopen($file, 'w+');

            fclose($handle);
        }

        $this->response->redirect($this->url->link('extension/module/retailcrm', $this->tokenTitle . '=' . $this->session->data[$this->tokenTitle], true));
    }

    /**
     * Method for load models
     *
     * @return void
     */
    private function loadModels()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $this->load->model('extension/event');
            $this->load->model('extension/extension');
            // $this->load->model('extension/module');

            $this->modelEvent = 'extension_event';
            $this->modelExtension = 'extension_extension';
            // $this->modelModule = 'extension_module';
        } else {
            $this->load->model('setting/event');
            $this->load->model('setting/extension');
            // $this->load->model('setting/module');

            $this->modelEvent = 'setting_event';
            $this->modelExtension = 'setting_extension';
            // $this->modelModule = 'setting_module';
        }
    }

    /**
     * Get collector module name
     *
     * @return string
     */
    private function getCollectorTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'daemon_collector';
        } else {
            $title = 'analytics_daemon_collector';
        }

        return $title;
    }

    /**
     * Get consultant module name
     *
     * @return string
     */
    private function getConsultantTitle()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'online_consultant';
        } else {
            $title = 'analytics_online_consultant';
        }

        return $title;
    }

    /**
     * Check file size
     *
     * @return string
     */
    private function checkLogFile($file)
    {
        $logs = '';

        if ($file === false) {
            return $logs;
        }

        if (filesize($file) < 2097152) {
            $logs .= file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
        } else {
            return false;
        }

        return $logs;
    }

    /**
     * Add events to db
     *
     * @return void
     */
    private function addEvents()
    {
        $this->loadModels();

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'catalog/model/checkout/order/addOrder/after',
                'extension/module/retailcrm/order_create'
            );

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'catalog/model/checkout/order/addOrderHistory/after',
                'extension/module/retailcrm/order_edit'
            );

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'catalog/model/account/customer/addCustomer/after',
                'extension/module/retailcrm/customer_create'
            );

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'catalog/model/account/customer/editCustomer/after',
                'extension/module/retailcrm/customer_edit'
            );

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'catalog/model/account/customer/editNewsletter/after',
                'extension/module/retailcrm/customer_edit_newsletter'
            );

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'catalog/model/account/address/editAddress/after',
                'extension/module/retailcrm/customer_edit'
            );

        $this->{'model_' . $this->modelEvent}
            ->addEvent(
                $this->moduleTitle,
                'admin/model/customer/customer/editCustomer/after',
                'extension/module/retailcrm/customer_edit'
            );
    }

    /**
     * Check events in db
     *
     * @return boolean
     */
    private function checkEvents()
    {
        if (version_compare(VERSION, '3.0', '<')) {
            $events = $this->{'model_' . $this->modelEvent}->getEvent(
                $this->moduleTitle,
                'catalog/model/checkout/order/addOrder/after',
                'extension/module/retailcrm/order_create'
            );
        } else {
            $this->load->model('extension/retailcrm/event');
            $events = $this->model_extension_retailcrm_event->getEventByCode($this->moduleTitle);
        }

        if (!empty($events)) {
            return true;
        }

        return false;
    }

    /**
     * Delete events from db
     *
     * @return void
     */
    private function deleteEvents()
    {
        $this->loadModels();

        if (version_compare(VERSION, '3.0', '<')) {
            $this->{'model_' . $this->modelEvent}->deleteEvent($this->moduleTitle);
        } else {
            $this->{'model_' . $this->modelEvent}->deleteEventByCode($this->moduleTitle);
        }
    }

    /**
     * Activate/deactivate module in marketplace RetailCRM
     *
     * @param \RetailcrmProxy $apiClient
     * @param string $clientId
     * @param boolean $active
     *
     * @return boolean
     */
    private function integrationModule($apiClient, $clientId, $active = true)
    {
        $scheme = isset($this->request->server['HTTPS']) ? 'https://' : 'http://';
        $logo = 'https://s3.eu-central-1.amazonaws.com/retailcrm-billing/images/5af48736c6a0c-opencart-seeklogo.com.svg';
        $integrationCode = 'opencart';
        $name = 'Opencart';
        $accountUrl = $scheme . $this->request->server['HTTP_HOST'] . '/admin';

        $configuration = [
            'clientId' => $clientId,
            'code' => $integrationCode . '-' . $clientId,
            'integrationCode' => $integrationCode,
            'active' => $active,
            'name' => $name,
            'logo' => $logo,
            'accountUrl' => $accountUrl,
        ];

        $response = $apiClient->integrationModulesEdit($configuration);

        if (!$response) {
            return false;
        }

        if ($response->isSuccessful()) {
            return true;
        }

        return false;
    }

    private function getHistorySinceId($api, $method)
    {
        $lastSinceId = 0;
        $startDate = new DateTime('-1 days');
        $historyResponse = $api->$method(['startDate' => $startDate->format('Y-m-d H:i:s')]);

        if (
            !$historyResponse instanceof ApiResponse
            || !$historyResponse->isSuccessful()
            || empty($historyResponse['history'])
            || empty($historyResponse['pagination'])
        ) {
            return $lastSinceId;
        }

        $startPage = $historyResponse['pagination']['currentPage'];
        $lastPage = $historyResponse['pagination']['totalPageCount'];

        for ($startPage; $startPage <= $lastPage; ++$startPage) {
            if ($historyResponse instanceof ApiResponse && !empty($historyResponse['history'])) {
                $history = $historyResponse['history'];
                $lastSinceId = end($history)['id'];
                $historyResponse = $api->$method(['sinceId' => $lastSinceId]);
            }
        }

        return $lastSinceId;
    }

    private function getAvailableTypes($availableSite, $types)
    {
        $result['opencart'] = $types['opencart'];
        $result['retailcrm'] = [];

        if (empty($availableSite)) {
            return $result;
        }

        foreach ($types['retailcrm'] as $codeKey => $type) {
            if ($type['active'] !== true) {
                continue;
            }

            if (!empty($type['sites']) && !in_array($availableSite['code'], $type['sites'])) {
                continue;
            }

            $result['retailcrm'][$codeKey] = $type;
        }

        return $result;
    }
}
