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
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'passhash' => 'asd125'),
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
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$this->assertEquals( 2, count($newUsers) );
	}
	
	public function testInsertDefaultIdsWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->registry->postgresStorage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$this->assertEquals( 2, count($newUsers) );
		foreach( $newUsers as $newUser ) {
			$this->assertTrue( isset($newUser['ID']), "Database-defaulted ID field should have a value." );
		}
	}
	
	protected static function keyById(array $things) {
		$keyed = array();
		foreach( $things as $t ) $keyed[$t['ID']] = $t;
		return $keyed;
	}
	
	public function testUpsertWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->registry->postgresStorage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$newUsers = self::keyById($newUsers);
		
		foreach( $newUsers as &$newUser ) {
			$newUser['username'] = 'Bob Dole';
		}; unset($newUser);
		
		// Update everyone to be named "Bob Dole"
		// but keep their existing passhash (and, of course, ID)
		$updatedUsers = $this->registry->postgresStorage->saveItems(
			$newUsers, $userRc,
			array('returnSaved'=>true, 'onDuplicateKey'=>EarthIT_Storage_ItemSaver::ODK_UPDATE)
		);
		
		$updatedUsers = self::keyById($updatedUsers);
		
		$this->assertEquals(2, count($updatedUsers));
		
		foreach( $updatedUsers as $user ) {
			$this->assertEquals( array(
				'ID' => $user['ID'],
				'username' => 'Bob Dole',
				'passhash' => $newUsers[$user['ID']]['passhash'],
				'e-mail address' => null
			), $user );
		}
	}
	
	public function testReplaceWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->registry->postgresStorage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$newUsers = self::keyById($newUsers);
		
		foreach( $newUsers as &$newUser ) {
			$newUser['username'] = 'Bob Dole';
			unset($newUser['passhash']);
		}; unset($newUser);
		
		// Update everyone to be named "Bob Dole"
		// but keep their existing passhash (and, of course, ID)
		$replacedUsers = $this->registry->postgresStorage->saveItems(
			$newUsers, $userRc,
			array('returnSaved'=>true, 'onDuplicateKey'=>EarthIT_Storage_ItemSaver::ODK_REPLACE)
		);
		
		$replacedUsers = self::keyById($replacedUsers);
		
		$this->assertEquals(2, count($replacedUsers));
		
		foreach( $replacedUsers as $user ) {
			$this->assertEquals( array(
				'ID' => $user['ID'],
				'username' => 'Bob Dole',
				'passhash' => null,
				'e-mail address' => null
			), $user );
		}
	}
	
	public function testInsertSkipWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->registry->postgresStorage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array('returnSaved'=>true));
		
		$newUsers = self::keyById($newUsers);
		
		$updates = array();
		foreach( $newUsers as $id=>$newUser ) {
			$updates[$id] = array('username'=>'Bob Saget') + $newUser;
			$newUser['username'] = 'Bob Dole';
		}
		
		$replacedUsers = $this->registry->postgresStorage->saveItems(
			$updates, $userRc,
			array('returnSaved'=>true, 'onDuplicateKey'=>EarthIT_Storage_ItemSaver::ODK_KEEP)
		);
		
		$replacedUsers = self::keyById($replacedUsers);
		
		// Nothing should have been updated.
		$this->assertEquals($newUsers, $replacedUsers);
	}
}
