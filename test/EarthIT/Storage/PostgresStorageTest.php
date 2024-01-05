<?php

class EarthIT_Storage_PostgresStorageTest extends EarthIT_Storage_StorageTest
{
	public function setUp() : void {
		parent::setUp();
		$this->storageHelper = $this->registry->postgresStorageHelper;
	}
	
	protected function makeStorage() {
		return $this->registry->postgresStorage;
	}
	
	protected function preallocateEntityIds($count) {
		$this->storageHelper->preallocateEntityIds($count);
	}
	
	protected function newEntityId() {
		return $this->storageHelper->newEntityId();
	}
	
	public function testInsertSimple() {
		$this->preallocateEntityIds(2);
		$entityId0 = $this->newEntityId();
		$entityId1 = $this->newEntityId();
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$oldUserCount = $this->storageHelper->queryValue("SELECT COUNT(*) FROM storagetest.user");
		
		$this->storage->saveItems( array(
			array('ID' => $entityId0, 'username' => 'Bob Hope', 'passhash' => 'asd123'),
			array('ID' => $entityId1, 'username' => 'Bob Jones', 'passhash' => 'asd125'),
		), $userRc);
		
		$newUserCount = $this->storageHelper->queryValue("SELECT COUNT(*) FROM storagetest.user");
		
		$this->assertEquals( $oldUserCount + 2, $newUserCount );
	}
	
	public function testSubItemFiltering() {
		$this->preallocateEntityIds(4);
		$user0Id = $this->newEntityId();
		$user1Id = $this->newEntityId();
		$org0Id = $this->newEntityId();
		$org1Id = $this->newEntityId();

		$userRc = $this->registry->schema->getResourceClass('user');
		$orgRc = $this->registry->schema->getResourceClass('organization');
		$uoaRc = $this->registry->schema->getResourceClass('user organization attachment');
		
		$this->storage->saveItems( array(
			array('ID' => $user0Id, 'username' => 'Test User 0', 'passhash' => 'asdf1234'),
			array('ID' => $user1Id, 'username' => 'Test User 1', 'passhash' => 'asdf1234'),
		), $userRc);
		
		$this->storage->saveItems( array(
			array('ID' => $org0Id, 'name' => 'Test Org 0'),
			array('ID' => $org1Id, 'name' => 'Test Org 1'),
		), $orgRc);
		
		$this->storage->saveITems( array(
			array('user ID' => $user0Id, 'organization ID' => $org0Id),
			array('user ID' => $user0Id, 'organization ID' => $org1Id),
			array('user ID' => $user1Id, 'organization ID' => $org1Id),
		), $uoaRc);
		
		// Let's find orgs that have user 1 in them!
		// Only org 1 should be found.
		
		$filter = new EarthIT_Storage_Filter_SubItemFilter(
			'user organization attachments',
			true,
			new EarthIT_Schema_Reference(
				'user organization attachment', array('ID'), array('organization ID')),
			$orgRc, $uoaRc,
			new EarthIT_Storage_Filter_ExactMatchFieldValueFilter(
				$uoaRc->getField('user ID'),
				$uoaRc,
				$user1Id
			)
		);
		
		$results = $this->storage->searchItems(
			new EarthIT_Storage_Search($orgRc, $filter),
			array(EarthIT_Storage_SQLStorage::DUMP_QUERIES=>false)
		);
		
		$this->assertEquals(1, count($results));
		foreach( $results as $item ) {
			$this->assertEquals($org1Id, $item['ID']);
		}
	}
}
