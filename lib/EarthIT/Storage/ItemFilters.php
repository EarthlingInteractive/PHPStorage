<?php

/**
 * Static functions for constructing item filters.
 */
class EarthIT_Storage_ItemFilters
{
	public static function ored( array $filters ) {
		if( count($filters) === 1 ) return EarthIT_Storage_Util::first($filters);
		return new EarthIT_Storage_Filter_OredItemFilter($filters);
	}
	
	public static function anded( array $filters ) {
		if( count($filters) === 1 ) return EarthIT_Storage_Util::first($filters);
		return new EarthIT_Storage_Filter_AndedItemFilter($filters);
	}

	/**
	 * A filter that matches everything!
	 */
	public static function emptyFilter() {
		return self::anded(array());
	}
	
	public static function byId( $ids, EarthIT_Schema_ResourceClass $rc ) {
		if( $ids === '' ) $ids = array();
		if( is_string($ids) ) $ids = array($ids);
		if( !is_array($ids) ) {
			throw new Exception("'\$ids' parameter must be a string or array");
		}
		
		// TODO: If just a single ID field, use an IN (...) instead
		
		$filters = array();
		foreach( $ids as $id ) {
			$filters[] = self::exactFieldValues( EarthIT_Storage_Util::itemIdToFieldValues($id, $rc), $rc );
		}
		
		// It can be any of those IDs!
		return self::ored($filters);
	}
	
	public static function exactFieldValues( array $fieldValues, EarthIT_Schema_ResourceClass $rc ) {
		$fields = $rc->getFields();
		$filters = array();
		foreach( $fieldValues as $fn=>$v ) {
			$filters[] = new EarthIT_Storage_Filter_ExactMatchFieldValueFilter($fields[$fn], $rc, $v);
		}
		return self::anded($filters);
	}
	
	public static function fieldValueFilter( $scheme, $pattern, EarthIT_Schema_Field $field, EarthIT_Schema_ResourceClass $rc ) {
		switch( $scheme ) {
		case 'eq':
			$value = EarthIT_Storage_Util::cast($pattern, $field->getType()->getPhpTypeName());
			return new EarthIT_Storage_Filter_ExactMatchFieldValueFilter($field, $rc, $value);
		case 'in':
			$values = array();
			foreach( explode(',',$pattern) as $p ) {
				$values[] = EarthIT_Storage_Util::cast($p, $field->getType()->getPhpTypeName());
			}
			$comparisonOp = EarthIT_Storage_Filter_InListComparisonOp::getInstance();
			$vExp = new EarthIT_Storage_Filter_ListValueExpression($values);
			break;
		case 'like':
			return new EarthIT_Storage_Filter_PatternFieldValueFilter($field, $rc, $pattern, true);
		default:
			throw new Exception("Unrecognized pattern scheme: '{$scheme}'");
		}
		
		return new EarthIT_Storage_Filter_FieldValueComparisonFilter($field, $rc, $comparisonOp, $vExp);
	}
	
	public static function parse( $filterString, EarthIT_Schema_ResourceClass $rc, array $nameMap=array() ) {
		if( $filterString instanceof EarthIT_Storage_ItemFilter ) return $filterString;
		
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
		
		return self::fieldValueFilter( $scheme, $pattern, $field, $rc );
	}
	
	public static function parseMulti( $filters, EarthIT_Schema_ResourceClass $rc ) {
		if( $filters === '' ) return self::emptyFilter();
		if( $filters instanceof EarthIT_Storage_ItemFilter ) return $filters;
		
		if( is_string($filters) ) {
			$filters = array_map('urldecode', explode('&', $filters));
		}
		if( !is_array($filters) ) {
			throw new Exception("'\$filters' parameter must be a string, an array, or an ItemFilter.");
		}
		
		foreach( $filters as &$f ) $f = self::parse($f, $rc); unset($f);
		
		if( count($filters) == 1 ) return EarthIT_Storage_Util::first($filters);
		return new EarthIT_Storage_Filter_AndedItemFilter($filters);
	}
}
