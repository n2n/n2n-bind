<?php

namespace n2n\bind\mapper\impl\closure;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\target\BindTestClass;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindableBoundary;

class BindablesClosureMapperTest extends TestCase {
	function testBindableClosure() {
		$dm = new DataMap(['string' => 'test', 'int' => 321]);
		$targetDm = new DataMap();

		Bind::attrs($dm)->toAttrs($targetDm)
				->props(['string', 'int'],
						Mappers::bindablesClosure(function (array $bindables, BindableBoundary $bindableBoundary) use ($dm) {
							$bindables['string']->setValue('huii');
							$bindables['int']->setExist(false);
							$bindableBoundary->acquireBindable('superInt')->setExist(true)->setValue(123);
						}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());


		$this->assertEquals('huii', $targetDm->reqString('string'));
		$this->assertFalse($targetDm->has('int'));
		$this->assertEquals(123, $targetDm->reqInt('superInt'));
	}
}