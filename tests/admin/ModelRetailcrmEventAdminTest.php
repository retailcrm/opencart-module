<?php

require_once __DIR__ . '/../' . getenv('TEST_SUITE') . '/TestCase.php';

class ModelRetailcrmEventAdminTest extends TestCase
{
    const CODE = 'test';

    public function setUp()
    {
        parent::setUp();

        $eventModel = $this->loadModel('setting/event');
        $eventModel->addEvent(self::CODE, 'test', 'test');
    }

    public function testGetEvent()
    {
        $eventModel = $this->loadModel('extension/retailcrm/event');
        $event = $eventModel->getEventByCode(self::CODE);

        $this->assertNotEmpty($event);
        $this->assertEquals(self::CODE, $event['code']);
        $this->assertEquals('test', $event['trigger']);
        $this->assertEquals('test', $event['action']);
    }
}
