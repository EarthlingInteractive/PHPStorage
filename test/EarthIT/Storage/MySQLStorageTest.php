<?php

// TODO: Implement MySQLGenerator, etc
// Then make this actually test some stuff.

class EarthIT_Storage_MySQLStorageTest extends EarthIT_Storage_TestCase /* EarthIT_Storage_StorageTest */
{
	public function testSelect() {
		$rows = $this->registry->mysqlRunner->fetchRows("SELECT 'horse' as `horsey`");
		$this->assertEquals( array(array('horsey'=>'horse')), $rows );
	}
	
	protected function makeStorage() {
		return $this->registry->mysqlStorage;
	}

	protected function preallocateEntityIds($count) { }
	protected function newEntityId() {
		return mt_rand(); // TODO
	}
}
