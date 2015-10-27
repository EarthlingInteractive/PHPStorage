<?php

class EarthIT_Storage_Search
{
	protected $resourceClass;
	protected $filters;
	protected $comparator;
	protected $skip;
	protected $limit;
	
	public function __construct(
		EarthIT_Schema_ResourceClass $rc,
		array $filters=array(),
		EarthIT_Storage_Comparator $comparator=null,
		$skip=0,
		$limit=null
	) {
		foreach( $filters as $filter ) {
			if( !($filter instanceof EarthIT_Storage_ItemFilter ) ) {
				throw new Exception("Provided filter is not an ItemFilter: ".var_export($filter,true));
			}
		}
		
		if( $comparator === null ) {
			$comparator = new EarthIT_Storage_FieldwiseComparator(array());
		}
		
		$this->resourceClass = $rc;
		$this->filters = $filters;
		$this->comparator = $comparator;
		$this->skip = $skip;
		$this->limit = $limit;
	}
	
	public function getResourceClass() { return $this->resourceClass; }
	public function getFilters() { return $this->filters; }
	public function getComparator() { return $this->comparator; }
	public function getSkip() { return $this->skip; }
	public function getLimit() { return $this->limit; }
}
