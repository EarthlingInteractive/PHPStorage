<?php

class EarthIT_Storage_UtilTest extends EarthIT_Storage_TestCase
{
	public function testIdRegex1() {
		$rc = $this->rc('thing with arbitrary string in primary key');
		$regex = EarthIT_Storage_Util::itemIdRegex($rc, true);
		$this->assertEquals('^([A-Fa-f0-9]{40})-(.*)-([A-Fa-f0-9]{40})$', $regex);
	}
	
	public function testIdRegex1b() {
		$rc = $this->rc('thing with arbitrary string in primary key');
		$e = null;
		try {
			$regex = EarthIT_Storage_Util::itemIdRegex($rc, false);
		} catch( Exception $e ) {
		}
		$this->assertNotNull($e);
	}
	
	public function testIdRegex2() {
		$rc = $this->rc('thing with multiple arbitrary strings in primary key');
		$e = null;
		try {
			$regex = EarthIT_Storage_Util::itemIdRegex($rc, true);
		} catch( Exception $e ) {
		}
		$this->assertNotNull($e);
	}
}
