<?php

interface EarthIT_Storage_ItemFetcher
{
	/**
	 * Get a bunch of items in 'schema form'.
	 *
	 * @param array $filters array of EarthIT_Storage_ItemFilter
	 *   (these are effectively ANDed together)
	 * @param array $orderBy array of EarthIT_Storage_OrderByComponent
	 * @param EarthIT_Schema_ResourceClass $rc class of object that you're fetching
	 * @param array $options array of additional options which may or may not be payed any attention to
	 *   by the storage backend:
	 *     fieldsOfInterest => array of names of fields that you care about (if not specified, you get all of them) 
	 * @return array of items, keyed by stringified primary key
	 */ 
	public function getItems( EarthIT_Schema_ResourceClass $rc, array $filters, array $orderBy, $offset, $limit, array $options );
}
