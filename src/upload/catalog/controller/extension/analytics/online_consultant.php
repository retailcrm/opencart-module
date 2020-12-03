<?php

class ControllerExtensionAnalyticsOnlineConsultant extends Controller {
    /**
     * @return string
     */
    public function index() {
        $this->load->model('setting/setting');
        $this->load->library('retailcrm/retailcrm');

        $moduleTitle = $this->retailcrm->getModuleTitle();
        $settings = $this->model_setting_setting->getSetting($moduleTitle);
        $setting = trim($settings['module_retailcrm_online_consultant_code']);

        return html_entity_decode($setting, ENT_QUOTES, 'UTF-8');
    }
}
