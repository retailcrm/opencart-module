<?php
class ModelIntarocrmOrder extends Model {

    public function send($order, $order_id)
    {
        $this->load->model('setting/setting');
        $settings = $this->model_setting_setting->getSetting('intarocrm');
        $settings['domain'] = parse_url(HTTP_SERVER, PHP_URL_HOST);

        if(isset($settings['intarocrm_url']) && $settings['intarocrm_url'] != '' && isset($settings['intarocrm_apikey']) && $settings['intarocrm_apikey'] != '') {
            include_once DIR_SYSTEM . 'library/intarocrm/apihelper.php';
            $order['order_id'] = $order_id;
            $crm = new ApiHelper($settings);
            $crm->processOrder($order);
        }

    }
}
?>