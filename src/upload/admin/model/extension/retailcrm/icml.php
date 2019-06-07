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
        $categories = $this->model_catalog_category->getCategories(array());
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
        $offerManufacturers = array();
        $currencyForIcml = $this->retailcrm->getCurrencyForIcml();
        $defaultCurrency = $this->getDefaultCurrency();
        $settingLenght = $this->retailcrm->getLenghtForIcml();
        $leghtsArray = $this->model_localisation_length_class->getLengthClasses();

        foreach ($leghtsArray as $lenght) {
            if ($lenght['value'] == 1) {
                $defaultLenght = $lenght;
            }
        }

        $manufacturers = $this->model_catalog_manufacturer
            ->getManufacturers(array());

        foreach ($manufacturers as $manufacturer) {
            $offerManufacturers[$manufacturer['manufacturer_id']] = $manufacturer['name'];
        }

        $products = $this->model_catalog_product->getProducts(array());

        foreach ($products as $product) {
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
                $e = $this->eOffers->appendChild($this->dd->createElement('offer'));

                if (!empty($offerId)) {
                    $e->setAttribute('id', $product['product_id'] . '#' . $offerId);
                    $e->setAttribute('productId', $product['product_id']);
                    $e->setAttribute('quantity', $optionsValues['qty']);
                } else {
                    $e->setAttribute('id', $product['product_id']);
                    $e->setAttribute('productId', $product['product_id']);
                    $e->setAttribute('quantity', $product['quantity']);
                }
                /**
                 * Offer activity
                 */
                $activity = $product['status'] == 1 ? 'Y' : 'N';
                $e->appendChild(
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
                        $e->appendChild($this->dd->createElement('categoryId'))
                            ->appendChild(
                                $this->dd->createTextNode($category)
                            );
                    }
                }
                /**
                 * Name & price
                 */
                $e->appendChild($this->dd->createElement('productName'))
                    ->appendChild($this->dd->createTextNode($product['name']));
                if (!empty($options)) {
                    $optionsString = array();
                    foreach($options as $option) {
                        $optionsString[] = $option['name'].': '.$option['value'];
                    }
                    $optionsString = ' ('.implode(', ', $optionsString).')';
                    $e->appendChild($this->dd->createElement('name'))
                        ->appendChild($this->dd->createTextNode($product['name'].$optionsString));
                } else {
                    $e->appendChild($this->dd->createElement('name'))
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

                $e->appendChild($this->dd->createElement('price'))
                    ->appendChild($this->dd->createTextNode($price));
                /**
                 * Vendor
                 */
                if ($product['manufacturer_id'] != 0) {
                    $e->appendChild($this->dd->createElement('vendor'))
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
                    (!empty($product['width'] && $product['width'] > 0))
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

                    if ($defaultLenght['length_class_id'] != $settingLenght) {
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

                    $e->appendChild($this->dd->createElement('dimensions'))
                        ->appendChild($this->dd->createTextNode($dimensions));
                }

                /**
                 * Image
                 */
                if ($product['image']) {
                    $image = $this->generateImage($product['image']);
                    $e->appendChild($this->dd->createElement('picture'))
                        ->appendChild($this->dd->createTextNode($image));
                }
                /**
                 * Url
                 */
                $this->url = new Url(
                    HTTP_CATALOG,
                    $this->config->get('config_secure')
                        ? HTTP_CATALOG
                        : HTTPS_CATALOG
                );
                $e->appendChild($this->dd->createElement('url'))
                    ->appendChild(
                        $this->dd->createTextNode(
                            $this->url->link(
                                'product/product&product_id=' . $product['product_id']
                            )
                        )
                    );
                // Options
                if (!empty($options)) {
                    foreach($options as $optionKey => $optionData) {
                        $param = $this->dd->createElement('param');
                        $param->setAttribute('code', $optionKey);
                        $param->setAttribute('name', $optionData['name']);
                        $param->appendChild($this->dd->createTextNode($optionData['value']));
                        $e->appendChild($param);
                    }
                }
                if ($product['sku']) {
                    $sku = $this->dd->createElement('param');
                    $sku->setAttribute('code', 'article');
                    $sku->setAttribute('name', $this->language->get('article'));
                    $sku->appendChild($this->dd->createTextNode($product['sku']));
                    $e->appendChild($sku);
                }
                if ($product['weight'] != '') {
                    $weight = $this->dd->createElement('param');
                    $weight->setAttribute('code', 'weight');
                    $weight->setAttribute('name', $this->language->get('weight'));
                    $weightValue = (isset($offer['weight_class']))
                        ? round($product['weight'], 3) . ' ' . $product['weight_class']
                        : round($product['weight'], 3)
                    ;
                    $weight->appendChild($this->dd->createTextNode($weightValue));
                    $e->appendChild($weight);
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

        return array(
            'optionName' => $option['name'],
            'optionValue' => $optionValue['name']
        );
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
