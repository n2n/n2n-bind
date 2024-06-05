<?php

namespace n2n\bind\mapper\impl;

use n2n\bind\build\impl\Bind;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\mapper\impl\valobj\ValueObjectMock;
use n2n\validation\validator\impl\Validators;
use n2n\bind\plan\Bindable;

class ValidatorMapperTest extends TestCase  {


	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMultiValidInvalidGroup(): void {
		$calls = 0;
		$values = [];
		$result = Bind::attrs(['prop1' => 'valid-value'])->toArray($values)
				->prop('prop1', Validators::valueClosure(function ($value) use (&$calls) {
					$calls++;
					return true;
				}))
				->prop('prop1', Validators::valueClosure(function ($value) use (&$calls) {
					$calls++;
					return false;
				}))
				->prop('prop1', Validators::valueClosure(function ($value) {
					$this->fail('should not be called');
				}))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals(2, $calls);
		$this->assertFalse($result->isValid());
		$this->assertEquals([], $values);
	}

	function testMultiDirtyGroup(): void {
		$calls = 0;
		$values = [];
		$result = Bind::attrs(['prop1' => 'valid-value'])->toArray($values)
				->prop('prop1', Validators::valueClosure(function ($value) use (&$calls) {
					$calls++;
					return true;
				}))
				->prop('prop1', Mappers::bindableClosure(function (Bindable $b) use (&$calls) {
					$calls++;
					$b->setDirty(true);
					return false;
				}))
				->prop('prop1', Validators::valueClosure(function ($value) {
					$this->fail('should not be called');
				}))
				->exec($this->createMock(MagicContext::class));

		$this->assertEquals(2, $calls);
		$this->assertFalse($result->isValid());
		$this->assertEquals([], $values);
	}

}