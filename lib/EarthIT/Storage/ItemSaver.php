<?php

/** @api */
interface EarthIT_Storage_ItemSaver
{
	/**
	 * Store a bunch of data.
	 * 
	 * @param array $itemData list of arrays of item data ('schema form') to be stored
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're saving
	 * @param boolean 
	 * @param array $options = [
	 *   'returnSaved' => true|false (default = false) ; if true, will return the items as they were saved
	 *     (any default field values filled in or changed by the database, etc)
	 *   'onDuplicateKey' => 'undefined|error|skip|replace|update' (default = 'error')
	 * ]
	 * @return array|null the items as inserted into the database, maintaining keys, or null if
	 *   options.returnSaved was false
	 */
	public function saveItems(array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array());
}
