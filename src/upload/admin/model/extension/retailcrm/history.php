<?php

class ModelExtensionRetailcrmHistory extends Model {
    protected $createResult;
    protected $settings;
    protected $moduleTitle;
    protected $opencartApiClient;

    private $orders_history;
    private $customers_history;
    private $data_repository;

    public function __construct($registry) {
        parent::__construct($registry);
        $this->load->library('retailcrm/retailcrm');
        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->opencartApiClient = $this->retailcrm->getOcApiClient($registry);
    }

    /**
     * Getting changes from RetailCRM
     * @param \RetailcrmProxy $retailcrmApiClient
     *
     * @return boolean
     */
    public function request($retailcrmApiClient) {
        $this->load->library('retailcrm/retailcrm');
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('user/api');
        $this->load->model('sale/order');
        $this->load->model('customer/customer');
        $this->load->model('extension/retailcrm/references');
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
        $this->load->model('localisation/zone');

        $this->load->language('extension/module/retailcrm');

        $this->data_repository = new \retailcrm\repository\DataRepository($this->registry);
        $this->orders_history = new retailcrm\history\Order(
            $this->data_repository,
            new \retailcrm\service\SettingsManager($this->registry),
            new \retailcrm\repository\ProductsRepository($this->registry),
            new \retailcrm\repository\OrderRepository($this->registry)
        );

        $this->customers_history = new retailcrm\history\Customer(
            $this->data_repository,
            new \retailcrm\repository\CustomerRepository($this->registry),
            new \retailcrm\service\SettingsManager($this->registry)
        );

        $this->orders_history->setOcDelivery(
            $this->model_extension_retailcrm_references->getOpercartDeliveryTypes()
        );

        $this->orders_history->setOcPayment(
            $this->model_extension_retailcrm_references->getOpercartPaymentTypes()
        );

        $settings = $this->model_setting_setting->getSetting($this->moduleTitle);
        $history = $this->model_setting_setting->getSetting('retailcrm_history');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        $url = isset($settings[$this->moduleTitle . '_url']) ? $settings[$this->moduleTitle . '_url'] : null;
        $key = isset($settings[$this->moduleTitle . '_apikey']) ? $settings[$this->moduleTitle . '_apikey'] : null;

        if (empty($url) || empty($key)) {
            $this->log->addNotice('You need to configure retailcrm module first.');

            return false;
        }

        $sinceIdOrders = $history['retailcrm_history_orders'] ?? 0;
        $sinceIdCustomers = $history['retailcrm_history_customers'] ?? 0;
        $packsOrders = $retailcrmApiClient->ordersHistory(['sinceId' => $sinceIdOrders]);
        $packsCustomers = $retailcrmApiClient->customersHistory(['sinceId' => $sinceIdCustomers]);

        if (!$packsOrders->isSuccessful() && count($packsOrders->history) <= 0
            && !$packsCustomers->isSuccessful() && count($packsCustomers->history) <= 0
        ) {
            return false;
        }

        $ordersHistory = $packsOrders->history;
        $customersHistory = $packsCustomers->history;
        $lastChangeOrders = $ordersHistory ? end($ordersHistory) : null;
        $lastChangeCustomers = $customersHistory ? end($customersHistory) : null;

        if ($lastChangeOrders !== null && $lastChangeCustomers !== null) {
            $this->model_setting_setting->editSetting(
                'retailcrm_history',
                [
                    'retailcrm_history_orders' => $lastChangeOrders['id'],
                    'retailcrm_history_customers' => $lastChangeCustomers['id']
                ]
            );
        }

        $orders = RetailcrmHistoryHelper::assemblyOrder($ordersHistory);
        $customers = RetailcrmHistoryHelper::assemblyCustomer($customersHistory);
        $newOrders = [];
        $updatedOrders = [];
        $this->settings = $settings;
        $this->status = array_flip($settings[$this->moduleTitle . '_status']);

        foreach ($orders as $order) {
            if (isset($order['deleted'])) {
                continue;
            }

            if (isset($order['externalId'])) {
                $updatedOrders[] = $order['id'];
            } else {
                $newOrders[] = $order['id'];
            }
        }

        unset($orders);

        $updateCustomers = [];

        foreach ($customers as $customer) {
            if (isset($customer['deleted'])) {
                continue;
            }

            if (isset($customer['externalId'])) {
                $updateCustomers[] = $customer['id'];
            }
        }

        unset($customers);

        if (!empty($updateCustomers)) {
            $customers = $retailcrmApiClient->customersList(['ids' => $updateCustomers]);
            if ($customers) {
                $this->updateCustomers($customers['customers']);
            }
        }

        if (!empty($newOrders)) {
            $orders = $retailcrmApiClient->ordersList(['ids' => $newOrders]);
            if ($orders) {
                $this->createResult = $this->createOrders($orders['orders'], $retailcrmApiClient);
            }
        }

        if (!empty($updatedOrders)) {
            $orders = $retailcrmApiClient->ordersList(['ids' => $updatedOrders]);

            if ($orders) {
                $this->updateOrders($orders['orders'], $retailcrmApiClient);
            }
        }

        if (!empty($this->createResult['customers'])) {
            $retailcrmApiClient->customersFixExternalIds($this->createResult['customers']);
        }

        if (!empty($this->createResult['orders'])) {
            $retailcrmApiClient->ordersFixExternalIds($this->createResult['orders']);
        }

        return true;
    }

