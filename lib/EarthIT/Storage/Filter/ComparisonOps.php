<?php

class EarthIT_Storage_Filter_ComparisonOps
{
	protected static function infix($php,$sql=null) {
		if( $sql === null ) $sql = $php;
		return new EarthIT_Storage_Filter_InfixComparisonOp($php, $sql);
	}
	
	protected static $exactMatch;
	public static function exactMatch() {
		if( self::$exactMatch === null ) self::$exactMatch = self::infix('===','=');
		return self::$exactMatch;
	}
	
	public static function get($scheme) {
		switch($scheme) {
		case 'eq': return self::exactMatch();
		case 'lt': return self::infix('<');
		case 'le': return self::infix('<=');
		case 'ge': return self::infix('>=');
		case 'gt': return self::infix('>');
		default:
			throw new Exception("Unrecognized comparison op scheme: '{$scheme}'");
		}
	}
}
