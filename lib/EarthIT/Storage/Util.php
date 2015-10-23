<?php

class EarthIT_Storage_Util
{
	public static function defaultSaveItemsOptions(array &$options) {
		if( !isset($options['returnStored']) ) $options['returnStored'] = false;
		if( !isset($options['onDuplicateKey']) ) $options['onDuplicateKey'] = 'error';
	}
}