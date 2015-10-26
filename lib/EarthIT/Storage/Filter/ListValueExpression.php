<?php

class EarthIT_Storage_Filter_ListValueExpression implements EarthIT_Storage_Filter_ValueExpression
{
	protected $value;
	
	public function __construct( array $value ) {
		$this->value = $value;
	}
	
	/** @override */
	public function toSql( EarthIT_DBC_ParamsBuilder $params ) {
		$s = array();
		foreach( $this->value as $v ) {
			$s[] = '{'.$params->bind($v).'}';
		}
		return '('.implode(', ',$s).')';
	}
	
	/** @override */
	public function evaluate() {
		return $this->value;
	}
}
