<?php

namespace retailcrm;

use retailcrm\repository\CustomerRepository;
use retailcrm\service\CorporateCustomer;
use retailcrm\service\CustomerManager;
use retailcrm\service\OrderManager;
use retailcrm\factory\OrderConverterFactory;
use retailcrm\factory\CustomerConverterFactory;
use retailcrm\service\SettingsManager;
use retailcrm\service\InventoryManager;

require_once DIR_SYSTEM . 'library/retailcrm/bootstrap.php';

class Retailcrm {

    const RETAILCRM_DISCOUNT = 'retailcrm_discount';
    const RETAILCRM_DISCOUNT_SORT_ORDER = 8;
    const VERSION_MODULE = '4.2.0';

    protected $registry;

    /** @var bool  */
    public static $history_run = false;



    public function __construct(\Registry $registry) {
        $this->registry = $registry;

        $modelsProvider = new ModelsProvider($this->registry);
        $modelsProvider->includeDependencies();
    }

    public function __get($name) {
        return $this->registry->get($name);
    }

    public function getCustomerManager() {
        return new CustomerManager($this->getApiClient(), CustomerConverterFactory::create($this->registry));
    }

    public function getOrderManager() {
        return new OrderManager(
            $this->getApiClient(),
            $this->getCustomerManager(),
            OrderConverterFactory::create($this->registry),
            $this->getCorporateCustomerService(),
            new SettingsManager($this->registry)
        );
    }

    public function getCorporateCustomerService() {
        return new CorporateCustomer($this->getApiClient(), new CustomerRepository($this->registry));
    }

    public function getInventoryManager() {
        return new InventoryManager($this->getApiClient());
    }

    /**
     * Get api client object
     *
     * @param string $apiUrl (default = null)
     * @param string $apiKey (default = null)
     *
     * @return mixed object | boolean
     */
    public function getApiClient($apiUrl = null, $apiKey = null) {
        if (!$this->registry->has('RetailcrmProxy')) {
            $setting = $this->model_setting_setting->getSetting($this->getModuleTitle());

            if ($apiUrl === null && $apiKey === null) {
                $apiUrl = isset($setting[$this->getModuleTitle() . '_url'])
                    ? $setting[$this->getModuleTitle() . '_url'] : '';
                $apiKey = isset($setting[$this->getModuleTitle() . '_apikey'])
                    ? $setting[$this->getModuleTitle() . '_apikey'] : '';
            }

            if (!$apiUrl || !$apiKey) {
                return false;
            }

            $this->registry->set(
                'RetailcrmProxy',
                new \RetailcrmProxy($apiUrl, $apiKey)
            );
        }

        return $this->registry->get('RetailcrmProxy');
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
        // Build offers by available options
        $options = $this->model_catalog_product->getProductOptions($product['product_id']);
        $offerOptions = array('select', 'radio');
        $requiredOptions = array();
        $notRequiredOptions = array();
        // Handle & sort mandatory options
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
        
        foreach($requiredOptions as $requiredOption) {
            if(empty($offers)) {
                foreach($requiredOption['product_option_value'] as $optionValue) {
                    $offers[$requiredOption['product_option_id'].':'.$requiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                        'price' => (float)$this->getOptionPrice($optionValue),
                        'qty' => $optionValue['quantity'],
                        'weight' => round($this->getWeightOption($optionValue), 3)
                    );
                }
            } else {
                foreach($offers as $optionKey => $optionAttr) {
                    unset($offers[$optionKey]); // Работая в контексте обязательных опций не забываем удалять прошлые обязательные опции, т.к. они должны быть скомбинированы с другими обязательными опциями
                    foreach($requiredOption['product_option_value'] as $optionValue) {
                        $offers[$optionKey.'_'.$requiredOption['product_option_id'].':'.$requiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                            'price' => $optionAttr['price'] + (float)$this->getOptionPrice($optionValue),
                            'qty' => ($optionAttr['qty'] > $optionValue['quantity']) ?
                                $optionValue['quantity'] : $optionAttr['qty'],
                            'weight' => round($optionAttr['weight'] + $this->getWeightOption($optionValue), 3)
                        );
                    }
                }
            }
        }

        foreach($notRequiredOptions as $notRequiredOption) {
            if(empty($offers)) {
                $offers['0:0-0'] = 0; // Add empty option for mandatory data
                foreach($notRequiredOption['product_option_value'] as $optionValue) {
                    $offers[$notRequiredOption['product_option_id'].':'.$notRequiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                        'price' => (float)$this->getOptionPrice($optionValue),
                        'qty' => $optionValue['quantity'],
                        'weight' => round($this->getWeightOption($optionValue), 3)
                    );
                }
            } else {
                foreach($offers as $optionKey => $optionAttr) {
                    foreach($notRequiredOption['product_option_value'] as $optionValue) {
                        $offers[$optionKey.'_'.$notRequiredOption['product_option_id'].':'.$notRequiredOption['option_id'].'-'.$optionValue['option_value_id']] = array(
                            'price' => $optionAttr['price'] + (float)$this->getOptionPrice($optionValue),
                            'qty' => ($optionAttr['qty'] > $optionValue['quantity']) ?
                                $optionValue['quantity'] : $optionAttr['qty'],
                            'weight' => round($optionAttr['weight'] + $this->getWeightOption($optionValue), 3)
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
     * @param array $option
     *
     * @return  float
     */
    private function getWeightOption($option) {
        if ($option['weight_prefix'] === '-') {
            return $option['weight'] * -1;
        }

        return $option['weight'];
    }

    /**
     * @param array $optionValue
     * @return float|int|mixed
     */
    private function getOptionPrice($optionValue) {
        if ($optionValue['price_prefix'] === '-') {
            return $optionValue['price'] * -1;
        }

        return $optionValue['price'];
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

    public function useServicesForIcml()
    {
        $this->load->model('setting/setting');

        $setting = $this->model_setting_setting->getSetting($this->getModuleTitle());

        return $setting['module_retailcrm_icml_service_enabled'] ?? false;
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
