<?php

namespace retailcrm\repository;

class ProductsRepository extends \retailcrm\Base {
    public function getProductSpecials($product_id) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "' ORDER BY priority, price");

        return $query->rows;
    }

    public function getProductOptions($product_id) {
        return $this->model_catalog_product->getProductOptions($product_id);
    }
}
