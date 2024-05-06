<?php

class ModelExtensionRetailcrmIcml extends Model
{
    protected $shop;
    protected $file;
    protected $properties;
    protected $params;
    protected $dd;
    protected $eCategories;
    protected $eOffers;

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
        $this->load->model('localisation/weight_class');
    }

    public function generateICML()
    {
        $this->load->language('extension/module/retailcrm');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/option');
        $this->load->model('catalog/manufacturer');
        $this->load->model('localisation/length_class');

        $string = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="'.date('Y-m-d H:i:s').'">
                <shop>
                    <name>'.$this->config->get('config_name').'</name>
                    <categories/>
                    <offers/>
                </shop>
            </yml_catalog>
        ';

        $xml = new SimpleXMLElement(
            $string,
            LIBXML_NOENT |LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE
        );

        $this->dd = new DOMDocument();
        $this->dd->preserveWhiteSpace = false;
        $this->dd->formatOutput = true;
        $this->dd->loadXML($xml->asXML());

        $this->eCategories = $this->dd
            ->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd
            ->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();

        $this->dd->saveXML();

        $downloadPath = DIR_SYSTEM . '/../';

        if (!file_exists($downloadPath)) {
            mkdir($downloadPath, 0755);
        }

        $this->dd->save($downloadPath . 'retailcrm.xml');
    }

    /**
     *
     */
    private function addCategories()
    {
        $categories = $this->model_catalog_category->getCategories([]);
        foreach ($categories as $category) {
            $category = $this->model_catalog_category->getCategory($category['category_id']);

            $c = $this->dd->createElement('category');

            if ($category['image']) {
                $c->appendChild(
                    $this->dd->createElement('picture', $this->generateImage($category['image']))
                );
            }

            $c->appendChild($this->dd->createElement('name', $category['name']));
            $e = $this->eCategories->appendChild($c);

            $e->setAttribute('id', $category['category_id']);

            if ($category['parent_id'] > 0) {
                $e->setAttribute('parentId', $category['parent_id']);
            }
        }

    }

    private function addOffers() {
        $offerManufacturers = [];
        $servicesForIcml = $this->retailcrm->useServicesForIcml();
        $currencyForIcml = $this->retailcrm->getCurrencyForIcml();
        $defaultCurrency = $this->getDefaultCurrency();
        $settingLenght = $this->retailcrm->getLenghtForIcml();
        $leghtsArray = $this->model_localisation_length_class->getLengthClasses();
        $weightClassesMas = $this->model_localisation_weight_class->getWeightClasses();
        $weightClasses = [];

        foreach ($weightClassesMas as $weightClass) {
            $weightClasses[$weightClass['weight_class_id']]['value'] = $weightClass['value'];
        }

        foreach ($leghtsArray as $lenght) {
            if ($lenght['value'] == 1) {
                $defaultLenght = $lenght;
            }
        }

        $manufacturers = $this->model_catalog_manufacturer
            ->getManufacturers([]);

        foreach ($manufacturers as $manufacturer) {
            $offerManufacturers[$manufacturer['manufacturer_id']] = $manufacturer['name'];
        }

        $products = $this->model_catalog_product->getProducts([]);

        foreach ($products as $product) {
            $offers = $this->retailcrm->getOffers($product);

            foreach ($offers as $optionsString => $optionsValues) {
                $optionsString = explode('_', $optionsString);
                $options = [];

                foreach($optionsString as $optionString) {
                    $option = explode('-', $optionString);
                    $optionIds = explode(':', $option[0]);

                    if ($optionString != '0:0-0') {
                        $optionData = $this->getOptionData($optionIds[1], $option[1]);
                        if (!empty($optionData)) {
                            $options[$optionIds[0]] = array(
                                'name' => $optionData['optionName'],
                                'value' => $optionData['optionValue'],
                                'value_id' => $option[1],
                                'option_id' => $optionIds[1]
                            );
                        }
                    }
                }

                ksort($options);

                $offerId = [];

                foreach($options as $optionKey => $optionData) {
                    $offerId[] = $optionKey.'-'.$optionData['value_id'];
                }

                $offerId = implode('_', $offerId);
                $catalog = $this->eOffers->appendChild($this->dd->createElement('offer'));

                if (!empty($offerId)) {
                    $catalog->setAttribute('id', $product['product_id'] . '#' . $offerId);
                    $catalog->setAttribute('productId', $product['product_id']);
                    $catalog->setAttribute('quantity', $optionsValues['qty']);
                } else {
                    $catalog->setAttribute('id', $product['product_id']);
                    $catalog->setAttribute('productId', $product['product_id']);
                    $catalog->setAttribute('quantity', $product['quantity']);
                }

                /**
                 * Set type for offers
                 */
                $useServices = $servicesForIcml && isset($product['shipping']) && $product['shipping'] == 0;

                $catalog->setAttribute('type', $useServices ? 'service' : 'product');

                /**
                 * Offer activity
                 */
                $activity = $product['status'] == 1 ? 'Y' : 'N';
                $catalog->appendChild(
                    $this->dd->createElement('productActivity')
                )->appendChild(
                    $this->dd->createTextNode($activity)
                );
                /**
                 * Offer categories
                 */
                $categories = $this->model_catalog_product
                    ->getProductCategories($product['product_id']);
                if (!empty($categories)) {
                    foreach ($categories as $category) {
                        $catalog->appendChild($this->dd->createElement('categoryId'))
                            ->appendChild(
                                $this->dd->createTextNode($category)
                            );
                    }
                }
                /**
                 * Name & price
                 */
                $catalog->appendChild($this->dd->createElement('productName'))
                    ->appendChild($this->dd->createTextNode($product['name']));
                if (!empty($options)) {
                    $optionsString = [];
                    foreach($options as $option) {
                        $optionsString[] = $option['name'].': '.$option['value'];
                    }
                    $optionsString = ' ('.implode(', ', $optionsString).')';
                    $catalog->appendChild($this->dd->createElement('name'))
                        ->appendChild($this->dd->createTextNode($product['name'].$optionsString));
                } else {
                    $catalog->appendChild($this->dd->createElement('name'))
                        ->appendChild($this->dd->createTextNode($product['name']));
                }

                if ($currencyForIcml && $currencyForIcml != $defaultCurrency) {
                    $price = $this->currency->convert(
                        $product['price'] + $optionsValues['price'],
                        $this->getDefaultCurrency(),
                        $this->retailcrm->getCurrencyForIcml()
                    );
                } else {
                    $price = $product['price'] + $optionsValues['price'];
                }

                $catalog->appendChild($this->dd->createElement('price'))
                    ->appendChild($this->dd->createTextNode($price));
                /**
                 * Vendor
                 */
                if ($product['manufacturer_id'] != 0) {
                    $catalog->appendChild($this->dd->createElement('vendor'))
                        ->appendChild(
                            $this->dd->createTextNode(
                                $offerManufacturers[$product['manufacturer_id']]
                            )
                        );
                }

                /**
                 * Dimensions
                 */
                if ((!empty($product['length']) && $product['length'] > 0) &&
                    (!empty($product['width']) && $product['width'] > 0)
                    && !empty($product['height']))
                {
                    $lenghtArray = $this->model_localisation_length_class->getLengthClass($product['length_class_id']);

                    if ($defaultLenght['length_class_id'] != $lenghtArray['length_class_id']) {
                        $productLength = $product['length'] / $lenghtArray['value'];
                        $productWidth = $product['width'] / $lenghtArray['value'];
                        $productHeight = $product['height'] / $lenghtArray['value'];
                    } else {
                        $productLength = $product['length'];
                        $productWidth = $product['width'];
                        $productHeight = $product['height'];
                    }

                    if ($defaultLenght['length_class_id'] != $settingLenght && $settingLenght) {
                        $unit = $this->model_localisation_length_class->getLengthClass($settingLenght);
                        $productLength = $productLength * $unit['value'];
                        $productWidth = $productWidth * $unit['value'];
                        $productHeight = $productHeight * $unit['value'];
                    }

                    $dimensions = sprintf(
                        '%01.3f/%01.3f/%01.3f',
                        $productLength,
                        $productWidth,
                        $productHeight
                    );

                    $catalog->appendChild($this->dd->createElement('dimensions'))
                        ->appendChild($this->dd->createTextNode($dimensions));
                }

                /**
                 * Image
                 */
                if ($product['image']) {
                    $image = $this->generateImage($product['image']);
                    $catalog->appendChild($this->dd->createElement('picture'))
                        ->appendChild($this->dd->createTextNode($image));
                }
                /**
                 * Url
                 */
                $this->url = new Url(HTTP_CATALOG, HTTPS_CATALOG);
                $catalog->appendChild($this->dd->createElement('url'))
                    ->appendChild(
                        $this->dd->createTextNode(
                            $this->url->link(
                                'product/product&product_id=' . $product['product_id'],
                                '',
                                (bool) $this->config->get('config_secure')
                            )
                        )
                    );

                // Options
                if (!empty($options)) {
                    foreach($options as $optionKey => $optionData) {
                        $param = $this->dd->createElement('param');
                        $param->setAttribute('code', $optionData['option_id']);
                        $param->setAttribute('name', $optionData['name']);
                        $param->appendChild($this->dd->createTextNode($optionData['value']));
                        $catalog->appendChild($param);
                    }
                }
                if ($product['sku']) {
                    $sku = $this->dd->createElement('param');
                    $sku->setAttribute('code', 'article');
                    $sku->setAttribute('name', $this->language->get('article'));
                    $sku->appendChild($this->dd->createTextNode($product['sku']));
                    $catalog->appendChild($sku);
                }
                if ($product['weight'] != '') {
                    $weight = $this->dd->createElement('weight');
                    $coeffWeight = 1;

                    if (!empty($weightClasses[$product['weight_class_id']]['value'])) {
                        $coeffWeight = $weightClasses[$product['weight_class_id']]['value'];
                    }

                    $weightValue = !empty($optionsValues['weight'])
                        ? $product['weight'] + $optionsValues['weight']
                        : $product['weight']
                    ;
                    $weightValue = round($weightValue / $coeffWeight, 6);

                    $weight->appendChild($this->dd->createTextNode($weightValue));
                    $catalog->appendChild($weight);
                }
            }
        }
    }

    /**
     * @param $image
     * @return mixed
     */
    private function generateImage($image)
    {
        $this->load->model('tool/image');

        $currentTheme = $this->config->get('config_theme');
        $width = $this->config->get($currentTheme . '_image_related_width') ? $this->config->get($currentTheme . '_image_related_width') : 200;
        $height = $this->config->get($currentTheme . '_image_related_height') ? $this->config->get($currentTheme . '_image_related_height') : 200;

        return $this->model_tool_image->resize(
            $image,
            $width,
            $height
        );
    }

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

        if (!empty($option['name']) && !empty($optionValue['name'])) {
            return array(
                'optionName' => $option['name'],
                'optionValue' => $optionValue['name']
            );
        }
    }

    private function getDefaultCurrency() {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency");

        foreach ($query->rows as $currency) {
            if ($currency['value'] == 1) {
                return $currency['code'];
            }
        }

        return $query->rows[0]['code'];
    }
}
