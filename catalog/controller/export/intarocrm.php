<?php

class ControllerExportIntarocrm extends Controller {
    protected $dd;
    protected $eCategories;
    protected $eOffers;

    public function index() {
        header('Content-Type: text/xml');
        echo $this->xml();
    }

    private function xml()
    {
        $this->dd = new DOMDocument();
        $this->dd->loadXML('<?xml version="1.0" encoding="UTF-8"?>
        <yml_catalog date="'.date('Y-m-d H:i:s').'">
            <shop>
                <name>'.$this->config->get('config_name').'</name>
                <categories/>
                <offers/>
            </shop>
        </yml_catalog>
        ');

        $this->eCategories = $this->dd->getElementsByTagName('categories')->item(0);
        $this->eOffers = $this->dd->getElementsByTagName('offers')->item(0);

        $this->addCategories();
        $this->addOffers();
        return $this->dd->saveXML();
    }

    private function addCategories()
    {
        $this->language->load('product/category');
        $this->load->model('catalog/category');

        foreach ($this->model_catalog_category->getCategories() as $category) {
            $e = $this->eCategories->appendChild($this->dd->createElement('category', $category['name']));
            $e->setAttribute('id',$category['category_id']);
        }

    }

    private function addOffers()
    {
        $this->load->model('catalog/product');
        $this->load->model('tool/image');

        foreach ($this->model_catalog_product->getProducts(array()) as $offer) {
            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));
            $e->setAttribute('id', $offer['product_id']);
            $e->setAttribute('productId', $offer['product_id']);
            $e->setAttribute('quantity', $offer['quantity']);
            $e->setAttribute('available', $offer['status'] ? 'true' : 'false');

            /*
             * DIRTY HACK, NEED TO REFACTOR
             */

            $sql = "SELECT * FROM `" . DB_PREFIX . "product_to_category` WHERE `product_id` = " .$offer['product_id']. ";";
            $result = $this->db->query($sql);
            foreach ($result->rows as $row) {
                $e->appendChild($this->dd->createElement('categoryId', $row['category_id']));
            }

            $e->appendChild($this->dd->createElement('name'))->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('productName'))->appendChild($this->dd->createTextNode($offer['name'] .' '. $offer['model']));
            $e->appendChild($this->dd->createElement('vendor'))->appendChild($this->dd->createTextNode($offer['manufacturer']));
            $e->appendChild($this->dd->createElement('price', $offer['price']));

            if ($offer['image']) {
                $e->appendChild(
                    $this->dd->createElement(
                        'picture',
                        $this->model_tool_image->resize($offer['image'], $this->config->get('config_image_product_width'), $this->config->get('config_image_product_height'))
                    )
                );
            }

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
                $weightValue = (isset($offer['weight_class'])) ? $offer['weight'] . ' ' . $offer['weight_class'] : $offer['weight'];
                $weight->appendChild($this->dd->createTextNode($weightValue));
                $e->appendChild($weight);
            }

            if ($offer['length'] != '' && $offer['width'] != '' && $offer['height'] != '') {
                $size = $this->dd->createElement('param');
                $size->setAttribute('name', 'size');
                $size->appendChild($this->dd->createTextNode($offer['length'] .'x'. $offer['width'] .'x'. $offer['height']));
                $e->appendChild($size);
            }
        }
    }

}
?>
