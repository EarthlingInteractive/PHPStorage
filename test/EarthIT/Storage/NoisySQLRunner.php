<?php

class EarthIT_Storage_NoisySQLRunner implements EarthIT_DBC_SQLRunner
{
	protected $backingRunner;
	public function __construct( EarthIT_DBC_SQLRunner $r ) {
		$this->backingRunner = $r;
	}
	
	protected function _noise( $sql ) {
		echo "-- query!\n$sql\n";
	}
	
	protected function noise( $sql, array $params ) {
		$exp = EarthIT_DBC_SQLExpressionUtil::expression($sql,$params);
		$this->_noise(EarthIT_DBC_SQLExpressionUtil::queryToSql($exp, EarthIT_DBC_DebugSQLQuoter::getInstance()));
		
	}
	
	public function fetchRows( $sql, array $params=array() ) {
		$this->noise($sql, $params);
		return $this->backingRunner->fetchRows($sql, $params);
	}
	
	public function doRawQuery( $sql ) {
		$this->_noise($sql);
		$this->backingRunner->doRawQuery($sql);
	}
	
	public function doQuery( $sql, array $params=array() ) {
		$this->noise($sql, $params);
		$this->backingRunner->doQuery($sql, $params);
	}
}
