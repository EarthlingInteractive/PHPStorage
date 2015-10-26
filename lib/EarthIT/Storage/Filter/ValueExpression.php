<?php

interface EarthIT_Storage_Filter_ValueExpression
{
	/** Return SQL that evaluates to the value */
	public function toSql( EarthIT_DBC_ParamsBuilder $params );
	/** Return the value! */
	public function evaluate();
}
