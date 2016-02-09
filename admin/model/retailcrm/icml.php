<?php

class ModelRetailcrmIcml extends Model
{
    protected $shop;
    protected $file;
    protected $properties;
    protected $params;
    protected $dd;
    protected $eCategories;
    protected $eOffers;

    public function generateICML()
    {
        $this->load->language('module/retailcrm');
        $this->load->model('catalog/category');
        $this->load->model('catalog/product');
        $this->load->model('catalog/manufacturer');

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
        foreach($categories as $category) {
            $e = $this->eCategories->appendChild(
                $this->dd->createElement(
                    'category', $category['name']
                )
            );

            $e->setAttribute('id', $category['category_id']);

            if ($category['parent_id'] > 0) {
                $e->setAttribute('parentId', $category['parent_id']);
            }
        }

    }

    private function addOffers()
    {
        $offerManufacturers = array();

        $manufacturers = $this->model_catalog_manufacturer
            ->getManufacturers(array());

        foreach ($manufacturers as $manufacturer) {
            $offerManufacturers[
                $manufacturer['manufacturer_id']
            ] = $manufacturer['name'];
        }

        $products = $this->model_catalog_product->getProducts(array());

        foreach ($products as $offer) {

            $e = $this->eOffers->appendChild($this->dd->createElement('offer'));
            $e->setAttribute('id', $offer['product_id']);
            $e->setAttribute('productId', $offer['product_id']);
            $e->setAttribute('quantity', $offer['quantity']);

            /**
             * Offer activity
             */
            $activity = $offer['status'] == 1 ? 'Y' : 'N';
            $e->appendChild(
                $this->dd->createElement('productActivity')
            )->appendChild(
                $this->dd->createTextNode($activity)
            );

            /**
             * Offer categories
             */
            $categories = $this->model_catalog_product
                ->getProductCategories($offer['product_id']);

            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $e->appendChild($this->dd->createElement('category'))
                        ->appendChild(
                            $this->dd->createTextNode($category)
                        );
                }
            }

            /**
             * Name & price
             */
            $e->appendChild($this->dd->createElement('name'))
                ->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('productName'))
                ->appendChild($this->dd->createTextNode($offer['name']));
            $e->appendChild($this->dd->createElement('price'))
                ->appendChild($this->dd->createTextNode($offer['price']));

            /**
             * Vendor
             */
            if ($offer['manufacturer_id'] != 0) {
                $e->appendChild($this->dd->createElement('vendor'))
                    ->appendChild(
                        $this->dd->createTextNode(
                            $offerManufacturers[$offer['manufacturer_id']]
                        )
                    );
            }

            /**
             * Image
             */
            if ($offer['image']) {
                $image = $this->generateImage($offer['image']);
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
                            'product/product&product_id=' . $offer['product_id']
                        )
                    )
            );


            if ($offer['sku']) {
                $sku = $this->dd->createElement('param');
                $sku->setAttribute('code', 'article');
                $sku->setAttribute('name', $this->language->get('article'));
                $sku->appendChild($this->dd->createTextNode($offer['sku']));
                $e->appendChild($sku);
            }

            if ($offer['weight'] != '') {
                $weight = $this->dd->createElement('param');
                $weight->setAttribute('color', 'weight');
                $weight->setAttribute('name', $this->language->get('weight'));
                $weightValue = (isset($offer['weight_class']))
                    ? round($offer['weight'], 3) . ' ' . $offer['weight_class']
                    : round($offer['weight'], 3)
                ;
                $weight->appendChild($this->dd->createTextNode($weightValue));
                $e->appendChild($weight);
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

        return $this->model_tool_image->resize(
            $image,
            $this->config->get('config_image_product_width'),
            $this->config->get('config_image_product_height')
        );
    }
}
