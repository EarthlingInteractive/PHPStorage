<?php

// TODO: Replace with generic SQL-backed storage, taking a separate object to generate queries
class EarthIT_Storage_SQLStorage implements EarthIT_Storage_ItemSaver, EarthIT_Storage_ItemFetcher
{
	protected $schema;
	protected $sqlRunner;
	protected $dbObjectNamer;
	protected $sqlGenerator;
	
	public function __construct(
		EarthIT_Schema $schema,
		EarthIT_DBC_SQLRunner $sqlRunner,
		EarthIT_DBC_Namer $dbObjectNamer,
		EarthIT_Storage_SQLGenerator $sqlGenerator
	) {
		$this->schema = $schema;
		$this->sqlRunner = $sqlRunner;
		$this->dbObjectNamer = $dbObjectNamer;
		$this->sqlGenerator = $sqlGenerator;
	}
	
	/**
	 * Return an EarthIT_DBC_SQLExpression that identifies the table.
	 * If a dbNamespacePath has been configured, it will be part of the expression.
	 */
	protected function rcTableExpression( EarthIT_Schema_ResourceClass $rc ) {
		$components = array();
		foreach( $rc->getDbNamespacePath() as $ns ) {
			$components[] = new EarthIT_DBC_SQLIdentifier($ns);
		}
		$components[] = $this->dbObjectNamer->getTableName($rc);
		return new EarthIT_DBC_SQLNamespacePath($components);
	}
	
	
	//// Conversion
	
