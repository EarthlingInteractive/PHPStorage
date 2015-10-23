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
	
	protected $dbObjectNamer;
	
	public function __construct(
		EarthIT_DBC_Namer $dbObjectNamer
	) {
		$this->dbObjectNamer = $dbObjectNamer;
	}

	//// DB external <-> DB internal value SQL mapping
	
	/**
	 * Return SQL that will decode the db-internal value $dbInternalValueSql to
	 * its external form that we can make sense of, e.g. for use in SELECTs
	 */
	public function dbExternalValueSql( EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc, $dbInternalValueSql='{columnValue}' ) {
		if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) ) {
			return "ST_AsGeoJSON({$dbInternalSql})";
		} else {
			return $dbInternalSql;
		}
	}
	
	/**
	 * Return SQL that will encode the db-external value $valueSql into
	 * the form actually stored in the table, e.g. for use in INSERTs
	 */
	public function dbInternalValueSql( EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc, $dbExternalValueSql ) {
		if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) ) {
			return "ST_GeomFromGeoJSON({$dbExternalValueSql})";
		} else {
			return $dbExternalValueSql;
		}
	}
	
	//// schema <-> DB external value mapping
	
	public function schemaToDbExternalValue( $v, EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc ) {
		if( $v === null ) {
			return null;
		} else if( self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) or self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) ) {
			return EarthIT_JSON::prettyEncode($v);
		} else {
			return $v;
		}
	}
	
	public function dbExternalToSchemaValue( $v, EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc ) {
		if( $v === null ) {
			return null;
		} else if( self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) or self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) ) {
			return EarthIT_JSON::decode($v);
		} else {
			return $v;
		}
	}
	
	/**
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried.
	 */
	public function makeBulkInserts( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter ) {
		if( count($itemData) == 0 ) return array();
		
		$fieldsToStore = EarthIT_Storage_Util::storableFields($rc);
		$columnNames = array();
		foreach( $fieldsToStore as $fn=>$f ) {
			$columnNames[] = $this->dbObjectNamer->getColumnName($rc, $f);
		}
		$valueRows = array();
		foreach( $itemData as $item ) {
			$valueSqls = array();
			foreach( $fieldsToStore as $fn=>$f ) {
				$paramName = "v_".($paramCounter++);
				$valueSqls[] = $this->dbInternalValueSql($f, $rc, "{{$paramName}}");
				$params[$paramName] = $this->schemaToDbExternalValue(isset($item[$fn]) ? $item[$fn] : null, $f, $rc);
			}
			$valueRows[] = "(".implode(', ', $valueSqls).")";
		}
		
		$sql =
			"INSERT INTO {table}\n".
			"(".implode(", ", $columnNames).") VALUES\n".
			implode(",\n", $valueRows);
		$params['table'] = EarthIT_DBC_SQLExpressionUtil::tableExpression($rc, $this->dbObjectNamer);
		
		return array( EarthIT_DBC_SQLExpressionUtil::expression($sql, $params) );
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
