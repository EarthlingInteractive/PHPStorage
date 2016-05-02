<?php

/**
 * Can be passed as field value to inserts
 * to represent a value already in 'DB internal' form
 * (or a string close enough to be converted by the driver).
 */
class EarthIT_Storage_InternalValue
{
	protected $value;
	public function __construct($v) {
		$this->value = $v;
	}
	public function getValue() {
		return $this->value;
	}
}
