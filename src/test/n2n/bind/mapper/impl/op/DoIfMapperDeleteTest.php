<?php

namespace n2n\bind\mapper\impl\op;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class DoIfMapperDeleteTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testSubPropDelete(): void {

		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar']]);
		$tdm = [];

		Bind::attrs($dataMap)->toArray($tdm)
				->prop('holeradio')
				->prop('sub', Mappers::deleteIf(true), Mappers::subProps()->prop('huii'))
				->exec($this->createMock(MagicContext::class));

		$this->assertArrayHasKey('holeradio', $tdm);
		$this->assertArrayNotHasKey('sub', $tdm);
		$this->assertEquals('foo', $tdm['holeradio']);

	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testSubPropNotDelete(): void {

		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar']]);
		$tdm = [];

		Bind::attrs($dataMap)->toArray($tdm)
				->prop('holeradio')
				->prop('sub', Mappers::deleteIf(false),
						Mappers::subProps()->prop('huii'))
				->exec($this->createMock(MagicContext::class));

		$this->assertArrayHasKey('holeradio', $tdm);
		$this->assertArrayHasKey('sub', $tdm);
		$this->assertEquals('foo', $tdm['holeradio']);
		$this->assertEquals(['huii' => 'bar'], $tdm['sub']);

	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDeleteIfClosure(): void {

		$dataMap = new DataMap(['prop1' => 'foo', 'prop2' => 'bar', 'prop3' => 'bar']);
		$tdm = [];
		$v = 0;

		Bind::attrs($dataMap)->toArray($tdm)
				->props(['prop1', 'prop3'], Mappers::deleteIf(function () use (&$v) {
					$v++;
					return ($v > 1);
				}))
				->prop('prop2', Mappers::deleteIf(function () use (&$v) {
					$v++;
					return ($v > 1);
				}))
				->exec($this->createMock(MagicContext::class));


		$this->assertArrayHasKey('prop1', $tdm);
		$this->assertArrayNotHasKey('prop2', $tdm);
		$this->assertArrayHasKey('prop3', $tdm);
		$this->assertEquals('foo', $tdm['prop1']);
		$this->assertEquals('bar', $tdm['prop3']);
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testSubPropDeleteClosure(): void {

		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar']]);
		$tdm = [];
		$v = 'foo';

		Bind::attrs($dataMap)->toArray($tdm)
				->prop('holeradio')
				->prop('sub', Mappers::deleteIf(function () use ($v) {
					$this->assertEquals('foo', $v);
					return true;
				}), Mappers::subProps()->prop('huii'))
				->exec($this->createMock(MagicContext::class));

		$this->assertArrayHasKey('holeradio', $tdm);
		$this->assertArrayNotHasKey('sub', $tdm);
		$this->assertEquals('foo', $tdm['holeradio']);
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testCascadeFalse(): void {

		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar', 'hoi' => 'foo']]);
		$tdm = [];


		Bind::attrs($dataMap)->toArray($tdm)
				->props(['holeradio', 'sub', 'sub/huii', 'sub/hoi'])
				->props(['holeradio', 'sub', 'sub/hoi'], Mappers::deleteIf(true, cascaded: false))
				->exec($this->createMock(MagicContext::class));

		$this->assertSame(['sub/huii' => 'bar'], $tdm);
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testCascadeTrue(): void {
		$dataMap = new DataMap(['holeradio' => 'foo', 'sub' => ['huii' => 'bar', 'hoi' => 'foo']]);
		$tdm = [];


		Bind::attrs($dataMap)->toArray($tdm)
				->props(['holeradio', 'sub', 'sub/huii', 'sub/hoi'])
				->props(['holeradio', 'sub', 'sub/hoi'], Mappers::deleteIf(true, cascaded: true))
				->exec($this->createMock(MagicContext::class));

		$this->assertEmpty($tdm);
	}
}
