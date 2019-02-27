<?php
namespace n2n\bind\marshal;

use PHPUnit\Framework\TestCase;
use n2n\bind\type\AutoBindableMock;
use n2n\util\magic\MagicContext;
use n2n\util\magic\MagicObjectUnavailableException;

class MarshalPlanTest extends TestCase {
	
	function testAuthBindable() {
		$marshalPlan = new MarshalPlan(new AutoBindableMock('Atusch', new \DateTime()));
		
		$array = $marshalPlan->toArray(new MagicContextMock());
		var_dump($array);
		$this->assertTrue(isset($array['firstname']));
		$this->assertTrue($array['firstname'] === 'Atusch');
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