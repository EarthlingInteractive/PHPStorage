<?php

/**
 * A comparison op that can be implemented in both PHP and SQL
 * using infix operators (possibly different ones)
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
