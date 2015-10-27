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
	 * @param array $selects array of alias => SQL expression text
	 * @return array of select parts [x, y, ...] of 'SELECT x, y, ... FROM yaddah yaddah'
	 */
	public static function formatSelectComponents( array $selects, EarthIT_DBC_ParamsBuilder $PB ) {
		$sqlz = array();
		foreach( $selects as $k=>$selSql ) {
			if( is_integer($k) ) {
				$sqlz[] = $selSql;
			} else {
				$aliasParamName = $PB->bind(new EarthIT_DBC_SQLIdentifier($k));
				$sqlz[] = "{$selSql} AS {{$aliasParamName}}";
			}
		}
		return $sqlz;
	}
	
	public static function parseFilter( EarthIT_Schema_ResourceClass $rc, $filterString ) {
		if( $filterString instanceof EarthIT_Storage_Filter ) return $filterString;
		
		$p = explode('=', $filterString, 2);
		if( count($p) != 2 ) throw new Exception("Not enough '='-separated parts in filter string: '$filterString'");
		$field = $rc->getField($p[0]);
		if( $field === null ) throw new Exception("Error while parsing filter string '$filterString': no such field as '{$p[0]}'");
		
		$valueStr = $p[1];
		$valueStrParts = explode(':', $valueStr, 2);
		if( count($valueStrParts) == 1 ) {
			$pattern = $valueStr;
			$scheme = strpos($pattern,'*') === false ? 'eq' : 'like';
		} else {
			$pattern = $valueStrParts[1];
			$scheme = $valueStrParts[0];
		}
		switch( $scheme ) {
		case 'eq':
			$comparisonOp = new EarthIT_Storage_Filter_InfixComparisonOp('===', '=');
			// TODO: convert the value to the proper type
			$vExp = new EarthIT_Storage_Filter_ScalarValueExpression($pattern);
			break;
		case 'in':
			$comparisonOp = EarthIT_Storage_Filter_InListComparisonOp::getInstance();
			$vExp = new EarthIT_Storage_Filter_ListValueExpression(explode(',',$pattern));
			break;
		case 'like':
			return new EarthIT_Storage_Filter_FieldValuePatternFilter($field, $rc, $pattern, true);
		default:
			throw new Exception("Unrecognized pattern scheme: '{$scheme}'");
		}
				
		return new EarthIT_Storage_Filter_FieldValueComparisonFilter($field, $rc, $comparisonOp, $vExp);
	}
	
	public static function makeSearch( EarthIT_Schema_ResourceClass $rc, $filters=array(), $orderBy=array(), $skip=0, $limit=null, array $options=array() ) {
		if( is_string($filters) ) $filters = explode('&',$filters);
		
		foreach( $filters as &$filter ) {
			$filter = self::parseFilter($rc, $filter);
		}; unset($filter);
		
		$comparator = EarthIT_Storage_FieldwiseComparator::parse($orderBy);
		
		return new EarthIT_Storage_Search($rc, $filters, $comparator, $skip, $limit, $options);
	}
}
