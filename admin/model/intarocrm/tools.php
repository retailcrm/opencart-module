<?php

class ModelIntarocrmTools extends Model {

    protected $dd, $eCategories, $eOffers;

    public function getOpercartDeliveryMethods()
    {
        $deliveryMethods = array();
        $files = glob(DIR_APPLICATION . 'controller/shipping/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('shipping/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $deliveryMethods[$extension.'.'.$extension] = strip_tags($this->language->get('heading_title'));
                }
            }
        }

        return $deliveryMethods;
    }

    public function getOpercartOrderStatuses()
    {
        $this->load->model('localisation/order_status');
        return $this->model_localisation_order_status->getOrderStatuses(array());
    }

    public function getOpercartPaymentTypes()
    {
        $paymentTypes = array();
        $files = glob(DIR_APPLICATION . 'controller/payment/*.php');

        if ($files) {
            foreach ($files as $file) {
                $extension = basename($file, '.php');

                $this->load->language('payment/' . $extension);

                if ($this->config->get($extension . '_status')) {
                    $paymentTypes[$extension] = strip_tags($this->language->get('heading_title'));
                }
            }
        }

        return $paymentTypes;
    }

    public function generateICML()
    {
        $string = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="'.date('Y-m-d H:i:s').'">
                <shop>
                    <name>'.$this->config->get('config_name').'</name>
                    <categories/>
                    <offers/>
                </shop>
            </yml_catalog>
        ';

        $xml = new SimpleXMLElement($string, LIBXML_NOENT |LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $this->dd = new DOMDocument();
        $this->dd->preserveWhiteSpace = false;
        $this->dd->formatOutput = true;
        $this->dd->loadXML($xml->asXML());

        $this->eCategories = $this->dd->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();

        $this->dd->saveXML();

        $downloadPath = DIR_SYSTEM . '/../download/';

        if (!file_exists($downloadPath)) {
            mkdir($downloadPath, 0755);
        }

        $this->dd->save($downloadPath . 'intarocrm.xml');
    }

    private function addCategories()
    {
        $this->load->model('catalog/category');

        foreach ($this->model_catalog_category->getCategories(array()) as $category) {
            $e = $this->eCategories->appendChild($this->dd->createElement('category', $category['name']));
            $e->setAttribute('id',$category['category_id']);
        }

    }

    private function addOffers()
    {
        $this->load->model('catalog/product');
        $this->load->model('catalog/manufacturer');
        $this->load->model('tool/image');

        $offerManufacturers = array();

        $manufacturers = $this->model_catalog_manufacturer->getManufacturers(array());

        foreach ($manufacturers as $manufacturer) {
            $offerManufacturers[$manufacturer['manufacturer_id']] = $manufacturer['name'];
        }

        foreach ($this->model_catalog_product->getProducts(array()) as $offer) {

            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));
            $e->setAttribute('id', $offer['product_id']);
            $e->setAttribute('productId', $offer['product_id']);
            $e->setAttribute('quantity', $offer['quantity']);
            $e->setAttribute('available', $offer['status'] ? 'true' : 'false');

            /*
             * DIRTY HACK, NEED TO REFACTOR
             */

            $sql = "SELECT * FROM `" .
                DB_PREFIX .
                "product_to_category` WHERE `product_id` = " .$offer['product_id']. ";"
            ;
            $result = $this->db->query($sql);
            foreach ($result->rows as $row) {
                $e->appendChild($this->dd->createElement('categoryId', $row['category_id']));
            }

            $e->appendChild($this->dd->createElement('name'))->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('productName'))
                ->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('price', $offer['price']));

            if ($offer['manufacturer_id'] != 0) {
                $e->appendChild($this->dd->createElement('vendor'))
                    ->appendChild($this->dd->createTextNode($offerManufacturers[$offer['manufacturer_id']]));
            }

            if ($offer['image']) {
                $e->appendChild(
                    $this->dd->createElement(
                        'picture',
                        $this->model_tool_image->resize(
                            $offer['image'],
                            $this->config->get('config_image_product_width'),
                            $this->config->get('config_image_product_height')
                        )
                    )
                );
            }

            $this->url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
            $e->appendChild($this->dd->createElement('url'))->appendChild(
                $this->dd->createTextNode(
                    $this->url->link('product/product&product_id=' . $offer['product_id'])
                )
            );

            if ($offer['sku'] != '') {
                $sku = $this->dd->createElement('param');
                $sku->setAttribute('name', 'article');
                $sku->appendChild($this->dd->createTextNode($offer['sku']));
                $e->appendChild($sku);
            }

            if ($offer['weight'] != '') {
                $weight = $this->dd->createElement('param');
                $weight->setAttribute('name', 'weight');
                $weightValue = (isset($offer['weight_class']))
                    ? round($offer['weight'], 3) . ' ' . $offer['weight_class']
                    : round($offer['weight'], 3)
                ;
                $weight->appendChild($this->dd->createTextNode($weightValue));
                $e->appendChild($weight);
            }

            if ($offer['length'] != '' && $offer['width'] != '' && $offer['height'] != '') {
                $size = $this->dd->createElement('param');
                $size->setAttribute('name', 'size');
                $size->appendChild(
                    $this->dd->createTextNode(
                        round($offer['length'], 2) .'x'.
                        round($offer['width'], 2) .'x'.
                        round($offer['height'], 2)
                    )
                );
                $e->appendChild($size);
            }
        }
    }
}