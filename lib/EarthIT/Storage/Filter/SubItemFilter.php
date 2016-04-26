<?php

/**
 * Filter that matches when there exists a related item
 * matching some criteria.
 * @unstable
 */
class EarthIT_Storage_Filter_SubItemFilter implements EarthIT_Storage_ItemFilter
{
	protected $refName;
	protected $refIsPlural;
	protected $ref;
	protected $originRc;
	protected $targetRc;
	protected $targetFilter;
	
	public function __construct(
		$refName, $refIsPlural,
		EarthIT_Schema_Reference $ref,
		EarthIT_Schema_ResourceClass $originRc,
		EarthIT_Schema_ResourceClass $targetRc,
		EarthIT_Storage_ItemFilter $targetFilter
	) {
		$this->refName = $refName;
		$this->refIsPlural = $refIsPlural;
		$this->ref = $ref;
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
		
		$originFieldNames = $this->ref->getOriginFieldNames();
		$targetFieldNames = $this->ref->getTargetFieldNames();
		
		$joinConditionSqls = [];
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
		if( !array_key_exists($this->refName, $item) ) {
			throw new Exception(
				__CLASS__.'#'.__FUNCTION__." can't check item ".
				"because {$this->refName} isn't included on it: ".EarthIT_JSON::prettyEncode($item)
			);
		}
		if( $this->refIsPlural ) {
			$subItems = $item[$this->refName];
		} else {
			$subItems = $item[$this->refName] === null ? [] : [$item[$this->refName]];
		}
		
		foreach( $subItems as $subItem ) {
			if( $this->targetFilter->matches($subItem) ) return true;
		}
		
		return false;
	}
}
