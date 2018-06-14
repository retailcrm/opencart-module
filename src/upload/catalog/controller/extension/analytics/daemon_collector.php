<?php
class ControllerExtensionAnalyticsDaemonCollector extends Controller {
    public function index() {
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');
        $moduleTitle = $this->retailcrm->getModuleTitle();

        $settings = $this->model_setting_setting->getSetting($moduleTitle);
        $setting = $settings[$moduleTitle . '_collector'];
        $siteCode = isset($setting['site_key']) ? $setting['site_key'] : '';

        if ($this->customer->isLogged()) $customerId = $this->customer->getID();
        
        $customer = isset($customerId) ? "'customerId': '" . $customerId . "'" : "";
        $labelPromo = !empty($setting['label_promo']) ? $setting['label_promo'] : null;
        $labelSend = !empty($setting['label_send']) ? $setting['label_send'] : null;
        $customForm = '';

        if (isset($setting['custom']) && $setting['custom_form'] == 1) {
            $customForm = "'fields': {";
            $cntEmpty = 0;

            foreach ($setting['custom'] as $field => $label) {
                if (empty($label)) {
                    $cntEmpty += 1;

                    continue;
                }

                if (isset($setting['require'][$field . '_require'])) {
                    $customForm .= "\n\t'$field': { required: true, label: '$label' },";
                } else {
                    $customForm .= "\n\t'$field': { label: '$label' },";
                }
            }
            $customForm .= "\n\t},";

            if ($cntEmpty == count($setting['custom'])) $customForm = '';
        }

        if (isset($setting['form_capture']) && $setting['form_capture'] == 1) {

            if (!empty($setting['period']) && is_numeric($setting['period'])) {

                if ($labelPromo != null || $labelSend != null){
                    $captureForm = "_rc('require', 'capture-form', {
                        'period': " . $setting['period'] . ",
                        " . $customForm . "
                        labelPromo: '" . $labelPromo . "',
                        labelSend: '" . $labelSend . "'
                    });";
                } else {
                    $captureForm = "_rc('require', 'capture-form', {
                        'period': " . $settings[$moduleTitle . '_collector']['period'] . ",
                        " . $customForm . "
                    });";
                }  
            } elseif ($labelPromo != null || $labelSend != null) {
                $captureForm = "_rc('require', 'capture-form', {
                    " . $customForm . "
                    labelPromo: '" . $labelPromo . "',
                    labelSend: '" . $labelSend . "'
                });";
            } elseif (isset($setting['custom'])){
                $captureForm = "_rc('require', 'capture-form', {
                    " . $customForm . "
                });";
            } else {
                $captureForm = "_rc('require', 'capture-form');";
            }
        } else {
            $captureForm = "";
        }

        if (!$customer) {
            $initClient = "_rc('create', '" . $siteCode . "');";
        } else {
            $initClient = "_rc('create', '" . $siteCode . "', {
                " . $customer . "
            });";
        }

        $js = "<script type=\"text/javascript\">
            (function(_,r,e,t,a,i,l){_['retailCRMObject']=a;_[a]=_[a]||function(){(_[a].q=_[a].q||[]).push(arguments)};_[a].l=1*new Date();l=r.getElementsByTagName(e)[0];i=r.createElement(e);i.async=!0;i.src=t;l.parentNode.insertBefore(i,l)})(window,document,'script','https://collector.retailcrm.pro/w.js','_rc');"
            . $initClient . $captureForm .
            "_rc('send', 'pageView');
        </script>";

        return html_entity_decode($js, ENT_QUOTES, 'UTF-8');
    }
}
