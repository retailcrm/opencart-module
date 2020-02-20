<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ControllerRetailcrmApiCatalogTest extends TestCase
{
    private $apiKey;
    private $retailcrm;

    const ORDER_ID = 1;
    const USERNAME = 'Default';

    public function setUp()
    {
        parent::setUp();

        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "api` WHERE api_id = 1");
        $api = $query->row;
        $this->apiKey = $api['key'];
        $this->retailcrm = new \retailcrm\Retailcrm(self::$registry);

        $this->setSetting(
            $this->retailcrm->getModuleTitle(),
            array(
                $this->retailcrm->getModuleTitle() . '_country' => array(1),
            )
        );

        if (isset($this->request->get['key']) && isset($this->request->get['username'])) {
            unset($this->request->get['key']);
            unset($this->request->get['username']);
        }
    }

    public function testGetDeliveryTypes()
    {
        $response = $this->dispatchAction('api/retailcrm/getDeliveryTypes');
        $data = json_decode($response->getOutput());

        $this->assertEquals('Not found api key', $data->error);

        $this->request->get['key'] = $this->apiKey;
        $this->request->get['username'] = static::USERNAME;
        $response = $this->dispatchAction('api/retailcrm/getDeliveryTypes');
        $data = json_decode($response->getOutput());

        $this->assertNotEmpty($data);
    }

    public function testAddOrderHistory()
    {
        $response = $this->dispatchAction('api/retailcrm/addOrderHistory');
        $data = json_decode($response->getOutput());

        $this->assertEquals('Not found api key', $data->error);

        $this->request->get['key'] = $this->apiKey;
        $this->request->get['username'] = static::USERNAME;
        $response = $this->dispatchAction('api/retailcrm/addOrderHistory');
        $data = json_decode($response->getOutput());

        $this->assertEquals('Not found data', $data->error);
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
}
