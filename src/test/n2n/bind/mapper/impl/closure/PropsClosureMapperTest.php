<?php

namespace n2n\bind\mapper\impl\closure;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\target\BindTestClass;
use n2n\bind\build\impl\Bind;
use n2n\util\magic\MagicContext;
use n2n\util\type\attrs\DataMap;
use n2n\bind\mapper\impl\Mappers;

class PropsClosureMapperTest extends TestCase {
	function testPropsClosure() {
		$objToWrite = new BindTestClass();
		$arrToWrite = ['int' => 1, 'string' => 'hello', 'arr' => [], 'obj' => $objToWrite];

		$dm = new DataMap(['string' => 'test', 'int' => 123, 'array' => $arrToWrite, 'obj' => $objToWrite]);
		$obj = new BindTestClass();
		$obj->setString('wrong');
		$obj->setObj($objToWrite);

		Bind::attrs($dm)->toObj($obj)
				->props(['string', 'int', 'array', 'obj'], Mappers::propsClosure(function ($values) use ($dm) {
					$this->assertEquals($values['string'], $dm->getString('string'));
					$this->assertEquals($values['int'], $dm->reqInt('int'));
					$this->assertEquals($values['array'], $dm->reqArray('array'));
					$this->assertEquals($values['obj'], $dm->req('obj'));

					return ['string' => 'str', 'obj' => null];
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('str', $obj->getString());
		$this->assertEquals(null, $obj->getInt());
		$this->assertEquals([], $obj->getArray());
		$this->assertEquals( null, $obj->getObj());
	}
}