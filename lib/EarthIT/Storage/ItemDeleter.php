<?php

interface EarthIT_Storage_ItemDeleter
{
	/**
	 * Delete stuff matching the specified filters.
	 * 
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're deleting
	 * @param EarthIT_Storage_ItemFilter $filter
	 * @return nothing
	 */ 
	public function deleteItems( EarthIT_Schema_ResourceClass $rc, EarthIT_Storage_ItemFilter $filter );
}
