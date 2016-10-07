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
	
	public function testFuzzyMatchParseFilter() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$filter = EarthIT_Storage_ItemFilters::parseMulti( array('userName'=>'not:in:Frodo Baggins,Jim Henson'), $userRc, null, true );
		$this->assertTrue( $filter instanceof EarthIT_Storage_Filter_NegatedItemFilter );
		
		$caught = false;
		try {
			$filter = EarthIT_Storage_ItemFilters::parseMulti( array('userName'=>'not:in:Frodo Baggins,Jim Henson'), $userRc, null, false );
		} catch( Exception $e ) {
			$caught = true;
		}
		$this->assertTrue( $caught, "non-fuzzy parsing with 'userName' field should have failed" );
	}
	
	public function testParseIsNotNullFilter() {
		$userRc = $this->registry->schema->getResourceClass('user');
		$filter = EarthIT_Storage_ItemFilters::parseMulti( array('e-mail address'=>'not:is:null'), $userRc, null, true );
		$PB = new EarthIT_DBC_ParamsBuilder();
		$sql = $filter->toSql( "u", $this->registry->dbObjectNamer, $PB);
		$PB->getParams();
		$this->assertEquals("(u.{v_0} IS NULL) = false", $sql);
		$this->assertTrue( $filter->matches(array('e-mail address'=>'frodo@baggins.net')) );
		$this->assertFalse( $filter->matches(array('e-mail address'=>null)) );
	}
	
	public function testZeroLengthInFilter() {
		$userRc = $this->registry->schema->getResourceClass('user');
		
		$filter = EarthIT_Storage_ItemFilters::parseMulti( array('username'=>'in:'), $userRc );
		$this->assertTrue( $filter instanceof EarthIT_Storage_Filter_OredItemFilter, "'in:' should have parsed to EarthIT_Storage_Filter_OredItemFilter; got a ".get_class($filter) );
		$this->assertEquals( array(), $filter->getComponentFilters(), "'in:' should have parsed to EarthIT_Storage_Filter_OredItemFilter with no components." );
	}
	
	public function _testParseSubItemFilter( $fuzz ) {
		$orgRc = $this->registry->schema->getResourceClass('organization');
		$filter0 = EarthIT_Storage_ItemFilters::parseMulti(
			array(
				($fuzz ? 'user-organization-attachments' : 'user organization attachments').
					'.user.username' => 'not:in:Frodo Baggins,Jim Henson'),
			$orgRc, $this->registry->schema, true );
		
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

	public function testParseSubItemFilter() {
		$this->_testParseSubItemFilter( false );
	}
	public function testParseSubItemFilterFuzzy() {
		$this->_testParseSubItemFilter( true );
	}
	public function testParseSubItemFiltersInvalidNonFuzzy() {
		$caught = false;
		try {
			EarthIT_Storage_ItemFilters::parseMulti(
				array('user-organization-attachments.user.username' => 'not:in:Frodo Baggins,Jim Henson'),
				$orgRc, $this->registry->schema, true );
		} catch( Exception $e ) {
			$caught = true;
		}
		
		$this->assertTrue(
			$caught, "non-fuzzy parsing of ".
			"'user-organization-attachments.user.username' filter should have thrown an exception");
	}
}
