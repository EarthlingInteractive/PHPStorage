<?php

interface EarthIT_Storage_SQLGenerator
{
	/**
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried.
	 */
	public function makeBulkInserts( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter );
	
	/**
	 * @return array of EarthIT_DBC_SQLExpressions; all but the last will be doQueried.
	 *   The last one will be fetchRowed to get DB-external-form column values for the inserted item.
	 */
	public function makeBulkInserts( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter );
	
	/**
	 * @param array $filters array of ItemFilters
	 * @return EarthIT_DBC_SQLExpression representing the 'WHERE ....' part of the query
	 */
	public function makeWhereClause( array $filters, EarthIT_Schema_ResourceClass $rc, &$paramCounter );
	
	/**
	 * @param array $filters array of ItemFilters
	 * @return EarthIT_DBC_SQLExpression
	 */
	public function makeSearch( array $filters, $offset, $limit, EarthIT_Schema_ResourceClass $rc, &$paramCounter );
	
	/**
	 * @return EarthIT_DBC_SQLExpression to be used as <x> in SELECT <x>
	 *   to get the DB-external-form value for the given field.
	 */
	public function makeSelectValue( EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc );
}
