<?php

class ModelRetailcrmEventAdminTest extends OpenCartTest
{
    const CODE = 'test';

    protected function setUp()
    {
        parent::setUp();

        $eventModel = $this->loadModel('extension/event');
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
