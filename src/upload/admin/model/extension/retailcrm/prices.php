<?php

class ModelExtensionRetailcrmPrices extends Model
{
    protected $settings;
    protected $moduleTitle;
    private $options;
    private $optionValues;

    /**
     * Constructor
     * 
     * @param Registry $registry
     */
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->library('retailcrm/retailcrm');
        $this->load->model('catalog/option');
        $this->load->model('setting/setting');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
    }

    /**
     * Upload prices to CRM
     * 
     * @param array $products
     * @param \RetailcrmProxy $retailcrmApiClient
     * @return mixed bool | array
     */
    public function uploadPrices($products, $retailcrmApiClient)
    {
        $prices = $this->getPrices($products, $retailcrmApiClient);

        if ($retailcrmApiClient === false || !$prices) {
            return false;
        }

        $pricesUpload = array_chunk($prices, 250);

        foreach ($pricesUpload as $priceUpload) {
            $retailcrmApiClient->storePricesUpload($priceUpload);
        }

        return $pricesUpload;
    }

    /**
     * Get prices
     * 
     * @param array $products
     * 
     * @return mixed
     */
    protected function getPrices($products, $retailcrmApiClient)
    {
        $prices = array();
        $site = $this->getSite($retailcrmApiClient);

        if (!isset($this->settings[$this->moduleTitle . '_special'])
            || $this->settings[$this->moduleTitle . '_apiversion'] == 'v3'
        ) {
            return false;
        }

        foreach ($products as $product) {
            $specials = $this->model_catalog_product->getProductSpecials($product['product_id']);

            if (!$specials) {
                continue;
            }

            if (is_array($specials) && count($specials)) {
                $productPrice = $this->getSpecialPrice($specials);

                if (!$productPrice) {
                    continue;
                }
            }

            $offers = $this->retailcrm->getOffers($product);

            foreach ($offers as $optionsString => $optionsValues) {
                $optionsString = explode('_', $optionsString);
                $options = array();

                foreach($optionsString as $optionString) {
                    $option = explode('-', $optionString);
                    $optionIds = explode(':', $option[0]);

                    if ($optionString != '0:0-0') {
                        $optionData = $this->getOptionData($optionIds[1], $option[1]);
                        $options[$optionIds[0]] = array(
                            'name' => $optionData['optionName'],
                            'value' => $optionData['optionValue'],
                            'value_id' => $option[1]
                        );
                    }
                }

                ksort($options);

                $offerId = array();

                foreach($options as $optionKey => $optionData) {
                    $offerId[] = $optionKey.'-'.$optionData['value_id'];
                }

                $offerId = implode('_', $offerId);

                $prices[] = array(
                    'externalId' => $offerId ? $product['product_id'] . '#' . $offerId : $product['product_id'],
                    'site' => $site,
                    'prices' => array(
                        array(
                            'code' => $this->settings[$this->moduleTitle . '_special'],
                            'price' => $productPrice + $optionsValues['price']
                        )
                    )
                );
            }
        }

        return $prices;
    }

    /**
     * Get actual special
     * 
     * @param array $specials
     * 
     * @return float $productPrice
     */
    private function getSpecialPrice($specials)
    {
        $date = date('Y-m-d');
        $always = '0000-00-00';
        $productPrice = 0;

        foreach ($specials as $special) {
            if (($special['date_start'] == $always && $special['date_end'] == $always)
                || ($special['date_start'] <= $date && $special['date_end'] >= $date)
            ) {
                if ((isset($priority) && $priority > $special['priority'])
                    || !isset($priority)
                ) {
                    $productPrice = $special['price'];
                    $priority = $special['priority'];
                }
            }
        }

        return $productPrice;
    }

    /**
     * Get data option
     * 
     * @param int $optionId
     * @param int $optionValueId
     * 
     * @return array
     */
    private function getOptionData($optionId, $optionValueId) {
        if (!empty($this->options[$optionId])) {
            $option = $this->options[$optionId];
        } else {
            $option = $this->model_catalog_option->getOption($optionId);
            $this->options[$optionId] = $option;
        }

        if (!empty($this->optionValues[$optionValueId])) {
            $optionValue = $this->optionValues[$optionValueId];
        } else {
            $optionValue = $this->model_catalog_option->getOptionValue($optionValueId);
            $this->optionValues[$optionValueId] = $optionValue;
        }

        return array(
            'optionName' => $option['name'],
            'optionValue' => $optionValue['name']
        );
    }

    /**
     * Get site
     *
     * @param \RetailcrmProxy $retailcrmApiClient
     *
     * @return mixed boolean | string
     */
    private function getSite($retailcrmApiClient)
    {
        $response = $retailcrmApiClient->sitesList();

        if ($response && $response->isSuccessful()) {
            $sites = $response->sites;
            $site = end($sites);

            return $site['code'];
        }

        return false;
    }
}
