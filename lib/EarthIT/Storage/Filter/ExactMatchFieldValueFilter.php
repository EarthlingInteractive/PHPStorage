<?php

/**
 * This can be done with FieldValueComparisonFilters,
 * but to since this is such a common case, and to cut
 * down on the number of objects we have to make,
 * this gets its own.
 */
class EarthIT_Storage_Filter_ExactMatchFieldValueFilter implements EarthIT_Storage_Filter_FieldValueFilter
{
	protected $rc;
	protected $field;
	protected $value;
	
	public function __construct(
		EarthIT_Schema_Field $field,
		EarthIT_Schema_ResourceClass $rc,
		$value
	) {
		$this->field = $field;
		$this->rc = $rc;
		$this->value = $value;
	}
	
	/** @override */
	public function getField() {
		return $this->field;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	/** @override */
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		$columnName = $dbObjectNamer->getColumnName($this->rc, $this->field);
		$fieldValueSql = $tableSql.'.{'.$params->bind(new EarthIT_DBC_SQLIdentifier($columnName)).'}';
		$valueParamName = $params->bind($this->value);
		return "{$fieldValueSql} = {{$valueParamName}}";
	}
	
	/** @override */
	public function matches( $item ) {
		return $item[$this->getField()->getName()] === $this->value;
	}
}