	protected function schemaToDbExternal( array $obj, EarthIT_Schema_ResourceClass $rc, array &$params ) {
		$columnNamer = $this->dbObjectNamer;
		$columnValues = array();
		foreach( EarthIT_CMIPREST_Util::storableFields($rc) as $f ) {
			$fieldName = $f->getName();
			if( array_key_exists($fieldName, $obj) ) {
				$value = $obj[$fieldName];
				
				if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) and $value !== null ) {
					$paramName = EarthIT_DBC_ParameterUtil::newParamName('geojson');
					$params[$paramName] = json_encode($value);
					$dbValue = new EarthIT_DBC_BaseSQLExpression("ST_GeomFromGeoJSON({{$paramName}})");
				} else if( self::valuesOfTypeShouldBeSelectedAsJson($f->getType()) and $value !== null ) {
					$dbValue = EarthIT_JSON::prettyEncode($value);
				} else {
					$dbValue = $value;
				}
				$columnValues[$this->fieldDbName($f, $rc)] = $dbValue;
			}
		}
		return $columnValues;
	}
	
	protected function dbExternalToSchema( array $obj, EarthIT_Schema_ResourceClass $rc ) {
		$fieldValues = array();
		foreach( EarthIT_CMIPREST_Util::storableFields($rc) as $f ) {
			$fieldName = $f->getName();
			$columnName = $this->fieldDbName($f, $rc);
			$fieldValues[$f->getName()] = self::dbToPhpValue($f->getType(), $obj[$columnName]);
		}
		return $fieldValues;
	}
	
	//// SQL generation
	
	protected function itemIdToColumnValues( EarthIT_Schema_ResourceClass $rc, $id ) {
		$fieldValues = EarthIT_CMIPREST_Util::idToFieldValues( $rc, $id );
		$columnValues = array();
		$fields = $rc->getFields();
		foreach( $fieldValues as $fieldName=>$value ) {
			$columnValues[$this->fieldDbName($fields[$fieldName], $rc)] = $value;
		}
		return $columnValues;
	}
	
	/**
	 * @param array $columnValues array of column name => column value
	 * @param array &$params column and value parameter values will be put here
	 * @return array of "{col...X} = {val...X}" strings, one per column,
	 *   with {placeholders} corresponding to the keys added to $params
	 */
	protected static function encodeColumnValuePairs( array $columnValues, array &$params ) {
		$parts = array();
		foreach( $columnValues as $colName=>$val ) {
			$cnp = EarthIT_DBC_ParameterUtil::newParamName('col');
			$cvp = EarthIT_DBC_ParameterUtil::newParamName('val');
			$params[$cnp] = new EarthIT_DBC_SQLIdentifier($colName);
			$params[$cvp] = $val;
			$parts[] = "{{$cnp}} = {{$cvp}}";
		}
		return $parts;
	}
	
	//// Do stuff
	
	protected function fetchRows( $sql, array $params ) {
		if( $sql == 'SELECT NOTHING' ) return array();
		$builder = new EarthIT_DBC_DoctrineStatementBuilder($this->dbAdapter);
		$stmt = $builder->makeStatement($sql, $params);
		$stmt->execute();
		return $stmt->fetchAll();
	}
	
	/** Same as fetchRows; just doesn't return anything. */
	protected function doQuery( $sql, array $params ) {
		$this->fetchRows($sql, $params);
	}
	
	//// Build complimicated queries
	
	protected function buildSelects( EarthIT_Schema_ResourceClass $rc, array &$params, $tableAlias=null ) {
		$taPrefix = $tableAlias ? "{$tableAlias}." : '';
		$selects = array();
		foreach( EarthIT_CMIPREST_Util::storableFields($rc) as $f ) {
			$columnName = $this->fieldDbName($f, $rc);
			$columnNameParam = EarthIT_DBC_ParameterUtil::newParamName('c');
			$params[$columnNameParam] = new EarthIT_DBC_SQLIdentifier($columnName);
			if( self::valuesOfTypeShouldBeSelectedAsGeoJson($f->getType()) ) {
				$selects[] = "ST_AsGeoJSON({$taPrefix}{{$columnNameParam}}) AS {{$columnNameParam}}";
			} else {
				$selects[] = "{$taPrefix}{{$columnNameParam}}";
			}
		}
		return $selects;
	}
	
	private function buildSearchSql(
		EarthIT_Schema_ResourceClass $rc,
		EarthIT_CMIPREST_SearchParameters $sp,
		$tableAlias,
		array &$params
	) {
		$fields = $rc->getFields();
		$whereClauses = array();
		$tableExpression = $this->rcTableExpression( $rc );
		$params['table'] = $tableExpression;
		foreach( $sp->getFieldMatchers() as $fieldName => $matcher ) {
			$field = $fields[$fieldName];
			$columnName = $this->fieldDbName($field, $rc);
			$columnParamName = EarthIT_DBC_ParameterUtil::newParamName('column');
			$params[$columnParamName] = new EarthIT_DBC_SQLIdentifier($columnName);
			$columnExpression = "{$tableAlias}.{{$columnParamName}}";
			$matcherSql = $matcher->toSql( $columnExpression, $fields[$fieldName]->getType()->getPhpTypeName(), $params );
			if( $matcherSql === 'TRUE' ) {
				continue;
			} else if( $matcherSql === 'FALSE' ) {
				return array('emptyResultSetGuaranteed' => true);
			}
			$whereClauses[] = $matcherSql;
		}
		
		if( count($orderByComponents = $sp->getOrderByComponents()) > 0 ) {
			$orderBySqlComponents = array();
			foreach( $orderByComponents as $oc ) {
				$orderByColumnParamName = EarthIT_DBC_ParameterUtil::newParamName('orderBy');
				$params[$orderByColumnParamName] = new EarthIT_DBC_SQLIdentifier($this->fieldDbName($oc->getField(), $rc));
				$orderBySqlComponents[] = "{{$orderByColumnParamName}}".($oc->isAscending() ? " ASC" : " DESC");
			}
			$orderBySection = "ORDER BY ".implode(', ',$orderBySqlComponents)."\n";
		} else $orderBySection = '';

		$limitClauseParts = array();
		if( $sp->getLimit() !== null ) $limitClauseParts[] = "LIMIT ".$sp->getLimit();
		if( $sp->getSkip() != 0 ) $limitClauseParts[] = "OFFSET ".$sp->getSkip();
		$limitSection = $limitClauseParts ? implode(' ',$limitClauseParts)."\n" : '';
		
		return array(
			'emptyResultSetGuaranteed' => false,
			'fromSection' => "FROM {table} AS {$tableAlias}\n",
			'whereSection' => $whereClauses ? "WHERE ".implode("\n  AND ",$whereClauses)."\n" : '',
			'orderBySection' => $orderBySection,
			'limitSection' => $limitSection
		);
	}
	
	protected function fieldSelects( $rc, $tableAlias, array &$params ) {
		$selectedThings = array();
		foreach( EarthIT_CMIPREST_Util::storableFields($rc) as $f ) {
			$columnNameParam = EarthIT_DBC_ParameterUtil::newParamName('column');
			$fieldNameParam = EarthIT_DBC_ParameterUtil::newParamName('field');
			$params[$columnNameParam] = new EarthIT_DBC_SQLIdentifier($this->fieldDbName($f, $rc));
			$params[$fieldNameParam] = new EarthIT_DBC_SQLIdentifier($f->getName());
			$selectedThings[] = "{$tableAlias}.{{$columnNameParam}} AS {{$fieldNameParam}}";
		}
		return $selectedThings;
	}
	
	protected function evaluateJohnTree(
		EarthIT_Schema_ResourceClass $rc,
		EarthIT_CMIPREST_SearchParameters $sp,
		array $johns,
		array $branches,
		$path, array &$results
	) {
		foreach( $branches as $k=>$johnTreeNode ) {
			$newJohns = $johns;
			$newJohns[] = $johnTreeNode->getJohn();
			$this->evaluateJohnTree( $rc, $sp, $newJohns, $johnTreeNode->branches, $path.".".$k, $results );
		}
		
		$results[$path] = array();
		
		$aliasNum = 0;
		$alias0 = 'a'.($aliasNum++);

		$params = array();
		$searchQuery = $this->buildSearchSql( $rc, $sp, 'root', $params );
		if( $searchQuery['emptyResultSetGuaranteed'] ) return;
		
		$joins = array();
		if( count($johns) == 0 ) {
			$targetRc = $rc;
			$targetAlias = $alias0;
		} else {
			$originAlias = $alias0;
			foreach( $johns as $j ) {
				$originRc = $j->originResourceClass;
				$targetRc = $j->targetResourceClass;
				$targetAlias = 'a'.($aliasNum++);
				$joinConditions = array();
				for( $li=0; $li<count($j->originLinkFields); ++$li ) {
					$originColumnParam = EarthIT_DBC_ParameterUtil::newParamName('originColumn');
					$targetColumnParam = EarthIT_DBC_ParameterUtil::newParamName('targetColumn');
					$params[$originColumnParam] = new EarthIT_DBC_SQLIdentifier($this->fieldDbName($j->originLinkFields[$li], $originRc));
					$params[$targetColumnParam] = new EarthIT_DBC_SQLIdentifier($this->fieldDbName($j->targetLinkFields[$li], $targetRc));
					$joinConditions[] = "{$targetAlias}.{{$targetColumnParam}} = {$originAlias}.{{$originColumnParam}}";
				}
				$targetTableParam = EarthIT_DBC_ParameterUtil::newParamName('targetTable');
				$params[$targetTableParam] = $this->rcTableExpression($targetRc);
				$joins[] = "JOIN {{$targetTableParam}} AS {$targetAlias} ON ".implode(' AND ',$joinConditions);
				$originAlias = $targetAlias;
			}
		}
		
		// The root table needs to be wrapped
		// into its own sub-query because it can be ordered and limited.
		// We want the order and limit clauses to apply only to the root
		// table, not the things we join to!
		
		$sql =
			"SELECT ".implode(', ', $this->buildSelects($targetRc, $params, $targetAlias))."\n".
			"FROM (\n\t".str_replace("\n","\n\t",
				"SELECT *\n".
				$searchQuery['fromSection'].
				$searchQuery['whereSection'].
				$searchQuery['orderBySection'].
				$searchQuery['limitSection']
			).") AS $alias0\n".
			(count($joins) ? implode("\n",$joins)."\n" : '');
		
		foreach( $this->fetchRows($sql, $params) AS $dbObj ) {
			$results[$path][] = $this->dbExternalToSchema($dbObj, $targetRc);
		}
	}
	
	////
	
	public function search(
		EarthIT_Schema_ResourceClass $rc,
		EarthIT_CMIPREST_SearchParameters $sp,
		array $branches
	) {
		$results = array();
		$this->evaluateJohnTree( $rc, $sp, array(), $branches, 'root', $results );
		return $results;
	}
	
	public function postItem( EarthIT_Schema_ResourceClass $rc, array $itemData ) {
		$tableExpression = $this->rcTableExpression( $rc );

		$params = array();
		$valuex = array();
		$columnValues = $this->schemaToDbExternal($itemData, $rc, $params);
		$columnExpressionList = array();
		foreach( $columnValues as $columnName => $value ) {
			$valueParamName = EarthIT_DBC_ParameterUtil::newParamName('v');
			$valuex[] = "{{$valueParamName}}";
			$params[$valueParamName] = $value;
			$columnExpressionList[] = new EarthIT_DBC_SQLIdentifier($columnName);
			$columnValueList[] = $value;
		}
		
		// TODO: actually determine ID columns
		// So we can validate or something?
		
		$selects = implode(', ',$this->buildSelects($rc, $params));
		$params['table'] = $tableExpression;
		$params['columns'] = $columnExpressionList;
		$rows = $this->fetchRows(
			"INSERT INTO {table}\n".
			"{columns} VALUES\n".
			"(".implode(',',$valuex).")\n".
			"RETURNING {$selects}", $params);
		if( count($rows) != 1 ) {
			throw new Exception("INSERT INTO ... RETURNING returned ".count($rows)." rows; expected exactly 1.");
		}
		foreach( $rows as $row ) {
			return $this->dbExternalToSchema($row, $rc);
		}
	}
	
	protected function doPatchLikeAction( EarthIT_Schema_ResourceClass $rc, $itemId, array $itemData, $merge ) {
		// TODO: Make it work even if the record does not already exist
		
		$idFieldValues = EarthIT_CMIPREST_Util::idToFieldValues( $rc, $itemId );
		$internalValues = EarthIT_CMIPREST_Util::mergeEnsuringNoContradictions( $idFieldValues, $itemData );
		if( !$merge ) {
			// Set other field values to their defaults.
			// Assuming null for now...
			foreach( $rc->getFields() as $fieldName => $field ) {
				if( !isset($internalValues[$fieldName]) ) {
					$internalValues[$fieldName] = null;
				}
			}
		}
		
		$params = array('table' => $this->rcTableExpression($rc));
		$columnValues = $this->schemaToDbExternal($internalValues, $rc, $params);

		$conditions = self::encodeColumnValuePairs($this->schemaToDbExternal($idFieldValues, $rc, $params), $params);
		$sets       = self::encodeColumnValuePairs($columnValues, $params);
		
		$params['columns'] = array();
		$values = array();
		foreach( $columnValues as $colName => $value ) {
			$params['columns'][] = new EarthIT_DBC_SQLIdentifier($colName);
			$valueParamName = EarthIT_DBC_ParameterUtil::newParamName('v');
			$valueSelects[] = "{{$valueParamName}}";
			$params[$valueParamName] = $value;
		}
		
		$selects = implode(', ',$this->buildSelects($rc, $params));
		$sql =
			"WITH los_updatos AS (\n".
			"\t"."UPDATE {table} SET\n".
			"\t\t".implode(",\n\t\t",$sets)."\n".
			"\t"."WHERE ".implode("\n\t  AND",$conditions)."\n".
			"\t"."RETURNING {$selects}\n".
			"), los_insertsos AS (\n".
			"\t"."INSERT INTO {table} {columns}\n".
			"\t"."SELECT ".implode(", ",$valueSelects)."\n".
			"\t"."WHERE NOT EXISTS ( SELECT * FROM los_updatos )\n".
			"\t"."RETURNING {$selects}\n".
			")\n".
			"SELECT * FROM los_updatos UNION ALL SELECT * FROM los_insertsos";
		$rows = $this->fetchRows( $sql, $params );
		if( count($rows) != 1 ) {
			throw new Exception("UPDATE ... RETURNING returned ".count($rows)." rows; expected exactly 1.");
		}
		foreach( $rows as $row ) {
			return $this->dbExternalToSchema($row, $rc);
		}
	}
	
	public function putItem( EarthIT_Schema_ResourceClass $rc, $itemId, array $itemData ) {
		return $this->doPatchLikeAction($rc, $itemId, $itemData, false);
	}
	
	public function patchItem( EarthIT_Schema_ResourceClass $rc, $itemId, array $itemData ) {
		return $this->doPatchLikeAction($rc, $itemId, $itemData, true);
	}
	
	public function deleteItem( EarthIT_Schema_ResourceClass $rc, $itemId ) {
		$idFieldValues = EarthIT_CMIPREST_Util::idToFieldValues( $rc, $itemId );
		$params = array('table' => $this->rcTableExpression($rc));
		$conditions = self::encodeColumnValuePairs($this->schemaToDbExternal($idFieldValues, $rc, $params), $params);
		$sql =
			"DELETE FROM {table}\n".
			"WHERE ".implode("\n  AND ", $conditions);
		$this->fetchRows( $sql, $params );
	}
	
	////
	
	/** @override */
	public function getItems( array $filters, EarthIT_Schema_ResourceClass $rc ) {
		throw new Exception(get_class($this)."#".__FUNCTION__." not yet implemented.");
	}
	
	/** @override */
	public function saveItems(array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array()) {
		EarthIT_Storage_Util::defaultSaveItemsOptions($options);
		
		$counter = 0;
		$queries = $this->sqlGenerator->makeBulkSaveQueries( $itemData, $rc, $counter, $options );
		
		$resultRows = array();
		
		foreach( $queries as $q ) {
			list($sql,$params) = EarthIT_DBC_SQLExpressionUtil::templateAndParamValues($q);
			if( $q->returnsStuff() ) {
				$resultRows = array_merge($resultRows, $this->sqlRunner->fetchRows($sql, $params));
			} else {
				$this->sqlRunner->doQuery($sql, $params);
			}
		}
		
		if( $options['returnSaved'] ) {
			return $this->sqlGenerator->dbExternalToSchemaItems($resultRows, $rc);
		}
	}
}