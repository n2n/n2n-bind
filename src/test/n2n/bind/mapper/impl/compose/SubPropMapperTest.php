<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class SubPropMapperTest extends TestCase {


	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testSubProp(): void {
		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar']]);
		$targetDataMap = new DataMap();

		Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->prop('holeradio', Mappers::valueClosure(function ($value) {
					$this->assertEquals('foo', $value);
					return 'foo2';
				}))
				->prop('sub', Mappers::subProp()
						->prop('huii', Mappers::valueClosure(function ($value) {
							$this->assertEquals('bar', $value);
							return 'bar2';
						})))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals('foo2', $targetDataMap->req('holeradio'));
		$this->assertEquals('bar2', $targetDataMap->req('sub/huii'));
	}
}