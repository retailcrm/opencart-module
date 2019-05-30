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
        $this->load->model('customer/customer_group');

        $this->moduleTitle = $this->retailcrm->getModuleTitle();
        $this->settings = $this->model_setting_setting->getSetting($this->moduleTitle);
    }

    /**
     * Upload prices to CRM
     *
     * @param array $products
     * @param RetailcrmProxy $retailcrmApiClient
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

        if ($this->settings[$this->moduleTitle . '_apiversion'] == 'v3') {
            return false;
        }

        foreach ($products as $product) {
            $specials = $this->model_catalog_product->getProductSpecials($product['product_id']);

            if ($specials) {
                $productPrice = array();

                if (is_array($specials) && count($specials)) {
                    $productPrice = $this->getSpecialPrice($specials);
                }

                $prices = array_merge($this->getPriceRequest($product, $site, $productPrice), $prices);
            } else {
                $productPrice = $this->getEmptyPrice();
                $prices = array_merge($prices, $this->getPriceRequest($product, $site, $productPrice));
                continue;
            }
        }

        return $prices;
    }

    /**
     * Get prices for request
     *
     * @param $product
     * @param $site
     * @param $productPrice
     *
     * @return array
     */
    private function getPriceRequest($product, $site, $productPrice)
    {
        $offers = $this->retailcrm->getOffers($product);
        $pricesProducts = array();

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
            $price = array();

            foreach($productPrice as $k => $v) {

                if (isset($this->settings[$this->moduleTitle . '_special_' . $k])) {
                    $price[] = array(
                        'code' => $this->settings[$this->moduleTitle . '_special_' . $k],
                        'price' => !$v['remove'] ? $v['price'] + $optionsValues['price'] : 0,
                        'remove' => $v['remove']
                    );
                }
            }

            $pricesProducts[] = array(
                'externalId' => $offerId ? $product['product_id'] . '#' . $offerId : $product['product_id'],
                'site' => $site,
                'prices' => $price
            );
        }

        return $pricesProducts;
    }

    /**
     * Get actual special
     *
     * @param array $specials
     *
     * @return array $productPrice
     */
    private function getSpecialPrice($specials)
    {
        $date = date('Y-m-d');
        $always = '0000-00-00';
        $productPrice = array();

        foreach ($specials as $special) {
            if (($special['date_start'] == $always && $special['date_end'] == $always)
                || ($special['date_start'] <= $date && $special['date_end'] >= $date)
            ) {
                if ((isset($groupId) && $groupId == $special['customer_group_id']) || !isset($groupId)) {
                    if ((isset($priority) && $priority > $special['priority'])
                        || !isset($priority)
                    ) {
                        $productPrice[$special['customer_group_id']]['price'] = $special['price'];
                        $productPrice[$special['customer_group_id']]['remove'] = false;
                        $priority = $special['priority'];
                        $groupId = $special['customer_group_id'];
                    }
                } else {
                    $productPrice[$special['customer_group_id']]['price'] = $special['price'];
                    $groupId = $special['customer_group_id'];
                    $productPrice[$special['customer_group_id']]['remove'] = false;
                }
            }
        }

        $customerGroups = $this->model_customer_customer_group->getCustomerGroups();

        foreach ($customerGroups as $customerGroup) {
            if (!isset($productPrice[$customerGroup['customer_group_id']])){
                $productPrice[$customerGroup['customer_group_id']]['remove'] = true;
            }
        }

        return $productPrice;
    }

    /**
     * Get price for no special
     *
     * @return array $productPrice
     */
    private function getEmptyPrice()
    {
        $customerGroups = $this->model_customer_customer_group->getCustomerGroups();
        $productPrice = array();

        foreach ($customerGroups as $customerGroup) {
            $productPrice[$customerGroup['customer_group_id']]['remove'] = true;
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
