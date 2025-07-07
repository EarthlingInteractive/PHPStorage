<?php

class EarthIT_Storage_Search
{
	protected $resourceClass;
	protected $filter;
	protected $comparator;
	protected $skip;
	protected $limit;
	
	public function __construct(
		EarthIT_Schema_ResourceClass $rc,
		EarthIT_Storage_ItemFilter|null $filter=null,
		EarthIT_Storage_Comparator|null $comparator=null,
		$skip=0,
		$limit=null
	) {
		if( $filter === null ) {
			$filter = EarthIT_Storage_ItemFilters::emptyFilter();
		}
		if( $comparator === null ) {
			$comparator = new EarthIT_Storage_FieldwiseComparator(array());
		}
		
		$this->resourceClass = $rc;
		$this->filter = $filter;
		$this->comparator = $comparator;
		$this->skip = $skip;
		$this->limit = $limit;
	}
	
	public function getResourceClass() { return $this->resourceClass; }
	public function getFilter() { return $this->filter; }
	public function getComparator() { return $this->comparator; }
	public function getSkip() { return $this->skip; }
	public function getLimit() { return $this->limit; }
}
