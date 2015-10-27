<?php

class EarthIT_Storage_Filter_AndedItemFilter implements EarthIT_Storage_ItemFilter
{
	protected $componentFilters;
	
	public function __construct( array $componentFilters ) {
		$this->componentFilters = $componentFilters;
	}
	
	public function getComponentFilters() {
		return $this->componentFilters;
	}
	
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		if( count($this->componentFilters) == 0 ) return '{'.$params->newParam('truth',true).'}';
		
		$sqlz = array();
		foreach( $this->componentFilters as $f ) {
			$sqlz[] = $f->toSql($tableSql, $dbObjectNamer, $params);
		}
		
		return implode(' AND ',$sqlz);
	}
	
	public function matches( $item ) {
		foreach( $this->componentFilters as $f ) if( !$f->matches($item) ) return false;
		return true;
	}
}
