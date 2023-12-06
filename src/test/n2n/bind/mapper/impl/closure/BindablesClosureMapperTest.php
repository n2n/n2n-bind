<?php

namespace n2n\bind\mapper\impl\closure;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\plan\BindBoundary;

class BindablesClosureMapperTest extends TestCase {
	function testBindableClosure() {
		$dm = new DataMap(['string' => 'test', 'int' => 321]);
		$targetDm = new DataMap();

		Bind::attrs($dm)->toAttrs($targetDm)
				->props(['string', 'int'],
						Mappers::bindablesClosure(function(array $bindables, BindBoundary $bindBoundary) use ($dm) {
							$bindables['string']->setValue('huii');
							$bindables['int']->setExist(false);
							$bindBoundary->acquireBindableByRelativeName('superInt')->setExist(true)->setValue(123);
						}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());


		$this->assertEquals('huii', $targetDm->reqString('string'));
		$this->assertFalse($targetDm->has('int'));
		$this->assertEquals(123, $targetDm->reqInt('superInt'));
	}
}