<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\util\attr\DataMap;
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

	function testDocsUsage(): void {
		$src = new DataMap(['sub' => ['huii' => 'bar', 'ignored' => '!!']]);
		$target = new DataMap();

		$result = Bind::attrs($src)->toAttrs($target)
				->logicalProp('sub', Mappers::subProps()
						->prop('huii', Mappers::value(fn ($v) => $v . '2')))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals('bar2', $target->req('sub/huii'));
		$this->assertFalse($target->has('sub/ignored'));
	}

	function testDocsNested(): void {
		$src = new DataMap(['holeradio' => 'foo', 'ignored' => '!!', 'sub' => ['huii' => 'bar', 'ignored' => '!!']]);
		$target = new DataMap();

		$result = Bind::attrs($src)->toAttrs($target)
				->logicalRoot(Mappers::subProps()
						->prop('holeradio', Mappers::value(fn ($v) => $v . '2'))
						->logicalProp('sub', Mappers::subProps()
								->prop('huii', Mappers::value(fn ($v) => $v . '2'))))
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($result->isValid());
		$this->assertEquals('foo2', $target->req('holeradio'));
		$this->assertEquals('bar2', $target->req('sub/huii'));
		$this->assertFalse($target->has('ignored'));
		$this->assertFalse($target->has('sub/ignored'));
	}
}
