<?php
class ModelRetailcrmOrder extends Model {

    public function send($order, $order_id)
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('retailcrm');
        $order['order_status'] = $settings['retailcrm_status'][$order['order_status_id']];

        if(!empty($settings['retailcrm_url']) && !empty($settings['retailcrm_apikey'])) {
            require_once DIR_SYSTEM . 'library/retailcrm.php';
            $order['order_id'] = $order_id;
            $crm = new ApiHelper($settings);
            $crm->processOrder($order);
        }
    }
}

