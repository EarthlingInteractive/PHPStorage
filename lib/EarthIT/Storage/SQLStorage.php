<?php

// TODO: Replace with generic SQL-backed storage, taking a separate object to generate queries
/** @unstable */ 
class EarthIT_Storage_SQLStorage implements EarthIT_Storage_ItemSaver, EarthIT_Storage_ItemSearcher, EarthIT_Storage_ItemDeleter
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
		
	////
	
	/** @override */
	public function searchItems( EarthIT_Storage_Search $search, array $options=array() ) {
		$q = $this->sqlGenerator->makeSearchQuery($search, $options);
		list($sql, $params) = EarthIT_DBC_SQLExpressionUtil::templateAndParamValues($q);
		$rows = $this->sqlRunner->fetchRows($sql,$params);
		return $this->sqlGenerator->dbExternalToSchemaItems($rows, $search->getResourceClass());
	}
	
	/** @override */
	public function deleteItems( EarthIT_Schema_ResourceClass $rc, array $filters ) {
		$params = array();
		$params['table'] = $this->sqlGenerator->rcTableExpression($rc);
		$params['filters'] = $this->sqlGenerator->makeFilterSql( $filters, $rc, "stuff", new EarthIT_DBC_ParamsBuilder($params) );
		$this->sqlRunner->doQuery(
			"DELETE FROM {table} AS stuff\n".
			"WHERE {filters}",
			$params);
	}
	
	/** @override */
	public function saveItems(array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array()) {
		EarthIT_Storage_Util::defaultSaveItemsOptions($options);
		
		$queries = $this->sqlGenerator->makeBulkSaveQueries( $itemData, $rc, $options );
		
		$resultRows = array();
		
		foreach( $queries as $q ) {
			list($sql,$params) = EarthIT_DBC_SQLExpressionUtil::templateAndParamValues($q);
			if( $q->returnsStuff() ) {
				$resultRows = array_merge($resultRows, $this->sqlRunner->fetchRows($sql, $params));
			} else {
				$this->sqlRunner->doQuery($sql, $params);
			}
		}
		
		if( $options[EarthIT_Storage_ItemSaver::RETURN_SAVED] ) {
			return $this->sqlGenerator->dbExternalToSchemaItems($resultRows, $rc);
		}
	}
}
