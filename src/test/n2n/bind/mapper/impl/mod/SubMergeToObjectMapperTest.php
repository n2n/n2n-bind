<?php

namespace n2n\bind\mapper\impl\mod;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\build\impl\target\mock\BindTestClassA;

class SubMergeToObjectMapperTest extends TestCase {

	/**
	 * @throws \Throwable
	 */
	function testMerge(): void {
		$targetObj = new MergeTestClass();
		$targetValue = null;
		Bind::attrs(['string' => 'test', 'int' => 123, 'array' => [2, 3]])
				->toValue($targetValue)
				->props(['string', 'int', 'array'])
				->root(Mappers::subMergeToObject(fn () => $targetObj))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals($targetObj, $targetValue);
		$this->assertEquals('test', $targetObj->string);
		$this->assertEquals('123', $targetObj->int);
		$this->assertEquals([2, 3], $targetObj->array);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testSubProp(): void {
		$targetObj = new MergeTestClass();
		$targetValue = null;
		Bind::attrs(['sub' => ['string' => 'test', 'int' => 123, 'array' => [2, 3]]])
				->toValue($targetValue)
				->props(['sub/string', 'sub/int', 'sub/array'])
				->prop('sub', Mappers::subMergeToObject(fn () => $targetObj))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals($targetObj, $targetValue);
		$this->assertEquals('test', $targetObj->string);
		$this->assertEquals('123', $targetObj->int);
		$this->assertEquals([2, 3], $targetObj->array);
	}
}
