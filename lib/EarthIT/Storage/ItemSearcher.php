<?php

interface EarthIT_Storage_ItemSearcher
{
	/**
	 * Get a bunch of items in 'schema form'.
	 *
	 * @param EarthIT_Storage_Search $search the search to do
	 * @param array $options array of additional options which may or may not be payed any attention to
	 *   by the storage backend:
	 *     fieldsOfInterest => array of names of fields that you care about (if not specified, you get all of them) 
	 * @return array of items, keyed by stringified primary key
	 */ 
	public function itemSearch( EarthIT_Storage_Search $search, array $options=array() );
}
