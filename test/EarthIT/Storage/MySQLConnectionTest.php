<?php

class EarthIT_Storage_MySQLConnectionTest extends EarthIT_Storage_DBConnectionTest
{
	protected function getStorageHelper() {
		return $this->registry->mysqlStorageHelper;
	}
}
