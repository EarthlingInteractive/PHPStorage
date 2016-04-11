<?php

/**
 * Generates SQL like (columnA, columnB) IN ((1,2), (3,4), (5,6)).
 * 
 * Not necessarily supported by all databases.
 * 
 * But you have to explicitly construct it, so hopefully that's not a problem.
 */
class EarthIT_Storage_Filter_MultiFieldValuesInListFilter implements EarthIT_Storage_Filter_MultiFieldValueFilter
{
	protected $fields;
	protected $rc;
	protected $valueTuples;
	public function __construct( array $fields, EarthIT_Schema_ResourceClass $rc, array $valueTuples ) {
		$this->fields = $fields;
		$this->rc = $rc;
		$this->valueTuples = $valueTuples;
	}
	public function getFields() { return $this->fields; }
	public function getValueTuples() { return $this->valueTuples; }
	
	/** @override */
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		$columnSqls = array();
		foreach( $this->fields as $field ) {
			$columnName = $dbObjectNamer->getColumnName($this->rc, $field);
			$fieldValueSql = $tableSql.'.{'.$params->bind(new EarthIT_DBC_SQLIdentifier($columnName)).'}';
			$columnSqls[] = $fieldValueSql;
		}
		$valueTupleSqls = array();
		foreach( $this->valueTuples as $valueTuple ) {
			$valueSqls = array();
			foreach( $valueTuple as $value ) {
				$valueSqls[] = '{'.$params->bind($value).'}';
			}
			$valueTupleSqls[] = '('.implode(', ',$valueSqls).')';
		}
		
		return "(".implode(', ',$columnSqls).") IN (".implode(', ',$valueTupleSqls).')';
	}
	
	/** @override */
	public function matches( $item ) {
		foreach( $this->valueTuples as $values ) {
			foreach( $this->fields as $k=>$field ) {
				if( $item[$field->getName()] !== $values[$k] ) continue 2;
			}
			return true;
		}
		return false;
	}
}
