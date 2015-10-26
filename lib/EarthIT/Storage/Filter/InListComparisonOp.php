<?php

class EarthIT_Storage_Filter_InListComparisonOp implements EarthIT_Storage_Filter_ComparisonOp
{
	private function __construct() { }
	
	public static function getInstance() {
		return new self();
	}
	
	public function toSql( $sqlA, $sqlB ) {
		return "{$sqlA} IN {$sqlB}";
	}

	/**
	 * @return boolean result of the test
	 */
	public function doComparison( $valueA, $valueB ) {
		return in_array($valueA, $valueB);
	}
}
