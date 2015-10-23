<?php

/** @api */
interface EarthIT_Storage_ItemFetcher
{
	/**
	 * Get a bunch of items in 'schema form'.
	 *
	 * @param array $filters array of EarthIT_Storage_ItemFilter
	 *   (these are effectively ANDed together)
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're fetching
	 * @return array of items, keyed by stringified primary key
	 */ 
	public function getItems( array $filters, EarthIT_Schema_ResourceClass $rc );
}
