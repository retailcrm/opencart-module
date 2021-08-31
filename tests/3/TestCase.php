<?php

class TestCase extends \Tests\OpenCartTest {
    public function setUp() {
        parent::setUp();

        $this->load->library('retailcrm/retailcrm');

        $this->setSetting(
            $this->retailcrm->getModuleTitle(),
            array(
                $this->retailcrm->getModuleTitle() . '_apiversion' => 'v5',
                $this->retailcrm->getModuleTitle() . '_order_number' => 1,
                $this->retailcrm->getModuleTitle() . '_status' => array(
                    1 => 'new'
                ),
                $this->retailcrm->getModuleTitle() . '_delivery' => array(
                    'flat.flat' => 'flat'
                ),
                $this->retailcrm->getModuleTitle() . '_payment' => array(
                    'cod' => 'cod'
                ),
                $this->retailcrm->getModuleTitle() . '_special_1' => 'special1',
                $this->retailcrm->getModuleTitle() . '_special_2' => 'special2',
                $this->retailcrm->getModuleTitle() . '_special_3' => 'special3',
                $this->retailcrm->getModuleTitle() . '_collector' => array(
                    'site_key' => 'RC-XXXXXXXXXX-X',
                    'custom_form' => 1,
                    'custom' => array(
                        'name' => 'Name',
                        'email' => 'Email',
                        'phone' => 'Phone',
                    ),
                    'form_capture' => 1,
                    'period' => 1
                )
            )
        );
    }

    protected function setSetting($code, $data, $store_id = 0) {
        $this->db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . (int)$store_id . "' AND `code` = '" . $this->db->escape($code) . "'");

        foreach ($data as $key => $value) {
            if (substr($key, 0, strlen($code)) == $code) {
                if (!is_array($value)) {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape($value) . "'");
                } else {
                    $this->db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . (int)$store_id . "', `code` = '" . $this->db->escape($code) . "', `key` = '" . $this->db->escape($key) . "', `value` = '" . $this->db->escape(json_encode($value, true)) . "', serialized = '1'");
                }
            }
        }
    }
    
    public function tearDown()
    {
    }
}
