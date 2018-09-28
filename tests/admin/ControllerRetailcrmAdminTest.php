<?php

namespace Tests;

class ControllerRetailcrmAdminTest extends OpenCartTest
{
    const MODULE_TITLE = 'retailcrm';

    public function setUp()
    {
        parent::setUp();

        $query = $this->db->query("SELECT permission from " . DB_PREFIX . "user_group WHERE name = 'Administrator'");
        $permissions = json_decode($query->row['permission'],true);

        if (!in_array('extension/module/retailcrm', $permissions['access'])) {
            $permissions['access'][] = 'extension/module/retailcrm';
            $this->db->query("UPDATE ".DB_PREFIX."user_group SET permission='".$this->db->escape(json_encode($permissions))."' WHERE name = 'Administrator'");
        }

        $this->retailcrm = $this->getMockBuilder('\retailcrm\retailcrm')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testIndex()
    {
        $this->login('admin', 'admin');
        $response = $this->dispatchAction('extension/module/retailcrm');

        $this->assertRegExp('/Connection settings/', $response->getOutput());
    }

    public function testIcml()
    {
        $this->login('admin', 'admin');
        $this->dispatchAction('extension/module/retailcrm/icml');
        $this->assertFileExists(DIR_SYSTEM . '../' . 'retailcrm.xml');
    }
}
