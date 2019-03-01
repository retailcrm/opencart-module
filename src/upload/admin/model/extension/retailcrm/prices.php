<?php

class ModelExtensionRetailcrmPrices extends Model
{
    protected $settings;

    private $options;
    private $optionValues;

    /**
     * Upload prices to CRM
     *
     * @param array $products
     * @param \RetailcrmProxy $retailcrm_api_client
     * @param \Retailcrm\Retailcrm $retailcrm
     *
     * @return mixed bool | array
     */
    public function uploadPrices($products, $retailcrm_api_client, $retailcrm)
    {
        $this->load->model('catalog/option');
        $this->load->model('setting/setting');
        $this->load->model('customer/customer_group');

        $prices = $this->getPrices($products, $retailcrm_api_client, $retailcrm);

        if ($retailcrm_api_client === false || !$prices) {
            return false;
        }

        $pricesUpload = array_chunk($prices, 250);

        foreach ($pricesUpload as $priceUpload) {
            $retailcrm_api_client->storePricesUpload($priceUpload);
        }

        return $pricesUpload;
    }

    /**
     * Get prices
     *
     * @param array $products
     * @param \RetailcrmProxy $retailcrm_api_client
     * @param \Retailcrm\Retailcrm $retailcrm
     * @return mixed
     */
    protected function getPrices($products, $retailcrm_api_client, $retailcrm)
    {
        $prices = array();
        $site = $this->getSite($retailcrm_api_client);

        foreach ($products as $product) {
            $specials = $this->model_catalog_product->getProductSpecials($product['product_id']);

            if (!$specials) {
                $productPrice = $this->getEmptyPrice();
                $prices[] = $this->getPriceRequest($product, $site, $productPrice, $retailcrm);
                continue;
            }

            $productPrice = array();

            if (is_array($specials) && count($specials)) {
                $productPrice = $this->getSpecialPrice($specials);
            }

            $prices[] = $this->getPriceRequest($product, $site, $productPrice, $retailcrm);
        }

        return $prices;
    }

    /**
     * Get prices for request
     *
     * @param $product
     * @param $site
     * @param $productPrice
     * @param \Retailcrm\Retailcrm $retailcrm
     *
     * @return array
     */
    private function getPriceRequest($product, $site, $productPrice, $retailcrm)
    {
        $settings = $this->model_setting_setting->getSetting(\retailcrm\Retailcrm::MODULE);
        $offers = $retailcrm->getOffers($product);
        $pricesProduct = array();

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
                if (isset($settings[\Retailcrm\Retailcrm::MODULE . '_special_' . $k])) {
                    $price[] = array(
                        'code' => $settings[\Retailcrm\Retailcrm::MODULE . '_special_' . $k],
                        'price' => $v == 0 ? $v : $v + $optionsValues['price']
                    );
                }
            }

            $pricesProduct = array(
                'externalId' => $offerId ? $product['product_id'] . '#' . $offerId : $product['product_id'],
                'site' => $site,
                'prices' => $price
            );
        }

        return $pricesProduct;
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
            $productPrice[$customerGroup['customer_group_id']] = 0;
        }

        return $productPrice;
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
        $productPrice = array();

        foreach ($specials as $special) {
            if (($special['date_start'] == $always && $special['date_end'] == $always)
                || ($special['date_start'] <= $date && $special['date_end'] >= $date)
            ) {
                if ((isset($groupId) && $groupId == $special['customer_group_id']) || !isset($groupId)) {
                    if ((isset($priority) && $priority > $special['priority'])
                        || !isset($priority)
                    ) {
                        $productPrice[$special['customer_group_id']] = $special['price'];
                        $priority = $special['priority'];
                        $groupId = $special['customer_group_id'];
                    }
                } else {
                    $productPrice[$special['customer_group_id']] = $special['price'];
                    $groupId = $special['customer_group_id'];
                }
            }
        }

        $customerGroups = $this->model_customer_customer_group->getCustomerGroups();

        foreach ($customerGroups as $customerGroup) {
            if (!isset($productPrice[$customerGroup['customer_group_id']])){
                $productPrice[$customerGroup['customer_group_id']] = 0;
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
    private function getSite($retailcrm_api_client)
    {
        $response = $retailcrm_api_client->sitesList();

        if ($response && $response->isSuccessful()) {
            $sites = $response->sites;
            $site = end($sites);

            return $site['code'];
        }

        return false;
    }
}
