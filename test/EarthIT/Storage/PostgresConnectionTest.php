<?php

class EarthIT_Storage_PostgresConnectionTest extends EarthIT_Storage_DBConnectionTest
{
	protected function getStorageHelper() {
		return $this->registry->postgresStorageHelper;
	}
}
