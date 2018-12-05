<?php

class ModelExtensionRetailcrmEvent extends Model{
    public function getEventByCode($code) {
        $query = $this->db->query("SELECT DISTINCT * FROM `" . DB_PREFIX . "event` WHERE `code` = '" . $this->db->escape($code) . "' LIMIT 1");
        return $query->row;
    }
}
