<?php

namespace n2n\bind\mapper\impl\closure;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\target\mock\BindTestClassA;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\build\impl\target\mock\BindTestClass;
use n2n\bind\plan\BindData;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class ValueAsBindDataClosureMapperTest extends TestCase {
	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testValueClosure() {
		$dm = new DataMap(['prop' => ['key1' => 'value1', 'key2' => 'value2']]);
		$targetArr = [];
		Bind::attrs($dm)->toArray($targetArr)
				->prop('prop', Mappers::valueAsBindDataClosure(function(BindData $bindData) use ($dm) {
					$this->assertEquals('value1', $bindData->reqString('key1'));
					$this->assertEquals('value2', $bindData->reqString('key2'));

					return ['key1' => 'holeradio1', 'key2' => 'holeradio2'];
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals(['prop' => ['key1' => 'holeradio1', 'key2' => 'holeradio2']], $targetArr);
	}

}