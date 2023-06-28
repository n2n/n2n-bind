<?php

namespace n2n\bind\mapper\impl\closure;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\target\mock\BindTestClassA;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;

class ValueClosureMapperTest extends TestCase {
	function testValueClosure() {
		$dm = new DataMap(['string' => 'test', 'obj' => null, 'int' => 321]);
		$obj = new BindTestClassA();
		$obj->setString('wrong');

		Bind::attrs($dm)->toObj($obj)
				->prop('string', Mappers::valueClosure(function($value) use ($dm) {
					$this->assertEquals('test', $value);
					return 'asdf';
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('asdf', $obj->getString());
		$this->assertEquals(null, $obj->getInt());
		$this->assertEquals([], $obj->getArray());
		$this->assertEquals(null, $obj->getA());

		Bind::attrs($dm)->toObj($obj)
				->prop('obj', Mappers::valueClosure(function($value) use ($dm, $obj) {
					return $obj;
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals($obj, $obj->getA());

		Bind::attrs($dm)->toObj($obj)
				->prop('int', Mappers::valueClosure(function($value) use ($dm) {
					return $value + 1;
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertEquals(322, $obj->getInt());
	}

	function testValueClosureWrongType() {
		$dm = new DataMap(['string' => 123]);
		$this->expectException(BindTargetException::class);
		Bind::attrs($dm)->toObj(new BindTestClassA())
				->prop('string', Mappers::valueClosure(function($value) use ($dm) {
					$this->assertEquals(123, $value);
					return 123;
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}
}