<?php

class EarthIT_Storage_Component
{
	protected $registry;
	
	public function __construct( EarthIT_Storage_TestRegistry $reg ) {
		$this->registry = $reg;
	}
}
