<?php

class EarthIT_Storage_PostgresConnectionTest extends EarthIT_Storage_DBConnectionTest
{
	protected function getStorageHelper() {
		return $this->registry->postgresStorageHelper;
	}

	/**
	 * Make sure INSERT .... RETURNING works like I expect.
	 * 
	 * Note: RETURNING (1, 2) is different than RETURNING 1, 2
	 * (and SELECT works the same way)
	 */
	public function testReturning() {
		$sql =
			"INSERT INTO storagetest.user (username) VALUES\n".
			"('bob'), ('fred')\n".
			"RETURNING id, username\n";
		$rows = $this->getStorageHelper()->queryRows($sql);
		$this->assertEquals(2, count($rows));
		foreach( $rows as $row ) {
			$this->assertEquals( 2, count($row) );
		}
	}
}
