<?php

class EarthIT_Storage_Filter_FieldValueComparisonFilter implements EarthIT_Storage_Filter_FieldValueFilter
{
	protected $rc;
	protected $field;
	protected $comparisonOp;
	protected $valueExpression;
	
	// Caches the result of expression->evaluate();
	protected $value = null;
	protected $valueEvaluated = false;
	
	public function __construct(
		EarthIT_Schema_Field $field,
		EarthIT_Schema_ResourceClass $rc,
		EarthIT_Storage_Filter_ComparisonOp $op,
		EarthIT_Storage_Filter_ValueExpression $vExp
	) {
		$this->field = $field;
		$this->rc = $rc;
		$this->comparisonOp = $op;
		$this->valueExpression = $vExp;
	}
	
	public function getField() { return $this->field; }
	public function getComparisonOp() { return $this->comparisonOp; }
	public function getValueExpression() { return $this->valueExpression; }
	
	/**
	 * @param string $tableSql SQL fragment giving the table name or alias
	 */
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params ) {
		$columnName = $dbObjectNamer->getColumnName($this->rc, $this->field);
		$fieldValueSql = $tableSql.'.{'.$params->bind(new EarthIT_DBC_SQLIdentifier($columnName)).'}';
		$valueValueSql = $this->valueExpression->toSql($params);
		return $this->comparisonOp->toSql( $fieldValueSql, $valueValueSql );
	}
	/**
	 * @param array $item schema-form item field values
	 */
	public function matches( $item ) {
		if( !$this->valueEvaluated ) {
			$this->value = $this->valueExpression->evaluate();
			$this->valueEvaluated = true;
		}
		$fieldValue = $item[$this->getField()->getName()];
		return $this->comparisonOp->doComparison($fieldValue, $this->value);
	}
}
