<?php

require_once 'v4_5.php';

class ModelExtensionRetailcrmHistoryV3 extends ModelExtensionRetailcrmHistoryV45
{
    protected $createResult;

    private $opencartApiClient;

    /**
     * Getting changes from RetailCRM
     * 
     * @return boolean
     */
    public function request()
    {      
        $moduleTitle = $this->retailcrm->getModuleTitle();
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

        $settings = $this->model_setting_setting->getSetting($moduleTitle);
        $history = $this->model_setting_setting->getSetting('retailcrm_history');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        $url = isset($settings[$moduleTitle . '_url']) ? $settings[$moduleTitle . '_url'] : null;
        $key = isset($settings[$moduleTitle . '_apikey']) ? $settings[$moduleTitle . '_apikey'] : null;

        if (empty($url) || empty($key)) {
            $this->log->addNotice('You need to configure retailcrm module first.');
            return false;
        }

        $crm = new RetailcrmProxy(
            $settings[$moduleTitle . '_url'],
            $settings[$moduleTitle . '_apikey'],
            DIR_SYSTEM . 'storage/logs/retailcrm.log',
            $settings[$moduleTitle . '_apiversion']
        );

        $lastRun = !empty($history['retailcrm_history_datetime'])
            ? new DateTime($history['retailcrm_history_datetime'])
            : new DateTime(date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s')))));

        $packsOrders = $crm->ordersHistory($lastRun);

        if(!$packsOrders->isSuccessful() && count($packsOrders['orders']) <= 0) {
            return false;
        }

        $generatedAt = $packsOrders['generatedAt'];

        $this->totalTitle = $this->totalTitles();
        $this->subtotalSettings = $this->model_setting_setting->getSetting($this->totalTitle . 'sub_total');
        $this->totalSettings = $this->model_setting_setting->getSetting($this->totalTitle . 'total');
        $this->shippingSettings = $this->model_setting_setting->getSetting($this->totalTitle . 'shipping');
        $this->delivery = array_flip($settings[$moduleTitle . '_delivery']);
        $this->payment = array_flip($settings[$moduleTitle . '_payment']);
        $this->status = array_flip($settings[$moduleTitle . '_status']);
        $this->payment_default = $settings[$moduleTitle . '_default_payment'];
        $this->delivery_default = $settings[$moduleTitle . '_default_shipping'];
        $this->ocPayment = $this->model_extension_retailcrm_references
            ->getOpercartPaymentTypes();

        $this->ocDelivery = $this->model_extension_retailcrm_references
            ->getOpercartDeliveryTypes();

        $this->zones = $this->model_localisation_zone->getZones();

        $updatedOrders = array();
        $newOrders = array();
        $orders = $packsOrders['orders'];

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

        if (!empty($newOrders)) {
            $orders = $crm->ordersList($filter = array('ids' => $newOrders));
            if ($orders) {
                $this->createResult = $this->createOrders($orders['orders']);
            }
        }

        if (!empty($updatedOrders)) {
            $orders = $crm->ordersList($filter = array('ids' => $updatedOrders));
            if ($orders) {
                $this->updateOrders($orders['orders']);
            }
        }
        
        $this->model_setting_setting->editSetting('retailcrm_history', array('retailcrm_history_datetime' => $generatedAt));

        if (!empty($this->createResult['customers'])) {
            $crm->customersFixExternalIds($this->createResult['customers']);
        }

        if (!empty($this->createResult['orders'])) {
            $crm->ordersFixExternalIds($this->createResult['orders']);
        }
    }
}
