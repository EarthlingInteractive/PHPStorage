<?php

class EarthIT_Storage_PostgresStorage extends EarthIT_Storage_SQLStorage
{
	public function __construct(
		EarthIT_Schema $schema,
		EarthIT_DBC_SQLRunner $sqlRunner,
		EarthIT_DBC_Namer $dbObjectNamer
	) {
		parent::__construct($schema, $sqlRunner, $dbObjectNamer, new EarthIT_Storage_PostgresSQLGenerator());
	}
	
	public function dbToPhpValue( $value, EarthIT_Schema_DataType $t ) {
		if( self::valuesOfTypeShouldBeSelectedAsGeoJson($t) || self::valuesOfTypeShouldBeSelectedAsJson($t) ) {
			return $value === null ? null : EarthIT_JSON::decode($value);
		}
		// Various special rules may end up here
		return EarthIT_CMIPREST_Util::cast( $value, $t->getPhpTypeName() );
	}
}