    /**
     * Create orders from history
     *
     * @param array $orders
     * @param \RetailcrmProxy $retailcrmApiClient
     *
     * @return array
     */
    protected function createOrders($orders, $retailcrmApiClient) {
        $customersIdsFix = array();
        $ordersIdsFix = array();

        foreach ($orders as $order) {
            $data = array();
            $corporateAddress = array();

            if (!empty($order['customer']['type']) && $order['customer']['type'] === 'customer_corporate') {
                $customer = $order['contact'];
                if (empty($customer['address'])) {
                    $corporateAddress = $this->getCorporateCustomerAddress($retailcrmApiClient, $order);

                    if (!empty($corporateAddress)) {
                        $customer['address'] = $corporateAddress;
                    }
                }
            } else {
                $customer = $order['customer'];
            }

            $customer_id = (!empty($customer['externalId']))
                ? $customer['externalId']
                : 0;

            if ($customer_id === 0) {
                $customer_data = array();

                $this->customers_history->handleCustomer($customer_data, $customer);
                $address = $this->customers_history->handleAddress($customer, $order);
                $this->customers_history->handleCustomFields($customer_data, $customer);
                $customer_data['address'] = array($address);
                $customer_id = $this->model_customer_customer->addCustomer($customer_data);

                $customersIdsFix[] = array('id' => $customer['id'], 'externalId' => (int)$customer_id);
            }

            $this->orders_history->handleBaseOrderData($data, $order);
            $this->orders_history->handleShipping($data, $order);
            $this->orders_history->handlePayment($data, $order, $corporateAddress);
            $this->orders_history->handleProducts($data, $order);
            $this->orders_history->handleTotals($data, $order);
            $this->orders_history->handleCustomFields($data, $order);
            $data['customer_id'] = $customer_id;

            $data['order_status_id'] = 1;

            $order_id = $this->data_repository->addOrder($data);

            $ordersIdsFix[] = array('id' => $order['id'], 'externalId' => (int) $order_id);
        }

        return array('customers' => $customersIdsFix, 'orders' => $ordersIdsFix);
    }

    /**
     * Update orders from history
     *
     * @param array $orders
     * @param \RetailcrmProxy $retailcrmApiClient
     *
     * @return void
     */
    protected function updateOrders($orders, $retailcrmApiClient) {
        foreach ($orders as $order) {
            $corporateAddress = array();
            $data = $this->model_sale_order->getOrder($order['externalId']);

            if (!empty($order['customer']['type']) && $order['customer']['type'] === 'customer_corporate') {
                $customer = $order['contact'];
                if (empty($customer['address'])) {
                    $corporateAddress = $this->getCorporateCustomerAddress($retailcrmApiClient, $order);

                    if (!empty($corporateAddress)) {
                        $customer['address'] = $corporateAddress;
                    }
                }
            } else {
                $customer = $order['customer'];
            }

            $customer_id = (!empty($customer['externalId']))
                ? $customer['externalId']
                : 0;

            if ($customer_id === 0) {
                $customer_data = array();

                $this->customers_history->handleCustomer($customer_data, $customer);
                $address = $this->customers_history->handleAddress($customer, $order);
                $this->customers_history->handleCustomFields($customer_data, $customer);
                $customer_data['address'] = array($address);
                $customer_id = $this->model_customer_customer->addCustomer($customer_data);

                $this->createResult['customers'][] = array('id' => $customer['id'], 'externalId' => (int)$customer_id);
            }

            $this->orders_history->handleBaseOrderData($data, $order);
            $this->orders_history->handleShipping($data, $order);
            $this->orders_history->handlePayment($data, $order, $corporateAddress);
            $this->orders_history->handleProducts($data, $order);
            $this->orders_history->handleTotals($data, $order);
            $this->orders_history->handleCustomFields($data, $order);

            $data['customer_id'] = $customer_id;
            if (array_key_exists($order['status'], $this->status)) {
                $data['order_status_id'] = $this->status[$order['status']];
            }

            if (isset($this->settings[$this->moduleTitle . '_status_changes'])
                && $this->settings[$this->moduleTitle . '_status_changes']
            ) {
                $this->opencartApiClient->addHistory($order['externalId'], $data['order_status_id']);
            }

            $this->data_repository->editOrder($order['externalId'], $data);
        }
    }

    protected function updateCustomers($customers) {
        foreach ($customers as $customer) {
            $customer_id = $customer['externalId'];
            $customer_data = $this->model_customer_customer->getCustomer($customer_id);

            $this->customers_history->handleCustomer($customer_data, $customer);
            $this->customers_history->handleCustomFields($customer_data, $customer);

            if (empty($customer_data['address_id'])) {
                $address = $this->customers_history->handleAddress($customer, array());
                $addresses = $this->model_customer_customer->getAddresses($customer_id);
                $addresses[] = $address;
                $customer_data['address'] = $addresses;
            } else {
                $updateAddress = $this->customers_history->handleAddress($customer, array(), $customer_data['address_id']);
                $addresses = $this->model_customer_customer->getAddresses($customer_id);
                $addresses[$customer_data['address_id']] = $updateAddress;
                $customer_data['address'] = $addresses;
            }

            $this->model_customer_customer->editCustomer($customer_id, $customer_data);
        }
    }

    /**
     * @param \RetailcrmProxy $retailcrmApiClient
     * @param array $order
     *
     * @return array
     */
    private function getCorporateCustomerAddress($retailcrmApiClient, $order) {
        $addresses = $retailcrmApiClient->customersCorporateAddresses(
            $order['customer']['id'],
            array('ids' => array($order['customer']['mainAddress'])),
            null,
            null,
            'id'
        );

        if ($addresses->isSuccessful() && !empty($addresses['addresses'])) {
            return $addresses['addresses'][0];
        }

        return array();
    }
}
