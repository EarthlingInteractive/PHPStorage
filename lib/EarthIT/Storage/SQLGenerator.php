<?php

interface EarthIT_Storage_SQLGenerator
{
	/**
	 * @param rows list of array(column name => DB-external value, ...)
	 * @param EarthIT_Schema_ResourceClass $rc class of items fetched
	 * @return array of schema-form items
	 */
	public function dbExternalToSchemaItems( array $rows, EarthIT_Schema_ResourceClass $rc );
	
	/**
	 * @param array $itemData list of schema-form items' data
	 * @param EarthIT_Schema_ResourceClass $rc class of items being inserted
	 * @param array $options same as $options parameter to ItemSaver#saveItems
	 * @throws EarthIT_Storage_SaveOptionsUnsupported if the option/data combination isn't supported
	 * @return array of EarthIT_DBC_SQLExpressions to be doQueried.
	 */
	public function makeBulkSaveQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, &$paramCounter, array $options=array() );
	
	/**
	 * @param array $filters array of ItemFilters
	 * @return EarthIT_DBC_SQLExpression representing the <x> in 'WHERE <x>' part of the query
	 */
	public function makeFilterSql( array $filters, EarthIT_Schema_ResourceClass $rc, $alias, &$paramCounter );
	
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
