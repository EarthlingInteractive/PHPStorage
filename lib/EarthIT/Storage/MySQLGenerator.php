<?php

// TODO: This was copied from PostgresSQLGenerator.
// A lof of the generated stuff won't work with MySQL.
// We'll need to replace RETURNING stuff with separate queries,
// or just disallow returning saved data entirely.

class EarthIT_Storage_MySQLGenerator implements EarthIT_Storage_SQLGenerator
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
	
	/**
	 * Return an EarthIT_DBC_SQLExpression that identifies the table.
	 */
	public function rcTableExpression( EarthIT_Schema_ResourceClass $rc ) {
		$components = array();
		foreach( $rc->getDbNamespacePath() as $ns ) {
			$components[] = new EarthIT_DBC_SQLIdentifier($ns);
		}
		$components[] = new EarthIT_DBC_SQLIdentifier($this->dbObjectNamer->getTableName($rc));
		return new EarthIT_DBC_SQLNamespacePath($components);
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
			return "ST_AsGeoJSON({$dbInternalValueSql}, 15, 2)";
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
		if( $v instanceof EarthIT_DBC_SQLQueryComponent or $v instanceof EarthIT_Storage_InternalValue ) {
			throw new Exception("Shouldn't be trying to convert ".get_class($v)." to DB external value; that conversion should be bypassed.");
		}
		
		if( $v === null ) {
			return null;
		} else if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) or self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) ) {
			return EarthIT_JSON::prettyEncode($v);
		} else {
			return $v;
		}
	}
	
	public function dbExternalToSchemaValue( $v, EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc ) {
		if( $v === null ) {
			return null;
		} else if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) or self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) ) {
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
				$columnName = $fieldColumnNames[$fn];
				if( array_key_exists($columnName,$row) ) {
					$item[$fn] = $this->dbExternalToSchemaValue($row[$columnName], $f, $rc);
				}
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
				if( array_key_exists($fn, $item) ) {
					$row[$fieldColumnNames[$fn]] = $this->schemaToDbExternalValue($item[$fn], $f, $rc);
				}
			}
			$rows[] = $row;
		}
		return $rows;
	}
	
	////
	
	protected function encodeColumnValuePairs(
		array $columnValues, array $columnParamNames, array $columnValueParamNames,
		EarthIT_Schema_ResourceClass $rc, array $fieldsByColumnName
	) {
		$parts = array();
		foreach( $columnValues as $columnName=>$val ) {
			$field = $fieldsByColumnName[$columnName];
			$internalValueSql = $this->dbExternalToInternalValueSql($field, $rc, "{{$columnValueParamNames[$columnName]}}");
			$parts[] = "{{$columnParamNames[$columnName]}} = $internalValueSql";
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
	
	protected function _bulkPatchyQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, array $options ) {
		switch( $options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY] ) {
		case EarthIT_Storage_ItemSaver::ODK_KEEP:
			$doUpdate = false;
			$resetUnspecifiedFieldValues = true;
			break;
		case EarthIT_Storage_ItemSaver::ODK_REPLACE:
			$doUpdate = true;
			$resetUnspecifiedFieldValues = true;
			break;
		case EarthIT_Storage_ItemSaver::ODK_UPDATE:
			$doUpdate = true;
			$resetUnspecifiedFieldValues = false;
			break;
		default:
			throw new Exception($options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY].'?');
		}
		
		$rows = $this->schemaToDbExternalItems( $itemData, $rc );
		
		$storableFields = EarthIT_Storage_Util::storableFields($rc);
		
		$paramCounter = 0;
		$params = array();
		$outputColumnValueSqls = array();
		$inputColumnValueSqls = array();
		$columnParamNames = array();
		$fieldsByColumnName = array();
		foreach( $storableFields as $fn=>$f ) {
			$columnName = $this->dbObjectNamer->getColumnName($rc, $f);
			$fieldsByColumnName[$columnName] = $f;
			$columnParamName = "c_".($paramCounter++);
			$columnParamNames[$columnName] = $columnParamName;
			$params[$columnParamName] = new EarthIT_DBC_SQLIdentifier($columnName);
			$fieldColumnNames[$fn] = $columnName;
			
			$columnValueParamName = "v_".($paramCounter++);
			$columnValueParamNames[$columnName] = $columnValueParamName;
			// $params[$columnValueParamName] needs to be set different per item/query
			
			$columnValueSelectSql = $this->dbInternalToExternalValueSql($f, $rc, "{{$columnParamName}}");
			if( $columnValueSelectSql !== "{{$columnParamName}}" ) $columnValueSelectSql .= "AS {{$columnParamName}}";
			$outputColumnValueSqls[$columnName] = $columnValueSelectSql;
			
			$inputColumnValueSqls[$columnName] = $this->dbExternalToInternalValueSql($f, $rc, "{{$columnValueParamName}}");
		}
		
		$pkFields = array();
		foreach( $rc->getPrimaryKey()->getFieldNames() as $fn ) {
			if( !isset($storableFields[$fn]) ) throw new Exception($rc->getName()." PK field '$fn' not storable!  Can't generate an update query.");
			$pkFields[$fn] = $storableFields[$fn];
			$pkFieldColumnNames[$fn] = $fieldColumnNames[$fn];
		}
		
		$nonPkColumnNames = array();
		foreach( $storableFields as $fn=>$f ) {
			if( isset($pkFields[$fn]) ) continue;
			$nonPkColumnNames[] = $fieldColumnNames[$fn];
		}
		
		$resultSelectSql = implode(', ',$outputColumnValueSqls);
			
		$params['table'] = EarthIT_DBC_SQLExpressionUtil::tableExpression($rc, $this->dbObjectNamer);
		$queries = array();
		foreach( $rows as $row ) {
			if( count($row) == 0 ) {
				throw new Exception("Can't insert zero values");
				// Maybe you can, but I don't know the MySQL syntax for it
			}
			
			$rowParams = $params;
			
			$pkColumnValues = array();
			foreach( $pkFieldColumnNames as $fieldName=>$columnName ) {
				$v = isset($row[$columnName]) ? $row[$columnName] : null;
				// The idea is that this ends up making our query act like a plain old insert,
				// since 'UPDATE ... WHERE {column} = NULL' will match nothing.
				$pkColumnValues[$columnName] = $v;
				$rowParams[$columnValueParamNames[$columnName]] = $v;
			}
			
			$rowInsertColumnSqls = array();
			$rowInsertValueSqls  = array();
			foreach( $row as $columnName=>$v ) {
				$rowInsertColumnSqls[] = "{{$columnParamNames[$columnName]}}";
				$rowInsertValueSqls[$columnName] = $inputColumnValueSqls[$columnName];
				$rowParams[$columnValueParamNames[$columnName]] = $v;
			}
			
			$conditions = $this->encodeColumnValuePairs($pkColumnValues, $columnParamNames, $columnValueParamNames, $rc, $fieldsByColumnName);
			$sets       = $this->encodeColumnValuePairs($row           , $columnParamNames, $columnValueParamNames, $rc, $fieldsByColumnName);
			
			if( $resetUnspecifiedFieldValues ) {
				// Default everything else!
				foreach( $nonPkColumnNames as $columnName ) {
					if( !array_key_exists($columnName, $row) ) {
						$sets[] = "{{$columnParamNames[$columnName]}} = DEFAULT";
					}
				}
			}
			
			if( count($conditions) == 0 ) {
				// Then there's no primary key data to possibly collide with.
				// 'Any existing records?' is always false and this
				// operation just becomes an INSERT.
				$conditions[] = 'FALSE';
			}
			
			$updateyPart = (count($sets) && $doUpdate) ?
				// Select updated records:
				"UPDATE {table} SET\n".
				"\t".implode(",\n\t\t",$sets)."\n".
				"WHERE ".implode("\n\t  AND ",$conditions)."\n".
				"RETURNING {$resultSelectSql}" :
				// If not updating records (either because ODK_KEEP or
				// because there's no non-PK fields being updated),
				// then this will just select whatever existing records:
				"SELECT {$resultSelectSql}\n".
				"FROM {table}\n".
				"WHERE ".implode("\n\t  AND ",$conditions);
			
			$sql =
				"WITH los_updatos AS (\n".
				"\t".str_replace("\n","\n\t",$updateyPart)."\n".
				"), los_insertsos AS (\n".
				"\t"."INSERT INTO {table} (".implode(", ",$rowInsertColumnSqls).")\n".
				"\t"."SELECT ".implode(", ",$rowInsertValueSqls)."\n".
				"\t"."WHERE NOT EXISTS ( SELECT * FROM los_updatos )\n".
				"\t"."RETURNING {$resultSelectSql}\n".
				")\n".
				"SELECT * FROM los_updatos UNION ALL SELECT * FROM los_insertsos";
			
			$queries[] = new EarthIT_Storage_StorageQuery($sql, $rowParams, $options[EarthIT_Storage_ItemSaver::RETURN_SAVED]);
		}
		return $queries;
	}
	
	protected function _bulkInsertQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, $returnSaved ) {
		$storableFields = EarthIT_Storage_Util::storableFields($rc);
		$defaultItem = EarthIT_Storage_Util::defaultItem($rc);
		foreach( $itemData as &$item ) {
			$item += $defaultItem;
		}; unset($item);
		$fieldsToStore = self::ensureSameFieldsGivenForAllItems( $itemData, $storableFields);
		
		$params = array();
		$params['table'] = EarthIT_DBC_SQLExpressionUtil::tableExpression($rc, $this->dbObjectNamer);
		
		$paramCounter = 0;
		$columnNameParams = array();
		$columnNamePlaceholders = array();
		$toStoreColumnNamePlaceholders = array(); // Only the ones we're specifying in our insert
		foreach( $storableFields as $fn=>$f ) {
			$columnNameParam = "c_".($paramCounter++);
			$columnNameParams[$fn] = $columnNameParam;
			$params[$columnNameParam] = new EarthIT_DBC_SQLIdentifier($this->dbObjectNamer->getColumnName($rc, $f));
			$columnNamePlaceholder = "{{$columnNameParam}}";
			$columnNamePlaceholders[$fn] = $columnNamePlaceholder;
		}
		foreach( $fieldsToStore as $fn=>$f ) {
			$columnNameParam = $columnNameParams[$fn];
			$toStoreColumnNamePlaceholders[] = $columnNamePlaceholders[$fn];
		}
		
		$columnDbExternalValueSqls = array();
		if( $returnSaved ) {
			foreach( $storableFields as $fn=>$f ) {
				$t = $this->dbInternalToExternalValueSql($f, $rc, $columnNamePlaceholders[$fn]);
				if( $t != $columnNamePlaceholders[$fn] ) $t .= " AS ".$columnNamePlaceholders[$fn];
				$columnDbExternalValueSqls[$fn] = $t;
			}
		}
		
		if( count($fieldsToStore) == 0 ) {
			if( $returnSaved ) throw new Exception("Returning saved records not supported for MySQL.");
			
			// INSERT INTO ... DEFAULT VALUES doesn't seem to have a bulk form,
			// so we'll have to make multiple queries.
			// Fortunately they're pretty simple queries (actually just the same one repeated).
			return array_fill( 0, count($itemData),
				new EarthIT_Storage_StorageQuery(
					"INSERT INTO {table} DEFAULT VALUES",
					$params, $returnSaved));
		}
		
		$valueRows = array();
		foreach( $itemData as $item ) {
			$valueSqls = array();
			foreach( $fieldsToStore as $fn=>$f ) {
				$paramName = "v_".($paramCounter++);
				
				$value = isset($item[$fn]) ? $item[$fn] : null;
				if( $value instanceof EarthIT_DBC_SQLQueryComponent ) {
					$isAlreadyInternal = true;
				} else if( $value instanceof EarthIT_Storage_InternalValue ) {
					$isAlreadyInternal = true;
					$value = $value->getValue();
				} else {
					$isAlreadyInternal = false;
				}
				
				if( $isAlreadyInternal ) {
					$valueSqls[] = "{{$paramName}}";
					$params[$paramName] = $value;
				} else {
					$valueSqls[] = $this->dbExternalToInternalValueSql($f, $rc, "{{$paramName}}");
					$params[$paramName] = $this->schemaToDbExternalValue($value, $f, $rc);
				}
			}
			$valueRows[] = "(".implode(', ', $valueSqls).")";
		}
		
		$sql =
			"INSERT INTO {table}\n".
			"(".implode(", ", $toStoreColumnNamePlaceholders).") VALUES\n".
			implode(",\n", $valueRows);
		
		if( $returnSaved ) {
			$sql .= "\nRETURNING ".implode(', ',$columnDbExternalValueSqls);
		}
		
		return array( new EarthIT_Storage_StorageQuery($sql, $params, $returnSaved) );
	}
	
	/**
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried; the
	 *   last will be fetchAlled if options.returnSaved is true
	 */
	public function makeBulkSaveQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array() ) {
		if( count($itemData) == 0 ) return array();
		
		EarthIT_Storage_Util::defaultSaveItemsOptions($options);
		
		switch( $options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY] ) {
		case EarthIT_Storage_ItemSaver::ODK_ERROR: case EarthIT_Storage_ItemSaver::ODK_UNDEFINED:
			return $this->_bulkInsertQueries($itemData, $rc, $options[EarthIT_Storage_ItemSaver::RETURN_SAVED]);
		default:
			return $this->_bulkPatchyQueries($itemData, $rc, $options);
		}
	}
	
	public function makeSearchQuery( EarthIT_Storage_Search $search, array $options=array() ) {
		$rc = $search->getResourceClass();
		
		$params = array();
		$PB = new EarthIT_DBC_ParamsBuilder($params);
		$params['table']  = $this->rcTableExpression($rc);
		$conditions = $search->getFilter()->toSql('stuff', $this->dbObjectNamer,$PB);
		
		// TODO: only select certain fields if fieldsOfInterest given
		$selects = $this->makeDbExternalFieldValueSqls(EarthIT_Storage_Util::storableFields($rc), $rc, 'stuff', $PB);
		$selectSqls = EarthIT_Storage_Util::formatSelectComponents($selects, $PB);
		if( count($selectSqls) == 0 ) {
			throw new Exception("Can't select zero stuff.");
		}
		
		$orderBys = array();
		$comparator = $search->getComparator();
		if( $comparator instanceof EarthIT_Storage_FieldwiseComparator ) {
			foreach( $comparator->getComponents() as $cc ) {
				$columnName = $this->dbObjectNamer->getColumnName($rc, $rc->getField($cc->getFieldName()));
				$orderBys[] = '{'.$PB->newParam('c',new EarthIT_DBC_SQLIdentifier($columnName)).'} '.
					$cc->getDirection();
			}
		} else {
			throw new Exception("Don't know how to order based on a ".get_class($comparator));
		}
		
		$skip  = $search->getSkip();
		$limit = $search->getLimit();
		$limitStuff = '';
		if( $limit !== null ) $limitStuff .= "LIMIT ".(int)$limit;
		if( $skip !== null ) $limitStuff .= "OFFSET ".(int)$skip;
		
		return EarthIT_DBC_SQLExpressionUtil::expression(
			"SELECT\n\t".implode(",\n\t", $selectSqls)."\n".
			"FROM {table} AS stuff\n".
			"WHERE {$conditions}\n".
			($orderBys ? "ORDER BY ".implode(', ',$orderBys)."\n" : '').
			$limitStuff,
			$params
		);
	}
	
	public function makeUpdateQueries(
		array $updates, EarthIT_Storage_ItemFilter $filter,
		EarthIT_Schema_ResourceClass $rc, array $options=array()
	) {
		if( count($updates) == 0 ) {
			throw new Exception("Not updating anything!");
			// Maybe we could allow that?  In which case this just becomes a search.
			// Would have to construct the query differently in that case.
		}
		
		// TODO: Mind the options.
		
		// This code was copied from _bulkPatchyQueries
		// and committed as soon as the unit test passed.
		// It's probably a bit of a mess.
		// Refactor it if you want to.
		
		$params = array();
		$PB = new EarthIT_DBC_ParamsBuilder($params);
		$params['table']  = $this->rcTableExpression($rc);
		$conditions = $filter->toSql('stuff', $this->dbObjectNamer, $PB);
		
		$storableFields = EarthIT_Storage_Util::storableFields($rc);
		$outputColumnValueSqls = array();
		$inputColumnValueSqls = array();
		$columnParamNames = array();
		$fieldsByColumnName = array();
		$columnUpdates = array();
		foreach( $storableFields as $fn=>$f ) {
			$columnName = $this->dbObjectNamer->getColumnName($rc, $f);
			$fieldColumnNames[$fn] = $columnName;
			$fieldsByColumnName[$columnName] = $f;
			
			$columnParamName = $PB->newParam('c_');
			$columnParamNames[$columnName] = $columnParamName;
			$params[$columnParamName] = new EarthIT_DBC_SQLIdentifier($columnName);
			
			$columnValueParamName = $PB->newParam("v_");
			$columnValueParamNames[$columnName] = $columnValueParamName;
			if( array_key_exists($fn, $updates) ) {
				$params[$columnValueParamName] = $updates[$fn];
				$columnUpdates[$columnName] = null; // Value never actually gets used kekeke
			}
			
			$columnValueSelectSql = $this->dbInternalToExternalValueSql($f, $rc, "{{$columnParamName}}");
			if( $columnValueSelectSql !== "{{$columnParamName}}" ) $columnValueSelectSql .= "AS {{$columnParamName}}";
			$outputColumnValueSqls[$columnName] = $columnValueSelectSql;
			
			$inputColumnValueSqls[$columnName] = $this->dbExternalToInternalValueSql($f, $rc, "{{$columnValueParamName}}");
		}
		
		$sets = $this->encodeColumnValuePairs($columnUpdates, $columnParamNames, $columnValueParamNames, $rc, $fieldsByColumnName);		

		$sql =
			"UPDATE {table} AS stuff\n".
			"SET\n\t".implode(",\n\t",$sets)."\n".
			"WHERE {$conditions}\n".
			"RETURNING\n\t".implode(",\n\t",$outputColumnValueSqls);
		
		$returnSaved = true; // TODO: Only if requested by options
		
		return array( new EarthIT_Storage_StorageQuery($sql, $params, $returnSaved) );
	}
	
	public function makeDbExternalFieldValueSqls(
		array $fields, EarthIT_Schema_ResourceClass $rc, $tableSql,
		EarthIT_DBC_ParamsBuilder $params
	) {
		$z = array();
		foreach( $fields as $f ) {
			$columnName = $this->dbObjectNamer->getColumnName($rc, $f);
			$columnParamName = $params->newParam('c', new EarthIT_DBC_SQLIdentifier($columnName));
			$z[$columnName] = $this->dbInternalToExternalValueSql( $f, $rc, "{$tableSql}.{{$columnParamName}}" );
		}
		return $z;
	}
}
