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
				->props(['string', 'int', 'array', 'obj'], Mappers::propsClosure(function($values) use ($dm) {
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
		$this->assertEquals(null, $obj->getObj());
	}

	function testOptProps() {
		$arr = [];
		Bind::attrs([])->toArray($arr)
				->optProps(['password'], Mappers::propsClosure(function($values) {
					$this->assertEquals([], $values);

					return ['passwordHash' => 'very good hash'];
				}))->exec($this->createMock(MagicContext::class));

		$this->assertEquals(['passwordHash' => 'very good hash'], $arr);


		$arr = [];
		Bind::attrs(['password' => 'huii'])->toArray($arr)
				->optProps(['password'], Mappers::propsClosure(function($values) {
					$this->assertEquals(['password' => 'huii'], $values);

					return ['passwordHash' => 'very good hash ' . $values['password']];
				}))->exec($this->createMock(MagicContext::class));

		$this->assertEquals(['passwordHash' => 'very good hash huii'], $arr);
	}

	function testOptPropsAny() {
		$resultArr = [];
		Bind::attrs([])->toArray($resultArr)
				->optProps(['password'], Mappers::propsClosureAny(function($values) {
					$this->assertEquals([], $values);

					return ['passwordHash' => 'very good hash 82o4jklj'];
				}))->exec($this->createMock(MagicContext::class));

		$this->assertEquals([], $resultArr);


		$resultArr = [];
		Bind::attrs(['huii' => 'hoi'])->toArray($resultArr)
				->optProps(['huii'], Mappers::propsClosureAny(function($values) {
					$this->assertEquals(['huii' => 'hoi'], $values);

					return ['huii' => 'very good hash'];
				}))->exec($this->createMock(MagicContext::class));

		$this->assertEquals(['huii' => 'very good hash'], $resultArr);
	}


	function testOptPropsEvery() {
		$resultArr = [];
		Bind::attrs(['huii' => 'hoi'])->toArray($resultArr)
				->optProps(['huii', 'huii2'], Mappers::propsClosureEvery(function($values) {
					$this->fail('Must not be executed.');
				}))->exec($this->createMock(MagicContext::class));

		$this->assertEquals(['huii' => 'hoi'], $resultArr);


		$resultArr = [];
		Bind::attrs(['huii' => 'hoi', 'huii2' => 'hoi2'])->toArray($resultArr)
				->optProps(['huii', 'huii2'], Mappers::propsClosureEvery(function($values) {
					$this->assertEquals(['huii' => 'hoi', 'huii2' => 'hoi2'], $values);

					return ['huii' => 'changed'];
				}))->exec($this->createMock(MagicContext::class));

		$this->assertEquals(['huii' => 'changed'], $resultArr);
	}
}