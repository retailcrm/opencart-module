<?php

require_once __DIR__ . '/../../../system/library/intarocrm/vendor/autoload.php';

class ControllerModuleIntarocrm extends Controller {
    private $error = array();
    protected $dd, $eCategories, $eOffers;

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
        $this->load->model('sale/customer');



        $settings = $this->model_setting_setting->getSetting('intarocrm');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        if (isset($settings['intarocrm_url']) && $settings['intarocrm_url'] != '' && isset($settings['intarocrm_apikey']) && $settings['intarocrm_apikey'] != '') {
            include_once __DIR__ . '/../../../system/library/intarocrm/apihelper.php';
            $crm = new ApiHelper($settings);
            $orders = $crm->orderHistory();
            $ordersIdsFix = array();
            $customersIdsFix = array();
            $subtotalSettings = $this->model_setting_setting->getSetting('sub_total');
            $totalSettings = $this->model_setting_setting->getSetting('total');
            $shippingSettings = $this->model_setting_setting->getSetting('shipping');

            foreach ($orders as $order)
            {
                if (!isset($order['deleted']) || !$order['deleted']) {

                    $data = array();

                    $customer_id = (isset($order['customer']['externalId']))
                        ? $order['customer']['externalId']
                        : ''
                        ;

                    if ($customer_id == '') {
                        $cData = array(
                            'customer_group_id' => '1',
                            'firstname' => $order['customer']['firstName'],
                            'lastname' => (isset($order['customer']['lastName'])) ? $order['customer']['lastName'] : ' ',
                            'email' => $order['customer']['email'],
                            'telephone' => (isset($order['customer']['phones'][0]['number'])) ? $order['customer']['phones'][0]['number'] : ' ',
                            'newsletter' => 0,
                            'password' => 'tmppass',
                            'status' => 1,
                            'address' => array(
                                'firstname' => $order['customer']['firstName'],
                                'lastname' => (isset($order['customer']['lastName'])) ? $order['customer']['lastName'] : ' ',
                                'address_1' => $order['customer']['address']['text'],
                                'city' => $order['customer']['address']['city'],
                                'postcode' => $order['customer']['address']['index']
                            ),
                        );

                        $this->model_sale_customer->addCustomer($cData);

                        if (isset($order['customer']['email']) && $order['customer']['email'] != '') {
                            $tryToFind = $this->model_sale_customer->getCustomerByEmail($order['customer']['email']);
                            $customer_id = $tryToFind['customer_id'];
                        } else {
                            $last = $this->model_sale_customer->getCustomers($data = array('order' => 'DESC', 'limit' => 1));
                            $customer_id = $last[0]['customer_id'];
                        }

                        $customersIdsFix[] = array('id' => $order['customer']['id'], 'externalId' => (int) $customer_id);
                    }

                    $delivery = array_flip($settings['intarocrm_delivery']);
                    $payment = array_flip($settings['intarocrm_payment']);
                    $status = array_flip($settings['intarocrm_status']);

                    $ocPayment = $this->getOpercartPaymentTypes();
                    $ocDelivery = $this->getOpercartDeliveryMethods();

                    $data['store_id'] = ($this->config->get('config_store_id') == null) ? 0 : $this->config->get('config_store_id');
                    $data['customer'] = $order['customer']['firstName'];
                    $data['customer_id'] = $customer_id;
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

                    foreach($order['items'] as $item) {
                        $data['order_product'][] = array(
                            'product_id' => $item['offer']['externalId'],
                            'name' => $item['offer']['name'],
                            'quantity' => $item['quantity'],
                            'price' => $item['initialPrice'],
                            'total' => $item['initialPrice'] * $item['quantity'],
                        );
                    }

                    $deliveryCost = isset($order['delivery']['cost']) ? $order['delivery']['cost'] : 0;

                    $data['order_total'] = array(
                        array(
                            'order_total_id' => '',
                            'code' => 'sub_total',
                            'value' => $order['summ'],
                            'sort_order' => $subtotalSettings['sub_total_sort_order']
                        ),
                        array(
                            'order_total_id' => '',
                            'code' => 'shipping',
                            'value' => $deliveryCost,
                            'sort_order' => $shippingSettings['shipping_sort_order']
                        ),
                        array(
                            'order_total_id' => '',
                            'code' => 'total',
                            'value' => isset($order['totalSumm']) ? $order['totalSumm'] : $order['summ'] + $deliveryCost,
                            'sort_order' => $totalSettings['total_sort_order']
                        )
                    );

                    if (isset($order['externalId'])) {
                        /*
                         * opercart developers believe that to remove all
                         * products from the order during the editing is a good idea...
                         *
                         * so we have to get all the goods before orders are breaks
                         *
                         */
                        $items = $crm->getOrderItems($order['externalId']);
                        $data['order_product'] = array();

                        foreach($items as $item) {
                            $data['order_product'][] = array(
                                'product_id' => $item['offer']['externalId'],
                                'name' => $item['offer']['name'],
                                'quantity' => $item['quantity'],
                                'price' => $item['initialPrice'],
                                'total' => $item['initialPrice'] * $item['quantity'],
                            );
                        }

                        $this->model_sale_order->editOrder($order['externalId'], $data);
                    } else {
                        $this->model_sale_order->addOrder($data);
                        $last = $this->model_sale_order->getOrders($data = array('order' => 'DESC', 'limit' => 1));
                        $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $last[0]['order_id']);
                    }
                }
            }

