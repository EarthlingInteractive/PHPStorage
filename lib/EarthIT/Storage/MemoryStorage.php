<?php

class EarthIT_Storage_MemoryStorage
implements
	EarthIT_Storage_ItemSaver,
	EarthIT_Storage_ItemSearcher,
	EarthIT_Storage_ItemDeleter,
	EarthIT_Storage_ItemUpdater
{
	/** Array of resource class name => list of items of that class */
	protected $items = array();
	protected $idGenerator;
	
	// For use as a simple ID generator
	protected $counter = 1;
	protected function incrementCounter() {
		return $this->counter++;
	}
	
	public function __construct( $idGenerator=null ) {
		if( $idGenerator === null ) {
			$idGenerator = array($this, 'incrementCounter');
		}
		$this->idGenerator = $idGenerator;
	}
	
	public function saveItems( array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array() ) {
		if( count($itemData) == 0 ) {
			// Save a few processor cycles
			return $options[EarthIT_Storage_ItemSaver::RETURN_SAVED] ? array() : null;
		}
		
		EarthIT_Storage_Util::defaultSaveItemsOptions($options);
		$rcName = $rc->getName();
		$blankItem = EarthIT_Storage_Util::defaultItem($rc, true);
		
		$savedItems = array();
		foreach( $itemData as $item ) {
			$id = EarthIT_Storage_Util::itemId($item, $rc);
			if( $id === null ) {
				$pk = $rc->getPrimaryKey();
				if( $pk !== null ) {
					foreach( $pk->getFieldNames() as $fn ) {
						if( !isset($item[$fn]) ) {
							if( $rc->getField($fn)->getType()->getName() == 'entity ID' ) {
								$item[$fn] = call_user_func($this->idGenerator);
							} else {
								throw new Exception("Don't know how to generate ID component '$fn' of '".$rc->getName()."'");
							}
						}
					}
				}
				$id = EarthIT_Storage_Util::itemId($item, $rc);
				if( $id === null ) {
					// Note: In some cases we may want to allow ID-less records,
					// in which case remove the throw and just append the item to wherever lists.
					throw new Exception("Failed to generate an ID for a ".$rc->getName());
				}
			}
			$item = EarthIT_Storage_Util::castItemFieldValues($item, $rc);
			if( isset($this->items[$rcName][$id]) ) {
				switch( ($odk = $options[EarthIT_Storage_ItemSaver::ON_DUPLICATE_KEY]) ) {
				case EarthIT_Storage_ItemSaver::ODK_ERROR:
				case EarthIT_Storage_ItemSaver::ODK_UNDEFINED:
					throw new Exception("saveItem causes collision for ".$rc->getName()." '{$id}'");
				case EarthIT_Storage_ItemSaver::ODK_KEEP:
					$savedItems[$id] = $this->items[$rcName][$id];
					break;
				case EarthIT_Storage_ItemSaver::ODK_REPLACE:
					$savedItems[$id] = $this->items[$rcName][$id] = $item + $blankItem;
					break;
				case EarthIT_Storage_ItemSaver::ODK_UPDATE:
					$savedItems[$id] = $this->items[$rcName][$id] = $item + $this->items[$rcName][$id];
					break;
				default:
					throw new Exception("Unrecognized on-duplicate-key option: '{$odk}'");
				}
			} else {
				$savedItems[$id] = $this->items[$rcName][$id] = $item + $blankItem;
			}
		}
		
		return $options[EarthIT_Storage_ItemSaver::RETURN_SAVED] ? $savedItems : null;
	}
	
	public function searchItems( EarthIT_Storage_Search $search, array $options=array() ) {
		$rcName = $search->getResourceClass()->getName();
		$filter = $search->getFilter();
		$matched = array();
		if( isset($this->items[$rcName]) ) foreach( $this->items[$rcName] as $item ) {
			if( $filter->matches($item) ) $matched[] = $item;
		}
		usort( $matched, $search->getComparator() );
		return array_slice($matched, $search->getSkip(), $search->getLimit());
	}
	
	public function deleteItems( EarthIT_Schema_ResourceClass $rc, EarthIT_Storage_ItemFilter $filter ) {
		$rcName = $rc->getName();
		$matchedKeys = array();
		if( isset($this->items[$rcName]) ) foreach( $this->items[$rcName] as $k=>$item ) {
			if( $filter->matches($item) ) $matchedKeys[] = $k;
		}
		foreach( $matchedKeys as $k ) unset($this->items[$rcName][$k]);
	}
	
	public function updateItems(
		array $updatedFieldValues, EarthIT_Schema_ResourceClass $rc,
		EarthIT_Storage_ItemFilter $filter, array $options=array()
	) {
		$rcName = $rc->getName();
		$matchedKeys = array();
		if( isset($this->items[$rcName]) ) foreach( $this->items[$rcName] as $k=>$item ) {
			if( $filter->matches($item) ) $matchedKeys[] = $k;
		}
		$updated = array();
		foreach( $matchedKeys as $k ) {
			$item = $this->items[$rcName][$k];
			$item = $updatedFieldValues + $item;
			$id = EarthIT_Storage_Util::itemId($item, $rc);
			if( $id !== null and $id !== $k ) {
				// TODO: Look at options to determine if updating is allowed
				// to overwrite other items
				unset($this->items[$rcName][$k]);
				$this->items[$rcName][$id] = $item;
				$k = $id;
			}
			$updated[$k] = $item;
		}
		return $updated;
	}
}
