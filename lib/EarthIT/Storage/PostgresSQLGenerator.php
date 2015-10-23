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
	public function dbInternalToExternalValueSql(
		EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc, $dbInternalValueSql='{columnValue}'
	) {
		if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) ) {
			return "ST_AsGeoJSON({$dbInternalValueSql})";
		} else {
			return $dbInternalValueSql;
		}
	}
	
	/**
	 * Return SQL that will encode the db-external value $valueSql into
	 * the form actually stored in the table, e.g. for use in INSERTs
	 */
	public function dbExternalToInternalValueSql(
		EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc, $dbExternalValueSql
	) {
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
	
	public function dbExternalToSchemaItems( array $rows, EarthIT_Schema_ResourceClass $rc ) {
		$itemData = array();
		$fields = EarthIT_Storage_Util::storableFields($rc);
		$fieldColumnNames = array();
		foreach( $fields as $fn=>$f ) {
			$fieldColumnNames[$fn] = $this->dbObjectNamer->getColumnName($rc, $f);
		}
		foreach( $rows as $row ) {
			foreach( $fields as $fn=>$f ) {
				$item[$fn] = $this->dbExternalToSchemaValue($row[$fieldColumnNames[$fn]], $f, $rc);
			}
			$itemData[] = $item;
		}
		return $itemData;
	}
	
	protected static function fss( array $dat, $storableFieldNames ) {
		$k = array_intersect(array_keys($dat), $storableFieldNames);
		sort($k);
		return implode(',',$k);
	}
	
	protected static function ensureSameFieldsGivenForAllItems( array $itemData, array $storableFields ) {
		$fss = null;
		$storableFieldNames = array_keys($storableFields);
		$storedFields = array();
		foreach( $itemData as $item ) {
			$itemFss = self::fss($item, $storableFieldNames);
			if( $fss === null ) {
				// This is the first item; use it as the reference.
				$fss = $itemFss;
				foreach( $item as $fn=>$v ) {
					if( isset($storableFields[$fn]) ) {
						$storedFields[$fn] = $storableFields[$fn];
					}
				}
			} else if( $fss !== $itemFss ) {
				throw new EarthIT_Storage_SaveOptionsUnsupported(
					"Can't bulk insert; not all items provide values for the same set of fields: $fss != $itemFss");
			}
		}
		return $storedFields;
	}
	
	/**
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried; the
	 *   last will be fetchAlled if options.returnSaved is true
	 */
	public function makeBulkSaveQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter, array $options=array() ) {
		if( count($itemData) == 0 ) return array();
		
		$storableFields = EarthIT_Storage_Util::storableFields($rc);
		$fieldsToStore = self::ensureSameFieldsGivenForAllItems( $itemData, $storableFields);
		
		if( count($fieldsToStore) == 0 ) {
			throw new Exception("Can't store no columns.");
		}
		
		$columnNameParams = array();
		$columnNamePlaceholders = array();
		$toStoreColumnNamePlaceholders = array(); // Only the ones we're specifying in our insert
		foreach( $storableFields as $fn=>$f ) {
			$columnNameParam = "c_".($paramCounter++);
			$columnNameParams[$fn] = $columnNameParam;
			$params[$columnNameParam] = new EarthIT_DBC_SQLIdentifier($this->dbObjectNamer->getColumnName($rc, $f));
			$columnNamePlaceholder = "{{$columnNameParam}}";
			$columnNamePlaceholders[$fn] = $columnNamePlaceholder;
			if( isset($fieldsToStore[$fn]) ) $toStoreColumnNamePlaceholders[] = $columnNamePlaceholder;
		}
		foreach( $fieldsToStore as $fn=>$f ) {
			$columnNameParam = $columnNameParams[$fn];
		}
		$valueRows = array();
		foreach( $itemData as $item ) {
			$valueSqls = array();
			foreach( $fieldsToStore as $fn=>$f ) {
				$paramName = "v_".($paramCounter++);
				$valueSqls[] = $this->dbExternalToInternalValueSql($f, $rc, "{{$paramName}}");
				$params[$paramName] = $this->schemaToDbExternalValue(isset($item[$fn]) ? $item[$fn] : null, $f, $rc);
			}
			$valueRows[] = "(".implode(', ', $valueSqls).")";
		}
		
		$sql =
			"INSERT INTO {table}\n".
			"(".implode(", ", $toStoreColumnNamePlaceholders).") VALUES\n".
			implode(",\n", $valueRows);
		
		if( $options['returnSaved'] ) {
			$columnDbExternalValueSqls = array();
			foreach( $storableFields as $fn=>$f ) {
				$t = $this->dbInternalToExternalValueSql($f, $rc, $columnNamePlaceholders[$fn]);
				if( $t != $columnNamePlaceholders[$fn] ) $t .= " AS ".$columnNamePlaceholders[$fn];
				$columnDbExternalValueSqls[$fn] = $t;
			}
			$sql .= "\nRETURNING ".implode(', ',$columnDbExternalValueSqls);
		}
		
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
