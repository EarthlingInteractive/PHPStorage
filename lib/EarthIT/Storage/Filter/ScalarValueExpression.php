<?php

class EarthIT_Storage_Filter_ScalarValueExpression implements EarthIT_Storage_Filter_ValueExpression
{
	protected $value;
	
	public function __construct( $value ) {
		$this->value = $value;
	}
	
	/** @override */
	public function toSql( EarthIT_DBC_ParamsBuilder $params ) {
		return '{'.$params->bind($this->value).'}';
	}
	
	/** @override */
	public function evaluate() {
		return $this->value;
	}
}
