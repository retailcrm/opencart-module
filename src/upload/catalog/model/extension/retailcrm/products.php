<?php

class ModelExtensionRetailcrmProducts extends Model {
    public function getProductOptions($product_id) {
        $this->load->model('catalog/product');

        return $this->model_catalog_product->getProductOptions($product_id);
    }
}
