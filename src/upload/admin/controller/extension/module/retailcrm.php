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
     * Install method
     *
     * @return void
     */
    public function install()
    {
        $this->load->model('setting/setting');

        $this->model_setting_setting->editSetting(
            \Retailcrm\Retailcrm::MODULE,
            array(
                \Retailcrm\Retailcrm::MODULE . '_status' => 1,
                \Retailcrm\Retailcrm::MODULE . '_country' => array($this->config->get('config_country_id'))
            )
        );

        $this->addCronJobs();
        $this->addEvents();
    }

    /**
     * Uninstall method
     *
     * @return void
     */
    public function uninstall()
    {
        $this->uninstall_collector();
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            \Retailcrm\Retailcrm::MODULE,
            array(\Retailcrm\Retailcrm::MODULE . '_status' => 0)
        );
        $this->model_setting_setting->deleteSetting('retailcrm_history');
        $this->deleteCronJobs();
        $this->deleteEvents();
    }

    /**
     * Install Demon Collector method
     *
     * @return void
     */
    public function install_collector()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->model_setting_extension->install('analytics', 'daemon_collector');
        $this->model_setting_setting->editSetting(
            'analytics_daemon_collector',
            array('analytics_daemon_collector_status' => 1)
        );
    }

    /**
     * Uninstall Demon Collector method
     *
     * @return void
     */
    public function uninstall_collector()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/extension');
        $this->model_setting_setting->editSetting(
            'analytics_daemon_collector',
            array('analytics_daemon_collector_status' => 0)
        );
        $this->model_setting_extension->uninstall('analytics', 'daemon_collector');
    }

    /**
     * Setup page
     *
     * @return void
     */
    public function index()
    {
        $this->load->library('retailcrm/retailcrm');
        $this->load->model('setting/extension');
        $this->load->model('setting/event');
        $this->load->model('localisation/country');
        $this->load->model('setting/setting');
        $this->load->model('extension/retailcrm/references');
        $this->load->language('extension/module/retailcrm');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addStyle('/admin/view/stylesheet/retailcrm.css');

        $history_setting = $this->model_setting_setting->getSetting('retailcrm_history');
        $retailcrm_api_client = $this->retailcrm->getApiClient();
        $opencart_api_client = $this->retailcrm->getOcApiClient($this->registry);

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            if ($this->checkEvents() === false) {
                $this->deleteEvents();
                $this->addEvents();
            }

            $analytics = $this->model_setting_extension->getInstalled('analytics');

            if ($this->request->post[\Retailcrm\Retailcrm::MODULE . '_collector_active'] == 1
                && !in_array('analytics_daemon_collector', $analytics)
            ) {
                $this->install_collector();
            } elseif ($this->request->post[\Retailcrm\Retailcrm::MODULE . '_collector_active'] == 0
                && in_array('analytics_daemon_collector', $analytics)
            ) {
                $this->uninstall_collector();
            }

            if (parse_url($this->request->post[\Retailcrm\Retailcrm::MODULE . '_url'])) {
                $crm_url = parse_url($this->request->post[\Retailcrm\Retailcrm::MODULE . '_url'], PHP_URL_HOST);
                $this->request->post[\Retailcrm\Retailcrm::MODULE . '_url'] = 'https://' . $crm_url;
            }

            if (isset($this->request->post[\Retailcrm\Retailcrm::MODULE . '_custom_field_active'])
                && $this->request->post[\Retailcrm\Retailcrm::MODULE . '_custom_field_active'] == 0
            ) {
                unset($this->request->post[\Retailcrm\Retailcrm::MODULE . '_custom_field']);
            }

            $this->model_setting_setting->editSetting(
                \Retailcrm\Retailcrm::MODULE,
                $this->request->post
            );

            if (!isset($history_setting['retailcrm_history_orders'])
                && !isset($history_setting['retailcrm_history_customers'])
            ) {
                $api = $this->retailcrm->getApiClient(
                    $this->request->post[\Retailcrm\Retailcrm::MODULE . '_url'],
                    $this->request->post[\Retailcrm\Retailcrm::MODULE . '_apikey'],
                    $this->request->post[\Retailcrm\Retailcrm::MODULE . '_apiversion']
                );

                $this->getHistory($api);
            }

            $this->session->data['success'] = $this->language->get('text_success');
            $redirect = $this->url->link(
                'extension/module/retailcrm', 'user_token=' . $this->session->data['user_token'],
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
            'retailcrm_apiversion',
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
            'debug',
            'text_debug'
        );

        foreach ($text_strings as $text) {
            $_data[$text] = $this->language->get($text);
        }

        $_data['retailcrm_errors'] = array();
        $_data['saved_settings'] = $this->model_setting_setting
            ->getSetting(\Retailcrm\Retailcrm::MODULE);

        $url = isset($_data['saved_settings'][\Retailcrm\Retailcrm::MODULE . '_url'])
            ? $_data['saved_settings'][\Retailcrm\Retailcrm::MODULE . '_url']
            : null;
        $key = isset($_data['saved_settings'][\Retailcrm\Retailcrm::MODULE . '_apikey'])
            ? $_data['saved_settings'][\Retailcrm\Retailcrm::MODULE . '_apikey']
            : null;

        if (!empty($url) && !empty($key)) {
            $_data['delivery'] = $this->model_extension_retailcrm_references
                ->getDeliveryTypes($opencart_api_client ,$retailcrm_api_client);
            $_data['statuses'] = $this->model_extension_retailcrm_references
                ->getOrderStatuses($retailcrm_api_client);
            $_data['payments'] = $this->model_extension_retailcrm_references
                ->getPaymentTypes($retailcrm_api_client);
            $_data['customFields'] = $this->model_extension_retailcrm_references
                ->getCustomFields($retailcrm_api_client);
        }

        $config_data = array(
            \Retailcrm\Retailcrm::MODULE . '_status'
        );

        foreach ($config_data as $conf) {
            if (isset($this->request->post[$conf])) {
                $_data[$conf] = $this->request->post[$conf];
            } else {
                $_data[$conf] = $this->config->get($conf);
            }
        }

        if (isset($this->_error['warning'])) {
            $_data['error_warning'] = $this->_error['warning'];
        } else {
            $_data['error_warning'] = '';
        }

        $_data['breadcrumbs'] = array();

        $_data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_home'),
            'href'      => $this->url->link(
                'common/dashboard',
                'user_token' . '=' . $this->session->data['user_token'], 'SSL'
            ),
            'separator' => false
        );

        $_data['breadcrumbs'][] = array(
            'text'      => $this->language->get('text_module'),
            'href'      => $this->url->link(
                'extension/extension',
                'user_token' . '=' . $this->session->data['user_token'], 'SSL'
            ),
            'separator' => ' :: '
        );

        $_data['breadcrumbs'][] = array(
            'text'      => $this->language->get('retailcrm_title'),
            'href'      => $this->url->link(
                'extension/module/retailcrm',
                'user_token' . '=' . $this->session->data['user_token'], 'SSL'
            ),
            'separator' => ' :: '
        );

        $_data['action'] = $this->url->link(
            'extension/module/retailcrm',
            'user_token' . '=' . $this->session->data['user_token'], 'SSL'
        );

        $_data['cancel'] = $this->url->link(
            'marketplace/extension',
            'user_token' . '=' . $this->session->data['user_token'], 'SSL'
        );

        $_data['modules'] = array();

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
        $_data['user_token'] = $this->request->get['user_token'];

        if(file_exists(DIR_SYSTEM . '/cron/export_done')) {
            $_data['export_file'] = false;
        } else {
            $_data['export_file'] = true;
        }

        $collectorFields = array(
            'name' => $this->language->get('field_name'),
            'email' => $this->language->get('field_email'),
            'phone' => $this->language->get('field_phone')
        );

        $_data['collectorFields'] = $collectorFields;
        $_data['api_versions'] = array('v5');
        $_data['default_apiversion'] = 'v5';

        $retailcrmLog = file_exists(DIR_SYSTEM . 'storage/logs/retailcrm.log')
            ? DIR_SYSTEM . 'storage/logs/retailcrm.log'
            : false;
        $ocApiLog = file_exists(DIR_SYSTEM . 'storage/logs/opencartapi.log')
            ? DIR_SYSTEM . 'storage/logs/opencartapi.log'
            : false;

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

        $_data['clear_retailcrm'] = $this->url->link('extension/module/retailcrm/clear_retailcrm', 'user_token' . '=' . $this->session->data['user_token'], true);
        $_data['clear_opencart'] = $this->url->link('extension/module/retailcrm/clear_opencart', 'user_token' . '=' . $this->session->data['user_token'], true);
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
        $this->load->library('retailcrm/retailcrm');

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/history.php')) {
            $this->load->model('extension/retailcrm/custom/history');
            $this->model_extension_retailcrm_custom_history->request($this->retailcrm->getApiClient());
        } else {
            $this->load->model('extension/retailcrm/history');
            $this->model_extension_retailcrm_history->request($this->retailcrm->getApiClient());
        }
    }

    /**
     * ICML generation
     *
     * @return void
     */
    public function icml()
    {
        $this->load->library('retailcrm/retailcrm');

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
    public function customerEdit($route, $customer)
    {
        $this->load->model('localisation/country');
        $this->load->model('localisation/zone');
        $this->load->model('customer/customer');

        $customerId = $customer[0];
        $customer = $customer[1];
        $addresses = $customer['address'];
        unset($customer);

        $customer = $this->model_customer_customer->getCustomer($customerId);

        foreach ($addresses as $address) {
            $country = $this->model_localisation_country->getCountry($address['country_id']);
            $zone = $this->model_localisation_zone->getZone($address['zone_id']);

            $customer['address'] = array(
                'address_1' => $address['address_1'],
                'address_2' => $address['address_2'],
                'city' => $address['city'],
                'postcode' => $address['postcode'],
                'iso_code_2' => $country['iso_code_2'],
                'zone' => $zone['name']
            );
        }

        $this->load->model('extension/retailcrm/customer');
        $this->model_extension_retailcrm_customer->changeInCrm($customer, $this->retailcrm->getApiClient());
    }

    /**
     * Export single order
     *
     * @return void
     */
    public function exportOrder()
    {
        $order_id = isset($this->request->get['order_id']) ? $this->request->get['order_id'] : '';
        $this->load->model('sale/order');

        $order = $this->model_sale_order->getOrder($order_id);
        $order['products'] = $this->model_sale_order->getOrderProducts($order_id);
        $order['totals'] = $this->model_sale_order->getOrderTotals($order_id);

        foreach ($order['products'] as $key => $product) {
            $order['products'][$key]['option'] = $this->model_sale_order->getOrderOptions($product['order_id'], $product['order_product_id']);
        }

        $this->load->library('retailcrm/retailcrm');
        $retailcrm_order = $this->retailcrm->createObject(\Retailcrm\Order::class);
        $retailcrm_order->prepare($order);
        $response = $retailcrm_order->create($this->retailcrm->getApiClient());

        if (!$response->isSuccessful()) {
            if (isset($response['errors'])) {
                $error = implode("\n", $response['errors']);
            } else {
                $error = $response->getErrorMsg();
            }

            $this->response->setOutput(
                json_encode(
                    array(
                        'status_code' => $response->getStatusCode(),
                        'error_msg' => $error
                    )
                )
            );
        } else {
            $this->response->setOutput(
                json_encode(
                    array(
                        'status_code' => $response->getStatusCode()
                    )
                )
            );
        }
    }

    /**
     * Export orders
     *
     * @return void
     */
    public function export()
    {
        $this->load->model('customer/customer');
        $this->load->model('sale/order');
        $this->load->library('retailcrm/retailcrm');

        $retailcrm_api_client = $this->retailcrm->getApiClient();
        $retailcrm_customer = $this->retailcrm->createObject(\Retailcrm\Customer::class);
        $retailcrm_order = $this->retailcrm->createObject(\Retailcrm\Order::class);

        $customers = $this->model_customer_customer->getCustomers();
        $retailcrm_customer->upload($retailcrm_api_client, $customers, 'customers');

        $orders = $this->model_sale_order->getOrders();
        $fullOrders = array();

        foreach ($orders as $order) {
            $fullOrder = $this->model_sale_order->getOrder($order['order_id']);

            $fullOrder['totals'] = $this->model_sale_order->getOrderTotals($order['order_id']);
            $fullOrder['products'] = $this->model_sale_order->getOrderProducts($order['order_id']);

            foreach ($fullOrder['products'] as $key => $product) {
                $fullOrder['products'][$key]['option'] = $this->model_sale_order->getOrderOptions($product['order_id'], $product['order_product_id']);
            }

            $fullOrders[] = $fullOrder;
        }

        $retailcrm_order->upload($retailcrm_api_client, $fullOrders);
    }

    /**
     * Promotional price upload
     *
     * @return void
     */
    public function prices()
    {
        $this->load->model('catalog/product');
        $this->load->library('retailcrm/retailcrm');
        $products = $this->model_catalog_product->getProducts();

        if (file_exists(DIR_APPLICATION . 'model/extension/retailcrm/custom/prices.php')) {
            $this->load->model('extension/retailcrm/custom/prices');
            $this->model_extension_retailcrm_custom_prices->uploadPrices(
                $products,
                $this->retailcrm->getApiClient(),
                $this->retailcrm
            );
        } else {
            $this->load->model('extension/retailcrm/prices');
            $this->model_extension_retailcrm_prices->uploadPrices(
                $products,
                $this->retailcrm->getApiClient(),
                $this->retailcrm
            );
        }
    }

    /**
     * Validate
     *
     * @return bool
     */
    private function validate()
    {
        $versionsMap = array(
            'v5' => '5.0'
        );

        if (!empty($this->request->post[\Retailcrm\Retailcrm::MODULE . '_url']) && !empty($this->request->post[\Retailcrm\Retailcrm::MODULE . '_apikey'])) {
            $apiClient = $this->retailcrm->getApiClient(
                $this->request->post[\Retailcrm\Retailcrm::MODULE . '_url'],
                $this->request->post[\Retailcrm\Retailcrm::MODULE . '_apikey']
            );
        }

        $response = isset($apiClient) ? $apiClient->apiVersions() : false;

        if ($response && $response->isSuccessful()) {
            if (!in_array($versionsMap[$this->request->post[\Retailcrm\Retailcrm::MODULE . '_apiversion']], $response['versions'])) {
                $this->_error['warning'] = $this->language->get('text_error_api');
            }
        } else {
            $this->_error['warning'] = $this->language->get('text_error_save');
        }

        if (!$this->user->hasPermission('modify', 'extension/module/retailcrm')) {
            $this->_error['warning'] = $this->language->get('error_permission');
        }

        if (isset($this->request->post[\Retailcrm\Retailcrm::MODULE . '_collector']['custom']) &&
            $this->request->post[\Retailcrm\Retailcrm::MODULE . '_collector']['custom_form'] == 1) {
            $customField = $this->request->post[\Retailcrm\Retailcrm::MODULE . '_collector']['custom'];

            if (empty($customField['name']) && empty($customField['email']) && empty($customField['phone'])) {
                $this->_error['fields'] = $this->language->get('text_error_collector_fields');
            }
        }

        if (!$this->_error) {
            return true;
        } else {
            return false;
        }
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

        $this->response->redirect(
            $this->url->link(
                'extension/module/retailcrm',
                'user_token' . '=' . $this->session->data['user_token'],
                true
            )
        );
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

        $this->response->redirect(
            $this->url->link(
                'extension/module/retailcrm',
                'user_token' . '=' . $this->session->data['user_token'],
                true
            )
        );
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
        $this->model_setting_event
            ->addEvent(
                \Retailcrm\Retailcrm::MODULE,
                'catalog/model/checkout/order/addOrder/after',
                'extension/module/retailcrm/orderCreate'
            );

        $this->model_setting_event
            ->addEvent(
                \Retailcrm\Retailcrm::MODULE,
                'catalog/model/checkout/order/editOrder/after',
                'extension/module/retailcrm/orderEdit'
            );

        $this->model_setting_event
            ->addEvent(
                \Retailcrm\Retailcrm::MODULE,
                'catalog/model/account/customer/addCustomer/after',
                'extension/module/retailcrm/customerCreate'
            );

        $this->model_setting_event
            ->addEvent(
                \Retailcrm\Retailcrm::MODULE,
                'catalog/model/account/customer/editCustomer/after',
                'extension/module/retailcrm/customerEdit'
            );

        $this->model_setting_event
            ->addEvent(
                \Retailcrm\Retailcrm::MODULE,
                'catalog/model/account/address/editAddress/after',
                'extension/module/retailcrm/customerEdit'
            );

        $this->model_setting_event
            ->addEvent(
                \Retailcrm\Retailcrm::MODULE,
                'admin/model/customer/customer/editCustomer/after',
                'extension/module/retailcrm/customerEdit'
            );
    }

    /**
     * Check events in db
     *
     * @return boolean
     */
    private function checkEvents()
    {
        $events = $this->model_setting_event->getEvent(
            \Retailcrm\Retailcrm::MODULE,
            'catalog/model/checkout/order/addOrder/after',
            'extension/module/retailcrm/orderCreate'
        );

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
        $this->model_setting_event->deleteEventByCode(\Retailcrm\Retailcrm::MODULE);
    }

    /**
     * Getting history for first setting save
     *
     * @param $apiClient
     *
     * @return void
     */
    private function getHistory($apiClient)
    {
        $ordersHistoryBegin = $apiClient->ordersHistory();

        if ($ordersHistoryBegin->isSuccessful() && !empty($ordersHistoryBegin['history'])) {
            $ordersHistoryEnd = $apiClient->ordersHistory(array(),
                $ordersHistoryBegin['pagination']['totalPageCount']
            );

            if ($ordersHistoryEnd->isSuccessful()) {
                $ordersHistoryArr = $ordersHistoryEnd['history'];
                $lastChangeOrders = end($ordersHistoryArr);
                $sinceIdOrders = $lastChangeOrders['id'];
                $generatedAt = $ordersHistoryEnd['generatedAt'];
            }
        }

        $customersHistoryBegin = $apiClient->customersHistory();

        if ($customersHistoryBegin->isSuccessful() && !empty($customersHistoryBegin['history'])) {
            $customersHistoryEnd = $apiClient->customersHistory(
                array(),
                $customersHistoryBegin['pagination']['totalPageCount']
            );

            if ($customersHistoryEnd->isSuccessful()) {
                $customersHistoryArr = $customersHistoryEnd['history'];
                $lastChangeCustomers = end($customersHistoryArr);
                $sinceIdCustomers = $lastChangeCustomers['id'];
            }
        }

        $this->model_setting_setting->editSetting(
            'retailcrm_history',
            array(
                'retailcrm_history_orders' => isset($sinceIdOrders) ? $sinceIdOrders : 1,
                'retailcrm_history_customers' => isset($sinceIdCustomers) ? $sinceIdCustomers : 1,
                'retailcrm_history_datetime' => isset($generatedAt) ? $generatedAt : date('Y-m-d H:i:s')
            )
        );
    }

    private function addCronJobs() {
        $this->load->model('setting/cron');
        $this->model_setting_cron->addCron('icml', 'day', 'cron/icml', 1);
        $this->model_setting_cron->addCron('prices', 'day', 'cron/icml', 0);
    }

    private function deleteCronJobs() {
        $this->load->model('setting/cron');
        $this->model_setting_cron->deleteCronByCode('icml');
        $this->model_setting_cron->deleteCronByCode('prices');
    }
}
