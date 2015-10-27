<?php

final class EarthIT_Storage_FieldwiseComparator implements EarthIT_Storage_Comparator
{
	public static function parse( $c ) {
		if( $c instanceof self ) return $c;
		
		if( $c === '' ) $c = array();
		// Careful about exploding zero-length strings, Eugene
		if( is_string($c) ) $c = explode(',', $c);
		
		if( is_array($c) ) {
			foreach( $c as &$component ) {
				$component = EarthIT_Storage_FieldwiseComparatorComponent::parse($component);
			}; unset($component);
			return new self( $c );
		}
		
		throw new Exception("Don't know how to parse ".var_export($c,true)." as a FieldwiseComparator");
	}
	
	protected $components;
	
	public function __construct( array $components ) {
		foreach( $components as $c ) if( !($c instanceof EarthIT_Storage_FieldwiseComparatorComponent) ) {
			throw new Exception(
				"Component passed to FieldwiseComparator constructor is not a FieldwiseComparatorComponent: ".
				var_export($c,true));
		}
		$this->components = $components;
	}
	
	public function getComponents() { return $this->components; }
	
	public function __invoke($itemA, $itemB) {
		foreach( $this->components as $c ) {
			$fn = $c->getFieldName();
			$v = $itemA[$fn] < $itemB[$fn] ? -1 : ($itemA[$fn] > $itemB[$fn] ? 1 : 0);
			if( $v != 0 ) return $v;
		}
	}
}
