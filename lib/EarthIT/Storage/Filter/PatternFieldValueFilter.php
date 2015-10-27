<?php

class EarthIT_Storage_Filter_PatternFieldValueFilter implements EarthIT_Storage_Filter_FieldValueFilter
{
	protected $rc;
	protected $field;
	protected $pattern;
	protected $caseInsensitive;
	
	public function __construct(
		EarthIT_Schema_Field $field,
		EarthIT_Schema_ResourceClass $rc,
		$pattern,
		$caseInsensitive = false
	) {
		$this->field = $field;
		$this->rc = $rc;
		$this->pattern = $caseInsensitive ? strtolower($pattern) : $pattern;
		$this->caseInsensitive = $caseInsensitive;
	}
	
	protected function getRegex() {
		return '#'.str_replace('\*','.*',preg_quote($this->pattern,'#')).'#';
	}
	
	protected function getLikePattern() {
		// Not worrying about escapes!
		return str_replace('*','%',$this->pattern);
	}
	
	public function getField() {
		return $this->field;
	}
	
	/**
	 * @param string $tableSql SQL fragment giving the table name or alias
	 */
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		$columnName = $dbObjectNamer->getColumnName($this->rc, $this->field);
		$fieldValueSql = $tableSql.'.{'.$params->bind(new EarthIT_DBC_SQLIdentifier($columnName)).'}';
		$patternParamName = $params->newParam('pattern', $this->getLikePattern());
		if( $this->caseInsensitive ) {
			$fieldValueSql = "lower($fieldValueSql)"; // Works on Postgres, anyway!
		}
		return "{$fieldValueSql} LIKE {{$patternParamName}}";
	}
	/**
	 * @param array $item schema-form item field values
	 */
	public function matches( $item ) {
		$fieldValue = $item[$this->getField()->getName()];
		if( $this->caseInsensitive ) $fieldValue = strtolower($fieldValue);
		return preg_match($this->getRegex(), $fieldValue);
	}
}
