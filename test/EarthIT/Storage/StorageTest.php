<?php

class EarthIT_Storage_StorageTest extends EarthIT_Storage_TestCase
{
	public function testInsertSimple() {
		$this->registry->storageHelper->preallocateEntityIds(2);
		$entityId0 = $this->registry->storageHelper->newEntityId();
		$entityId1 = $this->registry->storageHelper->newEntityId();
		$userRc = $this->registry->schema->getResourceClass('user');
		$this->registry->postgresStorage->shoveItems( array(
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'password' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'password' => 'asd125'),
																			 ), $userRc);
	}
}
