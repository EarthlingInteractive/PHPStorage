<?php

/**
 * An EarthIT_DBC_SQLExpression that knows whether or not you're
 * supposed to use its result (fetchRows) instead of just executing it
 * for its side-effects (doQuery)
 */
class EarthIT_Storage_StorageQuery extends EarthIT_DBC_BaseSQLExpression
{
	protected $returnsStuff;
	
	public function returnsStuff() {
		return $this->returnsStuff;
	}
	
	public function __construct( $sql, array $params, $returnsStuff ) {
		parent::__construct($sql, $params);
		$this->returnsStuff = $returnsStuff;
	}
}
