<?php

class EarthIT_Storage_Filter_NullValueExpression implements EarthIT_Storage_Filter_ValueExpression
{
	protected static $instance;
	public static function getInstance() {
		if( !self::$instance ) self::$instance = new self();
		return self::$instance;
	}
	
	protected function __construct() {}
	
	/** @override */
	public function toSql( EarthIT_DBC_ParamsBuilder $params ) {
		return 'NULL';
	}
	
	/** @override */
	public function evaluate() {
		return null;
	}
}