            if (!empty($customersIdsFix)) {
                $crm->customerFixExternalIds($customersIdsFix);
            }

            if (!empty($ordersIdsFix)) {
                $crm->orderFixExternalIds($ordersIdsFix);
            }

        } else {
            $this->log->addNotice('['.$this->config->get('store_name').'] RestApi::orderHistory: you need to configure Intarocrm module first.');
        }
    }

    public function exportXml()
    {
        $string = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="'.date('Y-m-d H:i:s').'">
                <shop>
                    <name>'.$this->config->get('config_name').'</name>
                    <categories/>
                    <offers/>
                </shop>
            </yml_catalog>
        ';

        $xml = new SimpleXMLElement($string, LIBXML_NOENT |LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $this->dd = new DOMDocument();
        $this->dd->preserveWhiteSpace = false;
        $this->dd->formatOutput = true;
        $this->dd->loadXML($xml->asXML());

        $this->eCategories = $this->dd->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();

        $this->dd->saveXML();

        $downloadPath = __DIR__ . '/../../../download/';

        if (!file_exists($downloadPath)) {
            mkdir($downloadPath, 0755);
        }

        $this->dd->save($downloadPath . 'intarocrm.xml');
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

    private function addCategories()
    {
        $this->load->model('catalog/category');

        foreach ($this->model_catalog_category->getCategories(array()) as $category) {
            $e = $this->eCategories->appendChild($this->dd->createElement('category', $category['name']));
            $e->setAttribute('id',$category['category_id']);
        }

    }

    private function addOffers()
    {
        $this->load->model('catalog/product');
        $this->load->model('catalog/manufacturer');
        $this->load->model('tool/image');

        $offerManufacturers = array();

        $manufacturers = $this->model_catalog_manufacturer->getManufacturers(array());

        foreach ($manufacturers as $manufacturer) {
            $offerManufacturers[$manufacturer['manufacturer_id']] = $manufacturer['name'];
        }

        foreach ($this->model_catalog_product->getProducts(array()) as $offer) {

            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));
            $e->setAttribute('id', $offer['product_id']);
            $e->setAttribute('productId', $offer['product_id']);
            $e->setAttribute('quantity', $offer['quantity']);
            $e->setAttribute('available', $offer['status'] ? 'true' : 'false');

            /*
             * DIRTY HACK, NEED TO REFACTOR
             */

            $sql = "SELECT * FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " .$offer['product_id']. ";";
            $result = $this->db->query($sql);
            foreach ($result->rows as $row) {
                $e->appendChild($this->dd->createElement('categoryId', $row['category_id']));
            }

            $e->appendChild($this->dd->createElement('name'))->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('productName'))->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('price', $offer['price']));

            if ($offer['manufacturer_id'] != 0) {
                $e->appendChild($this->dd->createElement('vendor'))->appendChild($this->dd->createTextNode($offerManufacturers[$offer['manufacturer_id']]));
            }

            if ($offer['image']) {
                $e->appendChild(
                    $this->dd->createElement(
                        'picture',
                        $this->model_tool_image->resize($offer['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'))
                    )
                );
            }

            $this->url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
            $e->appendChild($this->dd->createElement('url'))->appendChild(
                $this->dd->createTextNode(
                    $this->url->link('product/product&product_id=' . $offer['product_id'])
                )
            );

            if ($offer['sku'] != '') {
                $sku = $this->dd->createElement('param');
                $sku->setAttribute('name', 'article');
                $sku->appendChild($this->dd->createTextNode($offer['sku']));
                $e->appendChild($sku);
            }

            if ($offer['weight'] != '') {
                $weight = $this->dd->createElement('param');
                $weight->setAttribute('name', 'weight');
                $weightValue = (isset($offer['weight_class'])) ? round($offer['weight'], 3) . ' ' . $offer['weight_class'] : round($offer['weight'], 3);
                $weight->appendChild($this->dd->createTextNode($weightValue));
                $e->appendChild($weight);
            }

            if ($offer['length'] != '' && $offer['width'] != '' && $offer['height'] != '') {
                $size = $this->dd->createElement('param');
                $size->setAttribute('name', 'size');
                $size->appendChild(
                    $this->dd->createTextNode(
                        round($offer['length'], 2) .'x'.
                        round($offer['width'], 2) .'x'.
                        round($offer['height'], 2)
                    )
                );
                $e->appendChild($size);
            }
        }
    }
}
?>