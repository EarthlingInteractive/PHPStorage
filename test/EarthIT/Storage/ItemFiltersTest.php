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
	
	public function testParseNegatingFilter() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$filter = EarthIT_Storage_ItemFilters::parseMulti( array('username'=>'not:in:Frodo Baggins,Jim Henson'), $userRc );
		$this->assertTrue( $filter instanceof EarthIT_Storage_Filter_NegatedItemFilter );
		$this->assertTrue( $filter->getNegatedFilter() instanceof EarthIT_Storage_Filter_FieldValueComparisonFilter );
	}
	
	public function testParseSubItemFilter() {
		$orgRc = $this->registry->schema->getResourceClass('organization');
		$filter0 = EarthIT_Storage_ItemFilters::parseMulti(
			array('user organization attachments.user.username'=>'not:in:Frodo Baggins,Jim Henson'),
			$orgRc, $this->registry->schema );
		
		$this->assertTrue( $filter0 instanceof EarthIT_Storage_Filter_SubItemFilter );
		$this->assertEquals( 'user organization attachments', $filter0->referenceName );
		$this->assertTrue( $filter0->targetIsPlural );
		$this->assertEquals( 'user organization attachment', $filter0->targetResourceClass->getName() );
		
		$filter1 = $filter0->getTargetFilter();
		
		$this->assertTrue( $filter1 instanceof EarthIT_Storage_Filter_SubItemFilter );
		$this->assertEquals( 'user', $filter1->referenceName );
		$this->assertFalse( $filter1->targetIsPlural );
		$this->assertEquals( 'user', $filter1->targetResourceClass->getName() );
		
		$filter2 = $filter1->getTargetFilter();
		
		$this->assertTrue( $filter2 instanceof EarthIT_Storage_Filter_NegatedItemFilter );
	}
}
