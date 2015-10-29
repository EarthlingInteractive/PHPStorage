<?php

abstract class EarthIT_Storage_StorageTest extends EarthIT_Storage_TestCase
{
	protected abstract function makeStorage();
	
	protected $storage;
	
	public function setUp() {
		parent::setUp();
		$this->storage = $this->makeStorage();
	}
	
	public function testInsertFullyWithReturn() {
		$this->registry->storageHelper->preallocateEntityIds(2);
		$entityId0 = $this->registry->storageHelper->newEntityId();
		$entityId1 = $this->registry->storageHelper->newEntityId();
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->storage->saveItems( array(
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true));
		
		$this->assertEquals( 2, count($newUsers) );
	}
	
	public function testInsertDefaultIdsWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->storage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true));
		
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
	
	public function testUpsertWithFieldsSpecifiedInWackyOrder() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->storage->saveItems( array(
			array('username' => 'Bob Dylan', 'passhash' => 'asd123'),
		), $userRc, array(
			EarthIT_Storage_ItemSaver::RETURN_SAVED => true,
			EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY => EarthIT_Storage_ItemSaver::ODK_UPDATE
		));
		
		$newUsers = self::keyById($newUsers);
		$bob = EarthIT_Storage_Util::first($newUsers);
		
		$fixedUsers = $this->storage->saveItems( array(array(
			'passhash' => 'asdf1234',
			'ID' => $bob['ID'],
			'username' => 'Bob Marley',
		)), $userRc, array(
			EarthIT_Storage_ItemSaver::RETURN_SAVED => true,
			EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY => EarthIT_Storage_ItemSaver::ODK_UPDATE
		));
		
		$fetchedUsers = $this->storage->searchItems(
			EarthIT_Storage_Util::makeSearch($userRc, array('ID'=>$bob['ID'])));
		$this->assertEquals( array(
			'ID' => $bob['ID'],
			'passhash' => 'asdf1234',
			'username' => 'Bob Marley',
			'e-mail address' => null
		), EarthIT_Storage_Util::first($fetchedUsers));
	}
	
	public function testUpsertWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->storage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true));
		
		$newUsers = self::keyById($newUsers);
		
		foreach( $newUsers as &$newUser ) {
			$newUser['username'] = 'Bob Dole';
		}; unset($newUser);
		
		// Update everyone to be named "Bob Dole"
		// but keep their existing passhash (and, of course, ID)
		$updatedUsers = $this->storage->saveItems(
			$newUsers, $userRc,
			array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true, EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY=>EarthIT_Storage_ItemSaver::ODK_UPDATE)
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
		
		$newUsers = $this->storage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true));
		
		$newUsers = self::keyById($newUsers);
		
		foreach( $newUsers as &$newUser ) {
			$newUser['username'] = 'Bob Dole';
			unset($newUser['passhash']);
		}; unset($newUser);
		
		// Update everyone to be named "Bob Dole"
		// but keep their existing passhash (and, of course, ID)
		$replacedUsers = $this->storage->saveItems(
			$newUsers, $userRc,
			array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true, EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY=>EarthIT_Storage_ItemSaver::ODK_REPLACE)
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
	
	public function testInsertKeepWithReturn() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = $this->storage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true));
		
		$newUsers = self::keyById($newUsers);
		
		$updates = array();
		foreach( $newUsers as $id=>$newUser ) {
			$updates[$id] = array('username'=>'Bob Saget') + $newUser;
			$newUser['username'] = 'Bob Dole';
		}
		
		$replacedUsers = $this->storage->saveItems(
			$updates, $userRc,
			array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true, EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY=>EarthIT_Storage_ItemSaver::ODK_KEEP)
		);
		
		$replacedUsers = self::keyById($replacedUsers);
		
		// Nothing should have been updated.
		$this->assertEquals($newUsers, $replacedUsers);
	}
	
	public function testGetItems() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		// BALEET THEM ALL!
		$this->storage->deleteItems( $userRc, EarthIT_Storage_ItemFilters::emptyFilter() );
		
		// And now there should be ZERO USERS!
		$fetchedUsers = $this->storage->searchItems(new EarthIT_Storage_Search($userRc));
		$this->assertEquals(0, count($fetchedUsers));
		
		$newUsers = $this->storage->saveItems( array(
			array('username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true));
		
		$newUsers = self::keyById($newUsers);
		
		// So now there should be TOO USERS returned when we GET THEM ALL!
		$fetchedUsers = $this->storage->searchItems(new EarthIT_Storage_Search($userRc));
		$this->assertEquals(2, count($fetchedUsers));
	}
	
	public function testGetSpecificItemsWithInFilter() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = self::keyById($this->storage->saveItems( array(
			array('username' => 'Frodo Baggins', 'passhash' => 'asd123'),
			array('username' => 'Jean Wheasler', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true)));
		
		$newUserIds = array_keys($newUsers);
		
		$search = EarthIT_Storage_Util::makeSearch($userRc, 'ID=in:'.implode(',',$newUserIds));
		$foundItems = self::keyById($this->storage->searchItems($search));
		$this->assertEquals($newUsers, $foundItems);
	}

	public function testGetSpecificItemById() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = self::keyById($this->storage->saveItems( array(
			array('username' => 'Frodo Baggins', 'passhash' => 'asd123'),
			array('username' => 'Jean Wheasler', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true)));
		
		$newUserIds = array_keys($newUsers);
		
		$gotUsers = self::keyById(EarthIT_Storage_Util::getItemsById($newUserIds, $userRc, $this->storage));
		$this->assertEquals($newUsers, $gotUsers);
	}
	
	public function testSearchWithOrdering() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = self::keyById($this->storage->saveItems( array(
			array('username' => 'Frodo Baggins', 'passhash' => 'asd123'),
			array('username' => 'Jean Wheasler', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true)));
		
		$newUserIds = array_keys($newUsers);
		
		$search = EarthIT_Storage_Util::makeSearch($userRc, 'ID=in:'.implode(',',$newUserIds), '+ID');
		$gotUsers = self::keyById(EarthIT_Storage_Util::getItemsById($newUserIds, $userRc, $this->storage));
		$this->assertEquals($newUsers, $gotUsers);

		$search = EarthIT_Storage_Util::makeSearch($userRc, 'ID=in:'.implode(',',$newUserIds), '-ID');
		$gotUsers = self::keyById(EarthIT_Storage_Util::getItemsById($newUserIds, $userRc, $this->storage));
		$this->assertEquals(array_reverse($newUsers,true), $gotUsers);
	}

	public function testSearchWithLikePattern() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = self::keyById($this->storage->saveItems( array(
			array('username' => 'Frodo Baggins', 'passhash' => 'asd123'),
			array('username' => 'Jean Wheasler', 'passhash' => 'asd125'),
		), $userRc, array(EarthIT_Storage_ItemSaver::RETURN_SAVED=>true)));
		
		$newUserIds = array_keys($newUsers);
		
		$search = EarthIT_Storage_Util::makeSearch($userRc, 'ID=in:'.implode(',',$newUserIds).'&username=like:*baggins');
		$gotUsers = self::keyById($this->storage->searchItems($search));
		$this->assertEquals(array($newUserIds[0]=>$newUsers[$newUserIds[0]]), $gotUsers);
	}
	
	public function testPatchWithoutPrimaryKey() {
		// Should just act as if onduplicatekey=error
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$newUsers = self::keyById($this->storage->saveItems( array(
			array('username' => 'Frodo Baggins', 'passhash' => 'asd123'),
			array('username' => 'Jean Wheasler', 'passhash' => 'asd125'),
		), $userRc, array(
			EarthIT_Storage_ItemSaver::RETURN_SAVED=>true,
			EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY=>EarthIT_Storage_ItemSaver::ODK_UPDATE
		)));
		
		$this->assertEquals(2, count($newUsers));
	}
}
