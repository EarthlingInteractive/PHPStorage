<?php

interface EarthIT_Storage_Filter_FieldValueFilter extends EarthIT_Storage_ItemFilter
{
	/**
	 * @return EarthIT_Schema_Field
	 */
	public function getField();
}
