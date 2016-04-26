<?php

/**
 * Filter that matches when there exists a related item
 * matching some criteria.
 * @unstable
 */
class EarthIT_Storage_Filter_SubItemFilter implements EarthIT_Storage_ItemFilter
{
	protected $referenceName;
	protected $targetIsPlural;
	protected $reference;
	protected $originRc;
	protected $targetRc;
	protected $targetFilter;
	
	public function __construct(
		$referenceName, $targetIsPlural,
		EarthIT_Schema_Reference $reference,
		EarthIT_Schema_ResourceClass $originRc,
		EarthIT_Schema_ResourceClass $targetRc,
		EarthIT_Storage_ItemFilter $targetFilter
	) {
		$this->referenceName = $referenceName;
		$this->targetIsPlural = $targetIsPlural;
		$this->reference = $reference;
		$this->originRc = $originRc;
		$this->targetRc = $targetRc;
		$this->targetFilter = $targetFilter;
	}
	
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		static $aliasNum;
		// More properly this would be done with a join,
		// but that requires cooperation from code outside this filter.
		// Let's see if we can make this work with sub-selects...
		$table = EarthIT_DBC_SQLExpressionUtil::tableExpression($this->targetRc, $dbObjectNamer);
		$subItemTableSql = "{".$params->newParam('t',$table)."}";
		$subItemAlias = 'subitem'.(++$aliasNum);
		$subItemFilterSql = str_replace("\n","\n\t",$this->targetFilter->toSql( $subItemAlias, $dbObjectNamer, $params ));
		
		$originFieldNames = $this->reference->getOriginFieldNames();
		$targetFieldNames = $this->reference->getTargetFieldNames();
		
		$joinConditionSqls = array();
		for( $i=0; $i<count($targetFieldNames); ++$i ) {
			$targetCol = $dbObjectNamer->getColumnName($this->targetRc, $this->targetRc->getField($targetFieldNames[$i]));
			$originCol = $dbObjectNamer->getColumnName($this->originRc, $this->originRc->getField($originFieldNames[$i]));
			$joinConditionSqls[] =
				"{$subItemAlias}.{".$params->newParam('c',new EarthIT_DBC_SQLIdentifier($targetCol))."} = ".
				"{$tableSql}.{".$params->newParam('c',new EarthIT_DBC_SQLIdentifier($originCol))."}";
		}
		
		$joinConditionSql = implode(" AND ",$joinConditionSqls);
		return "(\n".
			"\tSELECT COUNT(*)\n".
			"\tFROM {$subItemTableSql} AS {$subItemAlias}\n".
			"\tWHERE {$subItemFilterSql} AND {$joinConditionSql}\n".
			") > 0";
	}
	
	public function matches( $item ) {
		if( !array_key_exists($this->referenceName, $item) ) {
			throw new Exception(
				__CLASS__.'#'.__FUNCTION__." can't check item ".
				"because {$this->referenceName} isn't included on it: ".EarthIT_JSON::prettyEncode($item)
			);
		}
		if( $this->targetIsPlural ) {
			$subItems = $item[$this->referenceName];
		} else {
			$subItems = $item[$this->referenceName] === null ? array() : array($item[$this->referenceName]);
		}
		
		foreach( $subItems as $subItem ) {
			if( $this->targetFilter->matches($subItem) ) return true;
		}
		
		return false;
	}
	
	public function getTargetResourceClass() { return $this->targetRc; }
	public function getTargetFilter() { return $this->targetFilter; }
	
	public function __get($k) {
		switch( $k ) {
		case 'targetIsPlural': case 'targetFilter': case 'reference': case 'referenceName':
			return $this->$k;
		}
		
		$meth = 'get'.ucfirst($k);
		if( method_exists($this,$meth) ) return $this->$meth();
		
		throw new Exception("Unrecognized ".get_class($this)." property: '{$k}'");
	}
}
