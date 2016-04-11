<?php

interface EarthIT_Storage_Filter_MultiFieldValueFilter extends EarthIT_Storage_ItemFilter
{
	/**
	 * @return array of EarthIT_Schema_Field
	 */
	public function getFields();
}
