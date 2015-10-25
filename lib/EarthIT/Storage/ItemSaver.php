<?php

/** @api */
interface EarthIT_Storage_ItemSaver
{
	const RETURN_SAVED = 'returnSaved';
	const ON_DUPLICATE_KEY = 'onDuplicateKey';
	
	const ODK_KEEP = 'keep';
	const ODK_UPDATE = 'update';
	const ODK_REPLACE = 'replace';
	const ODK_ERROR = 'error';
	const ODK_UNDEFINED = 'undefined';

	/**
	 * Store a bunch of data.
	 * 
	 * @param array $itemData list of arrays of item data ('schema form') to be stored
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're saving
	 * @param boolean 
	 * @param array $options = [
	 *   'returnSaved' => true|false (default = false) ; if true, will return the items as they were saved
	 *     (any default field values filled in or changed by the database, etc)
	 *   'onDuplicateKey' => 'undefined|error|keep|replace|update' (default = 'error')
	 *     (use the ODK_* constants)
	 * ]
	 * @return array|null the items as inserted into the database,
	 *   or null if options.returnSaved was false.
	 *   Keys of returned array have no meaning and don't necessarily
	 *   correspond to keys of the input array, though they may happen
	 *   to match for certain backends.
	 */
	public function saveItems(array $itemData, EarthIT_Schema_ResourceClass $rc, array $options=array());
}
