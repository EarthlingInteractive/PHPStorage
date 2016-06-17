<?php

abstract class EarthIT_Storage_DBConnectionTest extends EarthIT_Storage_TestCase
{
	protected abstract function getStorageHelper();
	
	public function testSelectHelloWorld() {
		$rows = $this->getStorageHelper()->queryRow("SELECT 'Hello, world!' AS text");
		$this->assertEquals( array('text'=>'Hello, world!'), $rows);
	}
	
	/**
	 * Make sure INSERT .... RETURNING works like I expect.
	 * 
	 * Note: RETURNING (1, 2) is different than RETURNING 1, 2
	 * (and SELECT works the same way)
	 */
	public function testReturning() {
		$sql =
			"INSERT INTO storagetest.user (username) VALUES\n".
			"('bob'), ('fred')\n".
			"RETURNING id, username\n";
		$rows = $this->getStorageHelper()->queryRows($sql);
		$this->assertEquals(2, count($rows));
		foreach( $rows as $row ) {
			$this->assertEquals( 2, count($row) );
		}
	}
}
