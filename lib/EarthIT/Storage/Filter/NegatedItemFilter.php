<?php

class EarthIT_Storage_Filter_NegatedItemFilter implements EarthIT_Storage_ItemFilter
{
	protected $negatedFilter;
	
	public function __construct( EarthIT_Storage_ItemFilter $negatedFilter ) {
		$this->negatedFilter = $negatedFilter;
	}
	
	public function getNegatedFilters() {
		return $this->negatedFilters;
	}
	
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		return '('.$this->negatedFilter->toSql($tableSql,$dbObjectNamer,$params).') = false';
	}
	
	public function matches( $item ) {
		return !$this->negatedFilter->matches($item);
	}
}
