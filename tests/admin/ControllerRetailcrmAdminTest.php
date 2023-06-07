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

    public function testController()
    {
        $this->login('admin', 'admin');

        $response = $this->dispatchAction('extension/module/retailcrm');
        $this->assertRegExp('/Connection settings/', $response->getOutput());

        $response = $this->dispatchAction('extension/module/retailcrm/icml');

        $this->assertRegExp('/Connection settings/', $response->getOutput());
        $this->assertFileExists(DIR_SYSTEM . '../' . 'retailcrm.xml');

        $response = $this->dispatchAction('extension/module/retailcrm/install_collector');
        $this->assertRegExp('/Connection settings/', $response->getOutput());

        $response = $this->dispatchAction('extension/module/retailcrm/uninstall_collector');
        $this->assertRegExp('/Connection settings/', $response->getOutput());
    }

    public function testGetAvailableTypes()
    {
        $data = $this->getDataForTestAvailableTypes();
        $sites = end($data['site']);
        $types = $data['types'];

        $retailCrm = new ControllerExtensionModuleRetailcrm(self::$registry);
        $class = new ReflectionClass($retailCrm);
        $method = $class->getMethod('getAvailableTypes');
        $method->setAccessible(true);

        $result = $method->invokeArgs($retailCrm, [$sites, $types]);

        $this->assertNotEmpty($result['opencart']);
        $this->assertNotEmpty($result['retailcrm']);
        $this->assertCount(2, $result['retailcrm']);
        $this->assertNotEmpty($result['retailcrm']['test1']['code']);
        $this->assertNotEmpty($result['retailcrm']['test4']['code']);
    }

    private function getDataForTestAvailableTypes(): array
    {
        return [
            'site' => [
                'opencart' => [
                    'code' => 'opencart',
                    'name' => 'OpenCart'
                ]
            ],
            'types' => [
                'opencart' => [
                    'test'
                ],
                'retailcrm' => [
                    'test1' => [
                        'active' => true,
                        'sites' => [],
                        'code' => 'test1'
                    ],
                    'test2' => [
                        'active' => false,
                        'sites' => [],
                        'code' => 'test2'
                    ],
                    'test3' => [
                        'active' => true,
                        'sites' => ['otherSite'],
                        'code' => 'test3'
                    ],
                    'test4' => [
                        'active' => true,
                        'sites' => ['cms1', 'cms2', 'opencart'],
                        'code' => 'test4'
                    ]
                ]
            ]
        ];
    }
}
