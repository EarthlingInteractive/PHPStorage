<?php

interface EarthIT_Storage_ItemShover
{
	/**
	 * Store a bunch of data.
	 * 
	 * @param array $itemData list of arrays of item data ('schema form') to be stored
	 * @param string $classUri 'long name' of the class of object that you're inserting
	 *   e.g. "http://ns.example.com/Data/WaffleSquare"
	 * @param boolean 
	 * @param array $options = [
	 *   'returnStored' => true|false (default = false) ; if true, will return the items 
	 *     inserted with the same keys as the input array
	 *   'onDuplicateKey' => 'undefined|error|skip|replace|update' (default = 'error')
	 * ]
	 * @return array|null the items as inserted into the database, maintaining keys, or null if
	 *   options.returnStored was false
	 */
	public function shoveItems(array $itemData, $classUri, array $options=array());
}
