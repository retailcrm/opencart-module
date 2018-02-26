<?php

class ModelExtensionRetailcrmApi extends Model
{
    public function login($username, $key)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api` WHERE `username` = '" . $this->db->escape($username) . "' AND `key` = '" . $this->db->escape($key) . "' AND status = '1'");

        return $query->row;
    }
}
