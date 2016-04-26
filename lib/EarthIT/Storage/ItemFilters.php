<?php

/**
 * Static functions for constructing item filters.
 */
class EarthIT_Storage_ItemFilters
{
	public static function ored( array $filters ) {
		if( count($filters) === 1 ) return EarthIT_Storage_Util::first($filters);
		return new EarthIT_Storage_Filter_OredItemFilter($filters);
	}
	
	public static function anded( array $filters ) {
		if( count($filters) === 1 ) return EarthIT_Storage_Util::first($filters);
		return new EarthIT_Storage_Filter_AndedItemFilter($filters);
	}

	/**
	 * A filter that matches everything!
	 */
	public static function emptyFilter() {
		return self::anded(array());
	}
	
	public static function byId( $ids, EarthIT_Schema_ResourceClass $rc ) {
		if( $ids === '' ) $ids = array();
		if( is_scalar($ids) ) $ids = array($ids);
		if( !is_array($ids) ) {
			throw new Exception("'\$ids' parameter must be a scalar or array.  Got: ".var_export($ids,true));
		}
		
		// TODO: If just a single ID field, use an IN (...) instead
		
		$filters = array();
		foreach( $ids as $id ) {
			$filters[] = self::exactFieldValues( EarthIT_Storage_Util::itemIdToFieldValues($id, $rc), $rc );
		}
		
		// It can be any of those IDs!
		return self::ored($filters);
	}
	
	public static function exactFieldValues( array $fieldValues, EarthIT_Schema_ResourceClass $rc ) {
		$fields = $rc->getFields();
		$filters = array();
		foreach( $fieldValues as $fn=>$v ) {
			$filters[] = new EarthIT_Storage_Filter_ExactMatchFieldValueFilter($fields[$fn], $rc, $v);
		}
		return self::anded($filters);
	}
	
	public static function fieldValueFilter( $scheme, $pattern, EarthIT_Schema_Field $field, EarthIT_Schema_ResourceClass $rc ) {
		switch( $scheme ) {
		case 'not':
			// Oh look this is kind of a silly way to do it:
			$toBeNegated = self::parsePattern( $field->getName(), $pattern, $rc );
			return new EarthIT_Storage_Filter_NegatedItemFilter($toBeNegated);
		case 'eq':
			$value = EarthIT_Storage_Util::cast($pattern, $field->getType()->getPhpTypeName());
			return new EarthIT_Storage_Filter_ExactMatchFieldValueFilter($field, $rc, $value);
		case 'in':
			$values = array();
			$patternValues = is_array($pattern) ? $pattern : explode(',',$pattern);
			foreach( $patternValues as $p ) {
				$values[] = EarthIT_Storage_Util::cast($p, $field->getType()->getPhpTypeName());
			}
			$comparisonOp = EarthIT_Storage_Filter_InListComparisonOp::getInstance();
			$vExp = new EarthIT_Storage_Filter_ListValueExpression($values);
			break;
		case 'lt': case 'le':
		case 'ge': case 'gt':
			$comparisonOp = EarthIT_Storage_Filter_ComparisonOps::get($scheme);
			$value = EarthIT_Storage_Util::cast($pattern, $field->getType()->getPhpTypeName());
			$vExp = new EarthIT_Storage_Filter_ScalarValueExpression($value);
			break;
		case 'like':
			return new EarthIT_Storage_Filter_PatternFieldValueFilter($field, $rc, $pattern, true);
		default:
			throw new Exception("Unrecognized pattern scheme: '{$scheme}'");
		}
		
		return new EarthIT_Storage_Filter_FieldValueComparisonFilter($field, $rc, $comparisonOp, $vExp);
	}
	
	// TODO: Maybe move this stuff out to a utility class in Schema.
	// CMIPREST also needs to do this stuff.
	
	const COLLECTION_NAME = 'http://ns.earthit.com/CMIPREST/collectionName';
	const INVERSE_NAME = 'http://ns.earthit.com/CMIPREST/inverseName';
	const INVERSE_COLLECTION_NAME = 'http://ns.earthit.com/CMIPREST/inverseCollectionName';
	
	protected static function maybeMinimize( $name, $minimize ) {
		return $minimize ? EarthIT_Schema_WordUtil::minimize($name) : $name;
	}
	
