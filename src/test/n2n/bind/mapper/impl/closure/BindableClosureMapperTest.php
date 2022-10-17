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

class BindableClosureMapperTest extends TestCase {
	function testBindableClosure() {
		$dm = new DataMap(['string' => 'test', 'obj' => null, 'int' => 321]);
		$obj = new BindTestClass();
		$obj->setString('wrong');

		Bind::attrs($dm)->toObj($obj)
				->prop('string', Mappers::bindableClosure(function (Bindable $bindable) use ($dm) {
					$this->assertEquals('test', $bindable->getValue());
					$bindable->setValue('asdf');
				}))
				->prop('int', Mappers::bindableClosure(function (Bindable $bindable) {
					$bindable->setExist(false);
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('asdf', $obj->getString());
		$this->assertEquals(null, $obj->getInt());
		$this->assertEquals([], $obj->getArray());


		Bind::attrs($dm)->toObj($obj)
				->prop('int', Mappers::bindableClosure(function ($bindable) use ($dm) {
					$bindable->setValue($bindable->getValue() + 1);
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertEquals(322, $obj->getInt());
	}

	function testBindableClosureWrongType() {
		$dm = new DataMap(['string' => 123]);
		$this->expectException(BindTargetException::class);
		Bind::attrs($dm)->toObj(new BindTestClass())
				->prop('string', Mappers::bindableClosure(function (Bindable $bindable) use ($dm) {
					$this->assertEquals(123, $bindable->getValue());
					$bindable->setValue(123);
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}
}