<?php

namespace retailcrm;

require_once 'bootstrap.php';

class Retailcrm {
    protected $apiClient;
    protected $registry;

    /** @var bool  */
    public static $history_run = false;

    public function __construct($registry) {
        $this->registry = $registry;
    }

    public function __get($name) {
        return $this->registry->get($name);
    }

    /**
     * Get api client object
     *
     * @param string $apiUrl (default = null)
     * @param string $apiKey (default = null)
     * @param string $apiVersion (default = null)
     *
     * @return mixed object | boolean
     */
    public function getApiClient($apiUrl = null, $apiKey = null, $apiVersion = null) {
        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting($this->getModuleTitle());

        if ($apiUrl === null && $apiKey === null) {
            $apiUrl = isset($setting[$this->getModuleTitle() . '_url'])
                ? $setting[$this->getModuleTitle() . '_url'] : '';
            $apiKey = isset($setting[$this->getModuleTitle() . '_apikey'])
                ? $setting[$this->getModuleTitle() . '_apikey'] : '';
            $apiVersion = isset($setting[$this->getModuleTitle() . '_apiversion'])
                ? $setting[$this->getModuleTitle() . '_apiversion'] : '';
        }

        if ($apiUrl && $apiKey) {
            return new \RetailcrmProxy($apiUrl, $apiKey, DIR_LOGS . 'retailcrm.log', $apiVersion);
        }

        return false;
    }

    /**
     * Get opencart api client
     *
     * @param object $registry
     *
     * @return \OpencartApiClient
     */
    public function getOcApiClient($registry) {
        return new \OpencartApiClient($registry);
    }

    /**
     * Get module title for this version
     *
     * @return string $title
     */
    public function getModuleTitle() {
        if (version_compare(VERSION, '3.0', '<')) {
            $title = 'retailcrm';
        } else {
            $title = 'module_retailcrm';
        }

        return $title;
    }

    /**
     * Get token param name
     *
     * @return string $token
     */
    public function getTokenTitle() {
        if (version_compare(VERSION, '3.0', '<')) {
            $token = 'token';
        } else {
            $token = 'user_token';
        }

        return $token;
    }

    public function getOffers($product) {
        // Формируем офферы отнсительно доступных опций
        $options = $this->model_catalog_product->getProductOptions($product['product_id']);
        $offerOptions = array('select', 'radio');
        $requiredOptions = array();
        $notRequiredOptions = array();
        // Оставляем опции связанные с вариациями товаров, сортируем по параметру обязательный или нет
        foreach($options as $option) {
            if(in_array($option['type'], $offerOptions)) {
                if($option['required']) {
                    $requiredOptions[] = $option;
                } else {
                    $notRequiredOptions[] = $option;
                }
            }
        }

        $offers = array();
        // Сначала совмещаем все обязательные опции
        foreach($requiredOptions as $requiredOption) {
            // Если первая итерация
            if(empty($offers)) {
                foreach($requiredOption['product_option_value'] as $optionValue) {
                    $offers[$requiredOption['product_option_id'].':'.$requiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                        'price' => (float)$optionValue['price'],
                        'qty' => $optionValue['quantity']
                    );
                }
            } else {
                foreach($offers as $optionKey => $optionAttr) {
                    unset($offers[$optionKey]); // Работая в контексте обязательных опций не забываем удалять прошлые обязательные опции, т.к. они должны быть скомбинированы с другими обязательными опциями
                    foreach($requiredOption['product_option_value'] as $optionValue) {
                        $offers[$optionKey.'_'.$requiredOption['product_option_id'].':'.$requiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                            'price' => $optionAttr['price'] + (float)$optionValue['price'],
                            'qty' => ($optionAttr['qty'] > $optionValue['quantity']) ?
                                $optionValue['quantity'] : $optionAttr['qty']
                        );
                    }
                }
            }
        }

        // Совмещаем или добавляем необязательные опции, учитывая тот факт что обязательных опций может и не быть.
        foreach($notRequiredOptions as $notRequiredOption) {
            // Если обязательных опцией не оказалось и первая итерация
            if(empty($offers)) {
                $offers['0:0-0'] = 0; // В случае работы с необязательными опциями мы должны учитывать товарное предложение без опций, поэтому создадим "пустую" опцию
                foreach($notRequiredOption['product_option_value'] as $optionValue) {
                    $offers[$notRequiredOption['product_option_id'].':'.$notRequiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                        'price' => (float)$optionValue['price'],
                        'qty' => $optionValue['quantity']
                    );
                }
            } else {
                foreach($offers as $optionKey => $optionAttr) {
                    foreach($notRequiredOption['product_option_value'] as $optionValue) {
                        $offers[$optionKey.'_'.$notRequiredOption['product_option_id'].':'.$notRequiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                            'price' => $optionAttr['price'] + (float)$optionValue['price'],
                            'qty' => ($optionAttr['qty'] > $optionValue['quantity']) ?
                                $optionValue['quantity'] : $optionAttr['qty']
                        );
                    }
                }
            }
        }

        if(empty($offers)) {
            $offers = array('0:0-0' => array('price' => '0', 'qty' => '0'));
        }

        return $offers;
    }

    /**
     * @return mixed
     */
    public function getCurrencyForIcml() {
        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting($this->getModuleTitle());

        if (isset($setting[$this->getModuleTitle() . '_currency'])
            && $this->currency->has($setting[$this->getModuleTitle() . '_currency'])
        ) {
            return $setting[$this->getModuleTitle() . '_currency'];
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getLenghtForIcml() {
        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting($this->getModuleTitle());

        if (isset($setting[$this->getModuleTitle() . '_lenght'])) {
            return $setting[$this->getModuleTitle() . '_lenght'];
        }

        return false;
    }
}
