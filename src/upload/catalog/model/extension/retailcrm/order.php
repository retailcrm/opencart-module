<?php

class ModelExtensionRetailcrmOrder extends Model {
    public function getOrderStatusId($order_id) {
        $query = $this->db->query("SELECT order_status_id FROM " . DB_PREFIX . "order WHERE order_id = '" . (int)$order_id . "'");

        return $query->rows;
    }
}
