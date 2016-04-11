<?php

class EarthIT_Storage_Filter_ListValueExpression implements EarthIT_Storage_Filter_ValueExpression
{
	protected $values;
	
	public function __construct( array $values ) {
		$this->values = $values;
	}
	
	/** @override */
	public function toSql( EarthIT_DBC_ParamsBuilder $params ) {
		$s = array();
		foreach( $this->values as $v ) {
			$s[] = '{'.$params->bind($v).'}';
		}
		return '('.implode(', ',$s).')';
	}
	
	public function getValues() {
		return $this->values;
	}
	
	/** @override */
	public function evaluate() {
		return $this->values;
	}
}
