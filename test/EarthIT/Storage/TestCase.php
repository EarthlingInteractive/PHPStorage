<?php
use PHPUnit\Framework\TestCase;

abstract class EarthIT_Storage_TestCase extends TestCase
{
	protected $registry;
	public function setUp() : void {
		global $EarthIT_Storage_TestRegistry;
		$this->registry = $EarthIT_Storage_TestRegistry;
	}
	
	protected function rc($name) {
		return $this->registry->schema->getResourceClass($name);
	}
}
