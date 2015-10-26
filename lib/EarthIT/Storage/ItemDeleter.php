<?php

interface EarthIT_Storage_ItemDeleter
{
	/**
	 * Delete stuff matching the specified filters.
	 * 
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're deleting
	 * @param array $filters array of EarthIT_Storage_ItemFilter
	 *   (these are effectively ANDed together)
	 * @return nothing
	 */ 
	public function deleteItems( EarthIT_Schema_ResourceClass $rc, array $filters );
}
