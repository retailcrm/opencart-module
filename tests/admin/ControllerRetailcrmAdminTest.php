<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ControllerRetailcrmAdminTest extends TestCase
{
    const MODULE_TITLE = 'retailcrm';

    public function setUp()
    {
        parent::setUp();

        $query = $this->db->query("SELECT permission from ".DB_PREFIX."user_group WHERE name = 'Administrator'");
        $permissions = json_decode($query->row['permission'],true);

        if (!in_array('extension/module/retailcrm', $permissions['access'])) {
            $permissions['access'][] = 'extension/module/retailcrm';
            $this->db->query("UPDATE ".DB_PREFIX."user_group SET permission='".$this->db->escape(json_encode($permissions))."' WHERE name = 'Administrator'");
        }

        $this->retailcrm = $this->getMockBuilder('\retailcrm\Retailcrm')
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

        $response = $this->dispatchAction('extension/module/retailcrm/icml');

        $this->assertRegExp('/Connection settings/', $response->getOutput());
        $this->assertFileExists(DIR_SYSTEM . '../' . 'retailcrm.xml');
    }

    public function testInstallCollector()
    {
        $this->login('admin', 'admin');

        $response = $this->dispatchAction('extension/module/retailcrm/install_collector');

        $this->assertRegExp('/Connection settings/', $response->getOutput());
    }

    public function testUnnstallCollector()
    {
        $this->login('admin', 'admin');

        $response = $this->dispatchAction('extension/module/retailcrm/uninstall_collector');

        $this->assertRegExp('/Connection settings/', $response->getOutput());
    }

    public function tearDown()
    {
    }
}
