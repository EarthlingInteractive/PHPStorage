<?php

abstract class EarthIT_Storage_DBConnectionTest extends EarthIT_Storage_TestCase
{
	protected abstract function getStorageHelper();
	
	public function testSelectHelloWorld() {
		$rows = $this->getStorageHelper()->queryRow("SELECT 'Hello, world!' AS text");
		$this->assertEquals( array('text'=>'Hello, world!'), $rows);
	}
}
