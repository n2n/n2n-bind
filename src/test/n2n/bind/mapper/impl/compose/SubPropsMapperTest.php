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

class SubPropsMapperTest extends TestCase {

	/**
	 * @throws \Throwable
	 */
	function testLogicalProp(): void {
		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar', 'ignored' => '!!']]);
		$targetDataMap = new DataMap();

		Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->prop('holeradio', Mappers::valueClosure(function ($value) {
					$this->assertEquals('foo', $value);
					return 'foo2';
				}))
				->logicalProp('sub', Mappers::subProps()
						->prop('huii', Mappers::valueClosure(function ($value) {
							$this->assertEquals('bar', $value);
							return 'bar2';
						})))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals('foo2', $targetDataMap->req('holeradio'));
		$this->assertEquals('bar2', $targetDataMap->req('sub/huii'));
		$this->assertFalse($targetDataMap->has('sub/ignored'));
	}

	function testLogicalRoot(): void {
		$dataMap = new DataMap(['holeradio' => 'foo', 'ignored' => '!!', 'sub' => ['huii' => 'bar', 'ignored' => '!!']]);
		$targetDataMap = new DataMap();

		Bind::attrs($dataMap)->toAttrs($targetDataMap)
				->logicalRoot(Mappers::subProps()
						->prop('holeradio', Mappers::valueClosure(function ($value) {
							$this->assertEquals('foo', $value);
							return 'foo2';
						}))
						->logicalProp('sub', Mappers::subProps()
								->prop('huii', Mappers::valueClosure(function ($value) {
									$this->assertEquals('bar', $value);
									return 'bar2';
								}))))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals('foo2', $targetDataMap->req('holeradio'));
		$this->assertEquals('bar2', $targetDataMap->req('sub/huii'));
		$this->assertFalse($targetDataMap->has('ignored'));
		$this->assertFalse($targetDataMap->has('sub/ignored'));
	}


}
