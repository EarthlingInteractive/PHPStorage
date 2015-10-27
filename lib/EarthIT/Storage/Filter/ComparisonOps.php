<?php

class EarthIT_Storage_Filter_ComparisonOps
{
	protected static $exactMatch;
	public static function exactMatch() {
		if( self::$exactMatch === null ) {
			self::$exactMatch = new EarthIT_Storage_Filter_InfixComparisonOp('===', '=');
		}
		return self::$exactMatch;
	}
}