	protected static function findReference(
		$refRef, EarthIT_Schema_ResourceClass $rc, EarthIT_Schema $schema=null,
		&$refName=null, &$refIsPlural=null, $fuzzyMatch=true
	) {
		$refRef = self::maybeMinimize($refRef, $fuzzyMatch);
		
		$refs = $rc->getReferences();
		
		if( $fuzzyMatch ) {
			foreach( $refs as $k=>$ref ) {
				if( EarthIT_Schema_WordUtil::minimize($k) == $refRef ) {
					$refName = $k;
					$refIsPlural = false;
					return $ref;
				}
			}
		} else {
			if( isset($refs[$refRef]) ) {
				$refIsPlural = false;
				$refName = $refRef;
				return $refs[$refName];
			}
		}
		
		if( $schema !== null ) {
			$rcs = $schema->getResourceClasses();
			
			// Look for one based on explicit inverse name
			foreach( $rcs as $trcName=>$trc ) {
				foreach( $rc->getReferences() as $ref ) {
					$refInverseName = $inverseRef->getFirstPropertyValue(EarthIT_CMIPREST_NS::INVERSE_NAME);
					$refPluralInverseName = $inverseRef->getFirstPropertyValue(
						EarthIT_CMIPREST_NS::INVERSE_COLLECTION_NAME,
						EarthIT_Schema_WordUtil::pluralize($refInverseName)
					);
					$matchRefInverseName = self::maybeMinimize($refInverseName, $fuzzyMatch);
					$matchRefPluralInverseName = self::maybeMinimize($refPluralInverseName, $fuzzyMatch);
					// plural takes priority if plural name = singular name
					if( $matchRefPluralInverseName == $refRef ) {
						$refName = $refPluralInverseName;
						$refIsPlural = true;
						return new EarthIT_Schema_Reference( $trcName, $ref->getTargetFieldNames(), $ref->getOriginFieldNames() );
					}
					if( $matchRefInverseName == $refRef ) {
						$refName = $refInverseName;
						$refIsPlural = false;
						return new EarthIT_Schema_Reference( $trcName, $ref->getTargetFieldNames(), $ref->getOriginFieldNames() );
					}
				}
			}
			
			// Look for one based on class name = ref ref
			foreach( $rcs as $trcName=>$trc ) {
				$trcCollectionName = $trc->getFirstPropertyValue(self::COLLECTION_NAME, EarthIT_Schema_WordUtil::pluralize($trcName));
				$matchTrcCollectionName = self::maybeMinimize($trcCollectionName, $fuzzyMatch);
				$matchTrcName = self::maybeMinimize($trcName, $fuzzyMatch);
				
				$found = false;
				// plural takes priority if plural name = singular name
				if( $matchTrcCollectionName == $refRef ) {
					$found = true;
					$refIsPlural = true;
					$refName = $trcCollectionName;
				} else if( $matchTrcName == $refRef ) {
					$found = true;
					$refIsPlural = false;
					$refName = $trcName;
				}
				if( $found ) {
					// Found a resource class that could work
					// if it has an appropriate reverse reference.
					foreach( $trc->getReferences() as $ref ) {
						if( $ref->getTargetClassName() == $rc->getName() ) {
							return new EarthIT_Schema_Reference( $trcName, $ref->getTargetFieldNames(), $ref->getOriginFieldNames() );
						}
					}
				}
			}
		}
		
		return null;
	}
	
	public static function parsePattern( $fieldName, $pattern, EarthIT_Schema_ResourceClass $rc, EarthIT_Schema $schema=null ) {
		$fieldNameParts = explode('.', $fieldName, 2);
		if( count($fieldNameParts) > 1 ) {
			if( $schema === null ) throw new Exception("Can't parse sub-item filter '{$fieldName}' because no \$schema provided.");
			
			$refRef = $fieldNameParts[0];
			$subFieldName = $fieldNameParts[1];
			$ref = self::findReference($refRef, $rc, $schema, $refName, $refIsPlural, true);
			if( $ref === null ) {
				throw new Exception(
					"Couldn't find reference '$refRef' from ".$rc->getName().
					($schema === null) ? " (try providing a schema to ".__FUNCTION__." so I can look harder)" : ''
				);
			}
			$targetRc = $schema->getResourceClass($ref->getTargetClassName());
			$targetFilter = self::parsePattern($subFieldName, $pattern, $targetRc, $schema);
			return new EarthIT_Storage_Filter_SubItemFilter(
				$refName, $refIsPlural, $ref,
				$rc, $targetRc, $targetFilter
			);
		}
		
		$field = $rc->getField($fieldName);
		if( $field === null ) throw new Exception("Error while parsing filter string '$filterString': no such field as '{$p[0]}'");
		
		if( is_scalar($pattern) ) {
			$patternParts = explode(':', $pattern, 2);
			if( count($patternParts) == 1 ) {
				$pattern = $pattern;
				$scheme = strpos($pattern,'*') === false ? 'eq' : 'like';
			} else {
				$pattern = $patternParts[1];
				$scheme = $patternParts[0];
			}
		} else if( is_array($pattern) ) {
			$scheme = 'in';
			$pattern = $pattern;
		} else {
			throw new Exception("Don't know how to interpret ".gettype($pattern)." as field value pattern.");
		}
		
		return self::fieldValueFilter( $scheme, $pattern, $field, $rc );
	}
	
	// TODO: What's nameMap?  Maybe remove it?
	public static function parse( $filterString, EarthIT_Schema_ResourceClass $rc, EarthIT_Schema $schema=null ) {
		if( $filterString instanceof EarthIT_Storage_ItemFilter ) return $filterString;
		
		$p = explode('=', $filterString, 2);
		if( count($p) != 2 ) throw new Exception("Not enough '='-separated parts in filter string: '$filterString'");
		return self::parsePattern($p[0], $p[1], $rc, $schema);
	}
	
	/**
	 * TODO: Document how different stuffs get parsed.
	 */
	public static function parseMulti( $filters, EarthIT_Schema_ResourceClass $rc, EarthIT_Schema $schema=null ) {
		if( $filters === '' ) return self::emptyFilter();
		if( $filters instanceof EarthIT_Storage_ItemFilter ) return $filters;
		
		if( is_string($filters) ) {
			$filters = array_map('urldecode', explode('&', $filters));
		}
		if( !is_array($filters) ) {
			throw new Exception("'\$filters' parameter must be a string, an array, or an ItemFilter.");
		}
		
		foreach( $filters as $k=>&$f ) {
			if( is_string($k) ) {
				//$f = "{$k}={$f}"; // ['ID' => 'foo'] = ['ID=foo']
				$f = self::parsePattern($k, $f, $rc, $schema);
			} else {
				$f = self::parse($f, $rc, $schema);
			}
		}; unset($f);
		
		if( count($filters) == 1 ) return EarthIT_Storage_Util::first($filters);
		return new EarthIT_Storage_Filter_AndedItemFilter($filters);
	}
}
