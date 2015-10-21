<?php

interface EarthIT_Storage_ItemFetcher
{
	/**
	 * Get a bunch of items in 'schema form'.
	 *
	 * @param array $filters array of EarthIT_Storage_ItemFilter
	 *   (these are effectively ANDed together)
	 * @param string $classUri 'long name' of the class of object that you're fetching
	 *   e.g. "http://ns.example.com/Data/WaffleSquare"
	 * @return array of items, keyed by stringified primary key
	 */ 
	public function getItems( array $filters, $classUri );
}
