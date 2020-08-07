<?php

require_once __DIR__ . '/../../' . getenv('TEST_SUITE') . '/TestCase.php';

class UtilsTest extends TestCase {
    public function testFilterRecursive() {
        $testArray = array(
            '0' => 0, 1 => 1, 'array' => array(), 'array0' => array('0'), 'emptyString' => ''
        );

        $array = \retailcrm\Utils::filterRecursive($testArray);

        $this->assertNotEmpty($array);
        $this->assertArrayHasKey('0', $array);
        $this->assertArrayHasKey(1, $array);
        $this->assertArrayHasKey('array0', $array);
        $this->assertEquals('0', $array['array0'][0]);
        $this->assertArrayNotHasKey('array', $array);
        $this->assertArrayNotHasKey('emptyString', $array);
    }
}
