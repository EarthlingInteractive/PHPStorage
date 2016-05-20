<?php

interface EarthIT_Storage_ItemUpdater
{
	const RETURN_UPDATED = 'returnSaved'; // Intentionally the same as ItemSaver::RETURN_SAVED
	
	/**
	 * Update items matching a filter by replacing certain field values.
	 * 
	 * @param array $updatedFieldValues updated field values; fields not mentioned will not be changed
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're updating
	 * @param EarthIT_Storage_ItemFilter $filter only update items matching this filter
	 * @param array $options
	 * @return nothing
	 */
	public function updateItems(
		array $updatedFieldValues,
		EarthIT_Schema_ResourceClass $rc,
		EarthIT_Storage_ItemFilter $filter,
		array $options=array()
	);
}
