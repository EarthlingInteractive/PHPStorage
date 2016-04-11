<?php

class EarthIT_Storage_ItemFiltersTest extends EarthIT_Storage_TestCase
{
	public function testParseArrayAsInList() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$filter = EarthIT_Storage_ItemFilters::parseMulti( array('username'=>array('Frodo Baggins','Jean Wheasler')), $userRc );
		$this->assertTrue( $filter instanceof EarthIT_Storage_Filter_FieldValueComparisonFilter );
		$this->assertTrue( $filter->getComparisonOp() instanceof EarthIT_Storage_Filter_InListComparisonOp );
		$this->assertTrue( $filter->getValueExpression() instanceof EarthIT_Storage_Filter_ListValueExpression );
		$this->assertTrue( $filter->getValueExpression()->getValues() === array('Frodo Baggins','Jean Wheasler') );
	}
}
