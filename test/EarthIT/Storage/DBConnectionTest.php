<?php

class EarthIT_Storage_DBConnectionTest extends EarthIT_Storage_TestCase
{
	public function testSelectHelloWorld() {
		$rows = $this->registry->sqlRunner->fetchRows("SELECT 'Hello, world!' AS text");
		$this->assertEquals( array(
			array('text'=>'Hello, world!'),
		), $rows);
	}
}
