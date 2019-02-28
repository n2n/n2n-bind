<?php
namespace n2n\bind\marshal;

use PHPUnit\Framework\TestCase;
use n2n\bind\type\AutoBindableMock;
use n2n\util\magic\MagicContext;
use n2n\util\magic\MagicObjectUnavailableException;
use n2n\bind\type\Bindable1Mock;
use n2n\bind\type\Bindable2Mock;

class MarshalPlanTest extends TestCase {
	
	function testAuthBindable() {
		$now = new \DateTime();
		$marshalPlan = new MarshalPlan(new AutoBindableMock('Atusch', $now));
		
		$array = $marshalPlan->toArray(new MagicContextMock());
		
		$this->assertTrue($array['firstname'] === 'Atusch');
		$this->assertTrue($array['dateTime'] === $now->getTimestamp());
		$this->assertTrue(count($array) == 2);
	}
	
	function testBindable1Mock() {
		$now = new \DateTime();
		$b1m = new Bindable1Mock('Atusch', $now);
		$b1m->setSomeValue('btusch');
		$b1m->setB2m(new Bindable2Mock());
		
		$marshalPlan = new MarshalPlan($b1m);
		
		$array = $marshalPlan->toArray(new MagicContextMock());
		
		$this->assertTrue(count($array) == 2);
		$this->assertTrue($array['someValue'] === 'btusch mapper suffix');
		
		$this->assertTrue(is_array($array['b2m']));
		$this->assertTrue(is_array($array['b2m']['autoBindable']));
	}
	
	function testIntercept() {
		$now = new \DateTime();
		$b1m = new Bindable1Mock('Atusch', $now);
		$b1m->setSomeValue('btusch');
		$b1m->setB2m(new Bindable2Mock());
		
		$marshalPlan = new MarshalPlan($b1m);
		$marshalPlan->prop('someValue', 'b2m/autoBindable/*')->map(function ($value) {
// 			$this->assertTrue($value )
			return 'ctusch';
		});
		
		$array = $marshalPlan->toArray(new MagicContextMock());
		
		$this->assertTrue(count($array) == 2);
		$this->assertTrue($array['someValue'] === 'ctusch');
		
		$this->assertTrue(is_array($array['b2m']));
		$this->assertTrue($array['b2m']['autoBindable']['firstname'] === 'ctusch');
		$this->assertTrue($array['b2m']['autoBindable']['dateTime'] === 'ctusch');
	}
}

class MagicContextMock implements MagicContext {
	public function lookup($id, $required = true) {
		throw new MagicObjectUnavailableException();
	}

	public function lookupParameterValue(\ReflectionParameter $parameter) {
		throw new MagicObjectUnavailableException();
	}

	
}