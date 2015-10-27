<?php

/**
 * Static functions for constructing item filters.
 */
class EarthIT_Storage_ItemFilters
{
	public static function emptyFilter() {
		return new EarthIT_Storage_Filter_AndedItemFilter(array());
	}
	
	public static function parse( $filterString, EarthIT_Schema_ResourceClass $rc ) {
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
