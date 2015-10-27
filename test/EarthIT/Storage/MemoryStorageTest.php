<?php

class EarthIT_Storage_MemoryStorageTest extends EarthIT_Storage_StorageTest
{
	protected function makeStorage() {
		return new EarthIT_Storage_MemoryStorage();
	}
}
