<?php

class EarthIT_Storage_Search
{
	protected $resourceClass;
	protected $filters;
	protected $orderBy;
	protected $skip;
	protected $limit;
	
	public function __construct(
		EarthIT_Schema_ResourceClass $rc,
		array $filters=array(),
		array $orderBy=array(),
		$skip=0,
		$limit=null
	) {
		foreach( $filters as $filter ) {
			if( !($filter instanceof EarthIT_Storage_ItemFilter ) ) {
				throw new Exception("Provided filter is not an ItemFilter: ".var_export($filter,true));
			}
		}
		
		$this->resourceClass = $rc;
		$this->filters = $filters;
		$this->orderBy = $orderBy;
		$this->skip = $skip;
		$this->limit = $limit;
	}
	
	public function __get($k) { return $this->$k; }
}
