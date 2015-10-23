<?php

class EarthIT_Storage_StorageTest extends EarthIT_Storage_TestCase
{
	public function testInsertSimple() {
		$this->registry->storageHelper->preallocateEntityIds(2);
		$entityId0 = $this->registry->storageHelper->newEntityId();
		$entityId1 = $this->registry->storageHelper->newEntityId();
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$oldUserCount = $this->registry->storageHelper->queryValue("SELECT COUNT(*) FROM storagetest.user");
		
		$this->registry->postgresStorage->saveItems( array(
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'password' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'password' => 'asd125'),
		), $userRc);
		
		$newUserCount = $this->registry->storageHelper->queryValue("SELECT COUNT(*) FROM storagetest.user");
		
		$this->assertEquals( $oldUserCount + 2, $newUserCount );
	}
	
	public function testInsertFullyWithReturn() {
		$this->registry->storageHelper->preallocateEntityIds(2);
		$entityId0 = $this->registry->storageHelper->newEntityId();
		$entityId1 = $this->registry->storageHelper->newEntityId();
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->registry->postgresStorage->saveItems( array(
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'password' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'password' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$this->assertEquals( 2, count($newUsers) );
	}
	
	public function testInsertDefaultIdsWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->registry->postgresStorage->saveItems( array(
			array('username' => 'Bob Hope', 'password' => 'asd123'),
			array('username' => 'Bob Jones', 'password' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$this->assertEquals( 2, count($newUsers) );
		foreach( $newUsers as $newUser ) {
			$this->assertTrue( isset($newUser['ID']), "Database-defaulted ID field should have a value." );
		}
	}
}
