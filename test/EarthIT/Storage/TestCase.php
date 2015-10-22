<?php

abstract class EarthIT_Storage_TestCase extends PHPUnit_Framework_TestCase
{
	protected $registry;
	public function setUp() {
		global $EarthIT_Storage_TestRegistry;
		$this->registry = $EarthIT_Storage_TestRegistry;
	}
}
