<?php

interface EarthIT_Storage_SQLGenerator
{
	/**
	 * @return EarthIT_DBC_SQLExpression
	 */
	public function rcTableExpression( EarthIT_Schema_ResourceClass $rc );
	
	/**
	 * @param rows list of array(column name => DB-external value, ...)
	 * @param EarthIT_Schema_ResourceClass $rc class of items fetched
	 * @return array of schema-form items
	 */
	public function dbExternalToSchemaItems( array $rows, EarthIT_Schema_ResourceClass $rc );

	/**
	 * Return SQL that will decode the db-internal value $dbInternalValueSql to
	 * its external form that we can make sense of, e.g. for use in SELECTs
	 *
	 * This is suitable for fields where there is a 1-1 mapping between
	 * internal and external forms.
	 *
	 * @param EarthIT_Schema_Field $f
	 * @param EarthIT_Schema_ResourceClass $rc
	 * @param $dbInternalValueSql SQL to retrieve internal value
	 */
	public function dbInternalToExternalValueSql( EarthIT_Schema_Field $f, EarthIT_Schema_ResourceClass $rc, $dbInternalValueSql );
	
	/**
	 * @param array $itemData list of schema-form items' data
	 * @param EarthIT_Schema_ResourceClass $rc class of items being inserted
	 * @param array $options same as $options parameter to ItemSaver#saveItems
	 * @throws EarthIT_Storage_SaveOptionsUnsupported if the option/data combination isn't supported
	 * @return array of EarthIT_Storage_StorageQueries to be doQueried/fetchRowsed.
	 */
	public function makeBulkSaveQueries( array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array() );
	
	/**
	 * @param array $filters array of ItemFilters
	 * @param EarthIT_Schema_ResourceClass $rc
	 * @param string $tableSql SQL text indicating the table or alias to filter
	 * @param EarthIT_DBC_ParamsBuilder
	 * @return string SQL text representing the <x> in 'WHERE <x>' part of the query
	 */
	public function makeFilterSql( array $filters, EarthIT_Schema_ResourceClass $rc, $tableSql, EarthIT_DBC_ParamsBuilder $params );
	
	/**
	 * Generate a query that will return a bunch of rows in DB-external form
	 *
	 * @param EarthIT_Storage_Search $search the search
	 * @param array $options array of
	 *   fieldsOfInterest => array of names of fields that you care about
	 *     (if not specified, the returned query gets all of them) 
	 * @return EarthIT_DBC_SQLExpression
	 */ 
	public function makeSearchQuery( EarthIT_Storage_Search $search, array $options=array() );
	
	/**
	 * Make select parts (suitable for passing to Util::formatSelects)
	 * to get all the specified fields.
	 * 
	 * Above using dbInternalToExternalValueSql for each field, this
	 * allows the SQLGenerator to build selects for fields that don't
	 * have a 1-1 correspondence with database columns.
	 * 
	 * @param array $fields array of EarthIT_Schema_Fields whose values should be selected
	 * @param EarthIT_Schema_ResourceClass $rc
	 * @param string $tableSql SQL to use to refer to the table (e.g. 'x' in 'x.somecolumn')
	 * @return array of value alias => EarthIT_DBC_SQLExpression to be used
	 *   to select all values into db-external form
	 */
	public function makeDbExternalFieldValueSqls(
		array $fields, EarthIT_Schema_ResourceClass $rc, $tableSql,
		EarthIT_DBC_ParamsBuilder $params );
}
