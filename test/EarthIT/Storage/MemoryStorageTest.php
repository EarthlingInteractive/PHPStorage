<?php

class EarthIT_Storage_MemoryStorageTest extends EarthIT_Storage_StorageTest
{
	protected $nextEntityId = 1;
	
	protected function preallocateEntityIds($count) {
		// Don't care!
	}
	
	protected function newEntityId() {
		return $this->nextEntityId++;
	}
	
	protected function makeStorage() {
		return new EarthIT_Storage_MemoryStorage();
	}
}
