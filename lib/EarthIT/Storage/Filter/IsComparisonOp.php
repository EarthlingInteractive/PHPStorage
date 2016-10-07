<?php

/**
 * SQL: x IS y.
 * PHP: x === y
 * meant to be used to compare values to null.
 */
class EarthIT_Storage_Filter_InfixComparisonOp implements EarthIT_Storage_Filter_ComparisonOp
{
	protected $phpOperatorSymbol;
	protected $sqlOperatorSymbol;
	
	public function __construct( $phpOperatorSymbol, $sqlOperatorSymbol ) {
		$this->phpOperatorSymbol = $phpOperatorSymbol;
		$this->sqlOperatorSymbol = $sqlOperatorSymbol;
	}
	
	public function toSql( $sqlA, $sqlB ) {
		return "{$sqlA} {$this->sqlOperatorSymbol} {$sqlB}";
	}

	/**
	 * @return boolean result of the test
	 */
	public function doComparison( $valueA, $valueB ) {
		return eval("return \$valueA {$this->phpOperatorSymbol} \$valueB;");
	}
}
