<?php

final class EarthIT_Storage_FieldwiseComparatorComponent
{
	const DIR_ASC  = 'ASC';
	const DIR_DESC = 'DESC';
	
	public static function parse( $c ) {
		if( $c instanceof self ) return $c;
		
		if( is_string($c) ) {
			switch( $c[0] ) {
			case '+': $dir = self::DIR_ASC ; $c = substr($c,1); break;
			case '-': $dir = self::DIR_DESC; $c = substr($c,1); break;
			default : $dir = self::DIR_ASC ;
			}

			return new self( $c, $dir );
		}
		
		throw new Exception("Don't know how to parse ".var_export($c,true)." as a FieldwiseComparatorComponent");
	}
	
	protected $fieldName;
	protected $direction;
	
	public function __construct( $fieldName, $direction=self::DIR_ASC ) {
		$this->fieldName = $fieldName;
		$this->direction = $direction;
	}
	
	public function getFieldName() { return $this->fieldName; }
	public function getDirectionFactor() { return $this->direction == self::DIR_ASC ? 1 : -1; }
	public function getDirection() { return $this->direction; }
}
