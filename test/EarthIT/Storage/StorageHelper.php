<?php

class EarthIT_Storage_StorageHelper extends EarthIT_Storage_Component
{
	//// Basic query stuff

	public function doQuery($sql, $params=array()) {
		list($sql,$params) = EarthIT_DBC_SQLExpressionUtil::templateAndParamValues($sql, $params);
		$this->registry->sqlRunner->doQuery($sql, $params);
	}
	
	protected function _queryRows($sql, array $params=array()) {
		list($sql,$params) = EarthIT_DBC_SQLExpressionUtil::templateAndParamValues($sql, $params);
		return $this->registry->sqlRunner->fetchRows($sql, $params);
	}
	
	public function queryRow($sql, array $params=array()) {
		foreach( $this->_queryRows($sql,$params) as $row ) return $row;
	}
	
	public function queryRows($sql, array $params=array(), $keyBy=null) {
		$data = $this->_queryRows($sql, $params);
		if( $keyBy === null ) return $data;
		
		if( !is_string($keyBy) ) throw new Exception("keyBy parameter to queryRows, if specified must be a string.");
		
		$keyed = array();
		foreach( $data as $r ) {
			$keyed[$r[$keyBy]] = $r;
		}
		return $keyed;
	}
	public function queryValue($sql, array $params=array()) {
		foreach( $this->_queryRows($sql,$params) as $row ) {
			foreach( $row as $v ) return $v;
		}
		return null;
	}
	public function queryValueSet($sql, array $params=array()) {
		$set = array();
		foreach( $this->_queryRows($sql, $params) as $row ) {
			if( count($row) > 1 ) {
				throw new Exception("Query returns more than one column; queryValueSet is ambiguous: $sql");
			}
			foreach( $row as $v ) $set[$v] = $v;
		}
		return $set;
	}
	
	////
	
	protected $neededEntityIds = 0;
	public function preallocateEntityIds($count) {
		$this->neededEntityIds += $count;
	}
	
	protected $entityIdPool = array();
	/** Add $count new entity IDs to $this->entityIdPool */
	protected function allocateEntityIds($count) {
		$this->entityIdPool = array_merge($this->entityIdPool, $this->queryValueSet(
			"SELECT nextval({seq}) AS id\n".
			"FROM generate_series(1,{count})", array(
				'count'=>$count, 'seq'=>'storagetest.newentityid'
			)));
	}
	
	protected function finishPreallocatingEntityIds() {
		$this->allocateEntityIds($this->neededEntityIds);
		$this->neededEntityIds = 0;
	}
	
	public function newEntityId() {
		if( $this->neededEntityIds < 1 ) $this->preallocateEntityIds(1);
		$this->finishPreallocatingEntityIds();
		return array_shift($this->entityIdPool);
	}
}
