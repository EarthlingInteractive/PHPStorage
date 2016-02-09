<?php

class EarthIT_Storage_PostgresStorageTest extends EarthIT_Storage_StorageTest
{
	protected function makeStorage() {
		return $this->registry->postgresStorage;
	}
	
	protected function preallocateEntityIds($count) {
		$this->registry->storageHelper->preallocateEntityIds($count);
	}
	
	protected function newEntityId() {
		return $this->registry->storageHelper->newEntityId();
	}
	
	public function testInsertSimple() {
		$this->registry->storageHelper->preallocateEntityIds(2);
		$entityId0 = $this->registry->storageHelper->newEntityId();
		$entityId1 = $this->registry->storageHelper->newEntityId();
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$oldUserCount = $this->registry->storageHelper->queryValue("SELECT COUNT(*) FROM storagetest.user");
		
		$this->storage->saveItems( array(
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc);
		
		$newUserCount = $this->registry->storageHelper->queryValue("SELECT COUNT(*) FROM storagetest.user");
		
		$this->assertEquals( $oldUserCount + 2, $newUserCount );
	}
}
