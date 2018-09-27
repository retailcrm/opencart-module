<?php

class ModelExtensionRetailcrmProducts extends Model {
    public function getProductOptions($product_id) {
        $this->load->model('catalog/product_option');

        return $this->model_catalog_product_option->getProductOptionsByProductId($product_id);
    }
}
