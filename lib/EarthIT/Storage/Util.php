<?php

class EarthIT_Storage_Util
{
	public static function first(array $stuff) {
		foreach( $stuff as $thing ) return $thing;
		throw new Exception("No first item in empty array!");
	}
	
	public static function defaultSaveItemsOptions(array &$options) {
		if( !isset($options[EarthIT_Storage_ItemSaver::RETURN_SAVED]) ) $options[EarthIT_Storage_ItemSaver::RETURN_SAVED] = false;
		if( !isset($options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY]) ) $options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY] = 'error';
	}
	
	/**
	 * Convert a value to the named PHP scalar type
	 * in a somewhat reasonable way.
	 */
	public static function cast( $value, $phpType ) {
		// Type or value unknown?  Return as-is.
		if( $phpType === null or $value === null ) return $value;
		
		switch( $phpType ) {
		case 'JSON value':
			// It could be anything!
			return $value;
		case 'GeoJSON array': case 'array':
			// It needs to be an array!
			if( !is_array($value) ) {
				throw new Exception("Can't cast value to array: ".var_export($value,true));
			}
			return $value;
		case 'string': return (string)$value;
		case 'float': return (float)$value;
		case 'int': return (int)$value;
		case 'bool':
			if( is_bool($value) ) return $value;
			if( is_numeric($value) ) {
				if( $value == 1 ) return true;
				if( $value == 0 ) return false;
			}
			if( is_string($value) ) {
				if( in_array($value, array('yes','true','on')) ) return true;
				if( in_array($value, array('no','false','off','')) ) return false;
			}
			throw new Exception("Invalid boolean representation: ".var_export($value,true));
		default:
			throw new Exception("Don't know how to cast to PHP type '$phpType'.");
		}
	}
	
	/**
	 * Return a copy of $item where field values have been casted to their correct types
	 */
	public static function castItemFieldValues( array $item, EarthIT_Schema_ResourceClass $rc ) {
		foreach( $rc->getFields() as $fn=>$f ) {
			if( isset($item[$fn]) and ($t = $f->getType()) and ($pt = $t->getPhpTypeName()) ) {
				$item[$fn] = self::cast($item[$fn], $pt);
			}
		}
		return $item;
	}
	
	/**
	 * @api
	 * 
	 * Return a regex that can be used to find or match a stringified ID.
	 * If $includeEndAnchors, '^' and '$' are included.
	 * This also allows the regex to contain up to one (.*) segment.
	 */
	public static function itemIdRegex( EarthIT_Schema_ResourceClass $rc, $includeEndAnchors=false ) {
		$pk = $rc->getPrimaryKey();
		if( $pk === null or count($pk->getFieldNames()) == 0 ) {
			throw new Exception("No ID regex because no primary key for ".$rc->getName().".");
		}
		
		$fields = $rc->getFields();
		$parts = array();
		$unpatternedFieldNames = array();
		foreach( $pk->getFieldNames() as $fn ) {
			$field = $fields[$fn];
			$datatype = $field->getType();
			$fRegex = $datatype->getRegex();
			if( $fRegex === null ) {
				$unpatternedFieldNames[] = $fn;
				$fRegex = '.*';
			}
			$parts[] = "($fRegex)";
		}
		
		$regex = implode('-', $parts);
		if( $includeEndAnchors ) {
			if( count($unpatternedFieldNames) > 1 ) {
				throw new Exception(
					"Can't build end-anchored ID regex because more than one ID component ".
					"field lacks a regex: ".implode(', ',$unpatternedFieldNames));
			}
			return '^'.$regex.'$';
		} else {
			if( count($unpatternedFieldNames) > 0 ) {
				throw new Exception(
					"Can't build unanchored ID regex because some ID component ".
					"fields lacks a regex: ".implode(', ',$unpatternedFieldNames));
			}
			
			return implode("-", $parts);
		}
	}
	
	/**
	 * Returns a scalar value representing the item's ID,
	 * or null if it does not have one.
	 * 
	 * If the class's primary key is a single scalar field,
	 * the value of that field is returned.
	 * 
	 * If it has a composite key, this returns a string composed of the
	 * component values stringified and joined with "-".
	 * 
	 * @return scalar|null
	 */
	public static function itemId( array $item, EarthIT_Schema_ResourceClass $rc ) {
		$indexes = $rc->getIndexes();
		if( !isset($indexes['primary']) ) return null;
		$pk = $indexes['primary'];
		if( $pk === null or count($pk->getFieldNames()) == 0 ) return null;
		
		$fields = $rc->getFields();
		$parts = array();
		foreach( $pk->getFieldNames() as $fn ) {
			if( !isset($item[$fn]) ) return null;
			$parts[] = $item[$fn];
		}
		return implode("-", $parts);
	}
	
	/**
	 * return array of field name => field value for the primary key fields encoded in $id
	 */
	public static function itemIdToFieldValues( $id, EarthIT_Schema_ResourceClass $rc ) {
		$idRegex = self::itemIdRegex( $rc, true );
		if( !preg_match('/'.$idRegex.'/', $id, $bif) ) {
			throw new Exception("ID did not match regex /$idRegex/: $id");
		}
		
		$idFieldValues = array();
		$pk = $rc->getPrimaryKey();
		$fields = $rc->getFields();
		$i = 1;
		foreach( $pk->getFieldNames() as $fn ) {
			$field = $fields[$fn];
			$idFieldValues[$fn] = self::cast($bif[$i], $field->getType()->getPhpTypeName());
			++$i;
		}
		
		return $idFieldValues;
	}
	
	/**
	 * @unstable; only intended for internal use for now
	 */
	public static function defaultItem( EarthIT_Schema_ResourceClass $rc, $allFields=false ) {
		$defs = array();
		foreach( $rc->getFields() as $fn=>$f ) {
			// We want there to be entries for all fields that have any
			// default value specified, even if that default value is
			// null.
			// Fields whose values are auto-generated by the database should
			// not have any default value specified (if they do, this would cause problems).
			foreach( $f->getPropertyValues(EarthIT_Storage_NS::DEFAULT_VALUE) as $v ) {
				$defs[$fn] = $v;
			}
			if( $allFields and !isset($defs[$fn]) ) $defs[$fn] = null;
		}
		// 'default value' might be of the wrong type sometimes.
		return self::castItemFieldValues($defs, $rc);
	}
	
	/**
	 * Get a field property value, taking into account
	 * whether the field is fake or not, and defaults for either case.
	 */
	protected static function fieldPropertyValue( $f, $propUri, $nonFakeDefault=null, $fakeDefault=null ) {
		$v = $f->getFirstPropertyValue($propUri);
		if( $v !== null ) return $v;
		
		$isFake = $f->getFirstPropertyValue(EarthIT_Storage_NS::IS_FAKE_FIELD);
		return $isFake ? $fakeDefault : $nonFakeDefault;
	}
	
	protected static function fieldsWithProperty( array $l, $propUri, $nonFakeDefault=null, $fakeDefault=null ) {
		$filtered = array();
		foreach( $l as $k=>$f ) {
			if( self::fieldPropertyValue($f, $propUri, $nonFakeDefault, $fakeDefault) ) {
				$filtered[$k] = $f;
			}
		}
		return $filtered;
	}
	
	public static function storableFields( EarthIT_Schema_ResourceClass $rc ) {
		return self::fieldsWithProperty($rc->getFields(), EarthIT_Storage_NS::HAS_A_DATABASE_COLUMN, true, false);
	}
	
	/**
	 * @param array $selects array of alias => SQL expression text
	 * @return array of select parts [x, y, ...] of 'SELECT x, y, ... FROM yaddah yaddah'
	 */
	public static function formatSelectComponents( array $selects, EarthIT_DBC_ParamsBuilder $PB ) {
		$sqlz = array();
		foreach( $selects as $k=>$selSql ) {
			if( is_integer($k) ) {
				$sqlz[] = $selSql;
			} else {
				$aliasParamName = $PB->bind(new EarthIT_DBC_SQLIdentifier($k));
				$sqlz[] = "{$selSql} AS {{$aliasParamName}}";
			}
		}
		return $sqlz;
	}
	
	public static function makeSearch(
		EarthIT_Schema_ResourceClass $rc, $filters=array(), $orderBy=array(),
		$skip=0, $limit=null, array $options=array()
	) {
		$schema = isset($options[EarthIT_Storage::SCHEMA]) ? $options[EarthIT_Storage::SCHEMA] : null;
		$fuzzyMatch = isset($options[EarthIT_Storage::FUZZY_MATCH]) ? $options[EarthIT_Storage::FUZZY_MATCH] : false;
		
		$filter = EarthIT_Storage_ItemFilters::parseMulti($filters, $rc, $schema, $fuzzyMatch);
		$comparator = EarthIT_Storage_FieldwiseComparator::parse($orderBy);
		
		return new EarthIT_Storage_Search($rc, $filter, $comparator, $skip, $limit, $options);
	}
	
	public static function getItemsById(
		array $ids, EarthIT_Schema_ResourceClass $rc,
		EarthIT_Storage_ItemSearcher $storage, array $options=array()
	) {
		$filter = EarthIT_Storage_ItemFilters::byId($ids, $rc);
		$search = new EarthIT_Storage_Search($rc, $filter);
		return $storage->searchItems($search, $options);
	}

	public static function getItemById(
		$id, EarthIT_Schema_ResourceClass $rc,
		EarthIT_Storage_ItemSearcher $storage, array $options=array()
	) {
		$items = self::getItemsById( array($id), $rc, $storage, $options );
		return count($items) == 0 ? null : self::first($items);
	}
}
