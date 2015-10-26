<?php

interface EarthIT_Storage_Filter_ComparisonOp
{
	public function toSql( $sqlA, $sqlB );
	/**
	 * @return boolean result of the test
	 */
	public function doComparison( $valueA, $valueB );
}
