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
			$item = array();
			foreach( $fields as $fn=>$f ) {
				$item[$fn] = $this->dbExternalToSchemaValue($row[$fieldColumnNames[$fn]], $f, $rc);
			}
			$itemData[] = $item;
		}
		return $itemData;
	}
	
	public function schemaToDbExternalItems( array $itemData, EarthIT_Schema_ResourceClass $rc ) {
		$rows = array();
		$fields = EarthIT_Storage_Util::storableFields($rc);
		$fieldColumnNames = array();
		foreach( $fields as $fn=>$f ) {
			$fieldColumnNames[$fn] = $this->dbObjectNamer->getColumnName($rc, $f);
		}
		foreach( $itemData as $item ) {
			$row = array();
			foreach( $fields as $fn=>$f ) {
				$row[$fieldColumnNames[$fn]] = $this->schemaToDbExternalValue($item[$fn], $f, $rc);
			}
			$rows[] = $row;
		}
		return $rows;
	}
	
	////
	
	protected static function encodeColumnValuePairs( array $columnValues, array $columnParamNames, array $columnValueParamNames ) {
		$parts = array();
		foreach( $columnValues as $columnName=>$val ) {
			$parts[] = "{{$columnParamNames[$columnName]}} = {{$columnValueParamNames[$columnName]}}";
		}
		return $parts;
	}
	
	/** Returns a comma-separated string of field names that are keys of $dat and values of $storableFieldNames */ 
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
	
	protected function defaultFieldValues( EarthIT_Schema_ResourceClass $rc ) {
		$defs = array();
		foreach( $rc->getFields() as $fn=>$f ) {
			// We want there to be entries for all fields that have any
			// default value specified, even if that default value is
			// null.
			// Fields whose values are auto-generated by the database should
			// not have any default value specified (if they do, this would cause problems).
			foreach( $f->getPropertyValues(EarthIT_Storage_NS::DEFAULT_VALUE) as $v ) {
				$defs[$fn] = $v;
			}
		}
		return $defs;
	}

	protected function _bulkPatchQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter, array $options ) {
		switch( $options['onDuplicateKey'] ) {
		case 'skip':
			$update = false;
			$resetUnspecifiedFieldValues = true;
			break;
		case 'replace':
			$update = true;
			$resetUnspecifiedFieldValues = true;
			break;
		case 'update':
			$update = true;
			$resetUnspecifiedFieldValues = false;
			break;
		default:
			throw new Exception($options['onDuplicateKey'].'?');
		}
		
		if( $resetUnspecifiedFieldValues ) {
			foreach( $itemData as &$item ) {
				$item += $this->defaultFieldValues($rc);
			}; unset($item);
			// TODO: Do we need to null out other fields, too?
		}
		
		$rows = $this->schemaToDbExternalItems( $itemData, $rc );
		if( count($itemData) != 1 and $options['returnStored'] ) {
			throw new EarthIT_Storage_SaveOptionsUnsupported(
				"Can't generate returning update queries for any number of items other than 1 (given ".count($itemData).")");
		}
		
		$storableFields = EarthIT_Storage_Util::storableFields($rc);

		$params = array();
		$outputColumnValueSqls = array();
		$inputColumnValueSqls = array();
		$columnParamNames = array();
		foreach( $storableFields as $fn=>$f ) {
			$columnName = $this->dbObjectNamer->getColumnName($rc, $f);
			$columnParamName = "c_".($paramCounter++);
			$columnParamNames[$columnName] = $columnParamName;
			$params[$columnParamName] = new EarthIT_DBC_SQLIdentifier($columnName);
			$fieldColumnNames[$fn] = $columnName;
			
			$columnValueParamName = "v_".($paramCounter++);
			$columnValueParamNames[$columnName] = $columnValueParamName;
			// $params[$columnValueParamName] needs to be set different per item/query
			
			$columnValueSelectSql = $this->dbInternalToExternalValueSql($f, $rc, "{{$columnParamName}}");
			if( $columnValueSelectSql !== "{{$columnParamName}}" ) $columnValueSelectSql .= "{{$columnParamName}}";
			$outputColumnValueSqls[$columnName] = $columnValueSelectSql;
			
			$inputColumnValueSqls[$columnName] = $this->dbExternalToInternalValueSql($f, $rc, "{{$columnValueParamName}}");
		}
		
		$pkFields = array();
		foreach( $rc->getPrimaryKey()->getFieldNames() as $fn ) {
			if( !isset($storableFields[$fn]) ) throw new Exception($rc->getName()." PK field '$fn' not storable!  Can't generate an update query.");
			$pkFields[] = $storableFields[$fn];
			$pkColumnNames[] = $fieldColumnNames[$fn];
		}
		
		$params['table'] = EarthIT_DBC_SQLExpressionUtil::tableExpression($rc, $this->dbObjectNamer);
		$queries = array();
		foreach( $rows as $row ) {
			$rowParams = $params;
			
			$pkColumnValues = array();
			foreach( $pkColumnNames as $columnName ) {
				if( !isset($row[$columnName]) ) {
					// The idea is that this ends up making our query act like a plain old insert,
					// since 'UPDATE ... WHERE {column} = NULL' will match nothing.
					$pkColumnValues[$columnName] = null;
				} else {
					$pkColumnValues[$columnName] = $row[$columnName];
				}
			}
			
			$rowInsertColumnSqls = array();
			$rowInsertValueSqls  = array();
			foreach( $row as $columnName=>$v ) {
				$rowInsertColumnSqls[] = "{{$columnParamNames[$columnName]}}";
				$rowInsertValueSqls[$columnName] = $inputColumnValueSqls[$columnName];
				$rowParams[$columnValueParamNames[$columnName]] = $v;
			}
			
			$conditions = self::encodeColumnValuePairs($pkColumnValues, $columnParamNames, $columnValueParamNames);
			$sets       = self::encodeColumnValuePairs($row           , $columnParamNames, $columnValueParamNames);
			
			$resultSelectSql = implode(', ',$outputColumnValueSqls);
			$sql =
				"WITH los_updatos AS (\n".
				"\t"."UPDATE {table} SET\n".
				"\t\t".implode(",\n\t\t",$sets)."\n".
				"\t"."WHERE ".implode("\n\t  AND",$conditions)."\n".
				"\t"."RETURNING {$resultSelectSql}\n".
				"), los_insertsos AS (\n".
				"\t"."INSERT INTO {table} (".implode(", ",$rowInsertColumnSqls).")\n".
				"\t"."SELECT ".implode(", ",$rowInsertValueSqls)."\n".
				"\t"."WHERE NOT EXISTS ( SELECT * FROM los_updatos )\n".
				"\t"."RETURNING {$resultSelectSql}\n".
				")\n".
				"SELECT * FROM los_updatos UNION ALL SELECT * FROM los_insertsos";
			
			$queries[] = new EarthIT_Storage_StorageQuery($sql, $rowParams, $options['returnSaved']);
		}
		return $queries;
	}
	
	protected function _bulkInsertQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter, $returnSaved ) {
		$storableFields = EarthIT_Storage_Util::storableFields($rc);
		$defaultItem = $this->defaultFieldValues($rc);
		foreach( $itemData as &$item ) {
			$item += $defaultItem;
		}; unset($item);
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
		
		if( $returnSaved ) {
			$columnDbExternalValueSqls = array();
			foreach( $storableFields as $fn=>$f ) {
				$t = $this->dbInternalToExternalValueSql($f, $rc, $columnNamePlaceholders[$fn]);
				if( $t != $columnNamePlaceholders[$fn] ) $t .= " AS ".$columnNamePlaceholders[$fn];
				$columnDbExternalValueSqls[$fn] = $t;
			}
			$sql .= "\nRETURNING ".implode(', ',$columnDbExternalValueSqls);
		}
		
		$params['table'] = EarthIT_DBC_SQLExpressionUtil::tableExpression($rc, $this->dbObjectNamer);
		
		return array( new EarthIT_Storage_StorageQuery($sql, $params, $returnSaved) );
	}
	
	/**
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried; the
	 *   last will be fetchAlled if options.returnSaved is true
	 */
	public function makeBulkSaveQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter, array $options=array() ) {
		if( count($itemData) == 0 ) return array();
		
		EarthIT_Storage_Util::defaultSaveItemsOptions($options);
		
		switch( $options['onDuplicateKey'] ) {
		case 'error': case 'undefined':
			return $this->_bulkInsertQueries($itemData, $rc, $paramCounter, $options['returnSaved']);
		case 'update': case 'replace': case 'skip':
			return $this->_bulkPatchQueries($itemData, $rc, $paramCounter, $options);
		default:
			throw new EarthIT_Storage_SaveOptionsUnsupported(get_class($this).'#'.__FUNCTION__." doesn't support onDuplicateKey={$options['onDuplicateKey']}");
		}
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
