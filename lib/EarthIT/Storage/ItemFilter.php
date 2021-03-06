<?php

interface EarthIT_Storage_ItemFilter
{
	/**
	 * @param string $tableSql SQL fragment giving the table name or alias
	 */
	public function toSql( $tableSql, EarthIT_DBC_Namer $dbObjectNamer, EarthIT_DBC_ParamsBuilder $params );
	/**
	 * @param array $item schema-form item field values
	 */
	public function matches( $item );
}
