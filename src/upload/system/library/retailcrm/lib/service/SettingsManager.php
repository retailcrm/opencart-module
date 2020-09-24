<?php

namespace retailcrm\service;

class SettingsManager extends \retailcrm\Base {
    private $settings;

    public function getSettings() {
        if (!$this->settings) {
            $this->settings = $this->model_setting_setting->getSetting($this->retailcrm->getModuleTitle());
        }

        return $this->settings;
    }

    public function getSettingByKey($key) {
        return $this->model_setting_setting->getSetting($key);
    }

    public function getSetting($key) {
        $settings = $this->getSettings();

        if (!empty($settings[$this->retailcrm->getModuleTitle() . '_' . $key])) {
            return $settings[$this->retailcrm->getModuleTitle() . '_' . $key];
        }

        return null;
    }

    public function getPaymentSettings() {
        return $this->getSettings()[$this->retailcrm->getModuleTitle() . '_payment'];
    }

    public function getDeliverySettings() {
        return $this->getSettings()[$this->retailcrm->getModuleTitle() . '_delivery'];
    }

    public function getStatusSettings() {
        return $this->getSettings()[$this->retailcrm->getModuleTitle() . '_status'];
    }
}
