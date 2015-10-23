<?php

class EarthIT_Storage_PostgresSQLGenerator implements EarthIT_Storage_SQLGenerator
{
	public static function valuesOfTypeShouldBeSelectedAsJson( EarthIT_Schema_DataType $t ) {
		return $t->getSqlTypeName() == 'JSON' and $t->getPhpTypeName() == 'JSON value';
	}
	
	public static function valuesOfTypeShouldBeSelectedAsGeoJson( EarthIT_Schema_DataType $t ) {
		return
			preg_match('/^(GEOMETRY|GEOGRAPHY)(\(|$)/', $t->getSqlTypeName()) &&
			$t->getPhpTypeName() == 'GeoJSON array';
	}

	/**
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried.
	 */
	public function makeBulkInserts( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter ) {
		throw new Exception(get_class($this)."#".__FUNCTION__." not yet implemented.");
	}
	
	/**
	 * @return array of EarthIT_DBC_SQLExpressions; all but the last will be doQueried.
	 *   The last one will be fetchRowed to get DB-external-form column values for the inserted item.
	 */
	public function makeSingleInsertWithResult( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter ) {
		throw new Exception(get_class($this)."#".__FUNCTION__." not yet implemented.");
	}
	
	/**
	 * @param array $filters array of ItemFilters
	 * @return EarthIT_DBC_SQLExpression representing the <x> in 'WHERE <x>' part of the query
	 */
	public function makeFilterSql( array $filters, EarthIT_Schema_ResourceClass $rc, $alias, &$paramCounter ) {
		throw new Exception(get_class($this)."#".__FUNCTION__." not yet implemented.");
	}
	
	/**
	 * @param array $filters array of ItemFilters
	 * @return EarthIT_DBC_SQLExpression
	 */
	public function makeSearch( array $filters, $offset, $limit, EarthIT_Schema_ResourceClass $rc, &$paramCounter ) {
		throw new Exception(get_class($this)."#".__FUNCTION__." not yet implemented.");
	}
	
	/**
	 * @return EarthIT_DBC_SQLExpression to be used as <x> in SELECT <x>
	 *   to get the DB-external-form value for the given field.
	 */
	public function makeSelectValue( EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc ) {
		throw new Exception(get_class($this)."#".__FUNCTION__." not yet implemented.");
	}
}
