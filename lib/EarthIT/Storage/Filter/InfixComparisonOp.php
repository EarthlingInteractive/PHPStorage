<?php

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
		return eval("\$valueA {$this->phpOp} \$valueB");
	}
}
