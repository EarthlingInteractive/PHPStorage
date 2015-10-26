<?php

class EarthIT_Storage_Util
{
	public static function defaultSaveItemsOptions(array &$options) {
		if( !isset($options[EarthIT_Storage_ItemSaver::RETURN_SAVED]) ) $options[EarthIT_Storage_ItemSaver::RETURN_SAVED] = false;
		if( !isset($options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY]) ) $options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY] = 'error';
	}

	/**
	 * Get a field property value, taking into account
	 * whether the field is fake or not, and defaults for either case.
	 */
	protected static function fieldPropertyValue( $f, $propUri, $nonFakeDefault=null, $fakeDefault=null ) {
		$v = $f->getFirstPropertyValue($propUri);
		if( $v !== null ) return $v;
		
		$isFake = $f->getFirstPropertyValue(EarthIT_Storage_NS::IS_FAKE_FIELD);
		return $isFake ? $fakeDefault : $nonFakeDefault;
	}
	
	protected static function fieldsWithProperty( array $l, $propUri, $nonFakeDefault=null, $fakeDefault=null ) {
		$filtered = array();
		foreach( $l as $k=>$f ) {
			if( self::fieldPropertyValue($f, $propUri, $nonFakeDefault, $fakeDefault) ) {
				$filtered[$k] = $f;
			}
		}
		return $filtered;
	}
	
	public static function storableFields( EarthIT_Schema_ResourceClass $rc ) {
		return self::fieldsWithProperty($rc->getFields(), EarthIT_Storage_NS::HAS_A_DATABASE_COLUMN, true, false);
	}
	
	/**
	 * @param array $selects array of alias => expression
	 * @return array of select parts [x, y, ...] of 'SELECT x, y, ... FROM yaddah yaddah'
	 */
	public static function formatSelects( array $selects, &$paramCounter=0, array &$params ) {
		$sqlz = array();
		foreach( $selects as $k=>$sel ) {
			$exprParamName = "e_".$paramCounter++;
			$params[$exprParamName] = $sel;
			$aliasParamName = "a_".$paramCounter++;
			$params[$aliasParamName] = new EarthIT_DBC_SQLIdentifier($k);
			$sqlz[] = "{{$exprParamName}} AS {{$aliasParamName}}";
		}
		return $sqlz;
	}
}
