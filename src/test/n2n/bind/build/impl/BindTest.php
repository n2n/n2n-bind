<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\bind\build\impl;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\mapper\impl\Mappers;
use n2n\validation\build\impl\EmptyMagicContext;
use n2n\util\magic\MagicContext;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\magic\MagicTaskExecutionException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\util\type\attrs\MissingAttributeFieldException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\util\ex\IllegalStateException;
use n2n\bind\mapper\Mapper;
use n2n\validation\plan\ErrorMap;
use n2n\bind\mapper\MapResult;
use JsonSerializable;

class BindTest extends TestCase {


	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		$this->assertFalse(ctype_print("\x06"));

		$sdm = new DataMap(['firstname' => 'Tester' . "\x06" . 'ich ' , 'lastname' => 'von  ' . "\t" . 'Testen ' . "\r\n"]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['firstname', 'lastname'], Mappers::cleanString())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('Testerich', $tdm->reqString('firstname'));
		$this->assertEquals('von Testen', $tdm->reqString('lastname'));

		$this->assertEquals($tdm, $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFail() {
		$this->assertFalse(ctype_print("\x06"));

		$sdm = new DataMap([
			'firstname' => str_repeat('A', 256) ,
			'lastname' => 'von  ' . "\t" . 'Testen ' . "\r\n",
			'huii' => [ 'hoi' => null ],
			'hobby' => 'huii'
		]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['firstname', 'lastname', 'huii/hoi'],
						Mappers::cleanString(true, 11, 255))
				->prop('hobby', Mappers::cleanString(), Validators::closure(fn () => false))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());

		$this->assertTrue($tdm->isEmpty());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('firstname')->getMessages());
		$this->assertCount(1, $errorMap->getChild('lastname')->getMessages());
		$this->assertCount(1, $errorMap->getChild('huii')->getChild('hoi')->getMessages());
		$this->assertCount(1, $errorMap->getChild('hobby')->getMessages());



		$this->expectException(IllegalStateException::class);
		$result->get();
	}

	function testUnresolvableBindable() {
		$sdm = new DataMap([ 'huii' => 'hoii' ]);
		$tdm = new DataMap();

		$this->expectException(UnresolvableBindableException::class);

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['holeradio'], Mappers::cleanString(true, 11, 255))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

	}

	function testOptProp() {
		$sdm = new DataMap([ 'huii' => 'hoii' ]);

		$tdm = new DataMap();
		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->optProp('huii', Mappers::propsClosureAny(function ($valuesMap) {
					$this->assertEquals(['huii' => 'hoii'], $valuesMap);
					return ['huii' => 'holeradio'];
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals(['huii' => 'holeradio'], $tdm->toArray());

		$tdm = new DataMap();
		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->optProp('doesNotExist', Mappers::propsClosureAny(function ($valuesMap) {
					$this->fail();
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals([], $tdm->toArray());
	}

	function testDynProp() {
		$sdm = new DataMap([ 'huii' => 'hoii' ]);

		$tdm = new DataMap();
		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->dynProp('huii', true, Mappers::propsClosureAny(function ($valuesMap) {
					$this->assertEquals(['huii' => 'hoii'], $valuesMap);
					return ['huii' => 'holeradio'];
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals(['huii' => 'holeradio'], $tdm->toArray());

		$tdm = new DataMap();
		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->dynProp('doesNotExist', false, Mappers::propsClosureAny(function ($valuesMap) {
					$this->fail();
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals([], $tdm->toArray());
	}

	function testValue() {
		$sdm = new DataMap([ 'huii' => 'hoii' ]);
		$tdm = new DataMap();

		$resultValue = null;
		$result = Bind::values('huii ')->toValue($resultValue)
				->map(Mappers::cleanString())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());
		$this->assertEquals('huii', $resultValue);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testOnSuccessCall(): void {
		$called = false;
		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => 'valid'])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->onSuccess(function ($arg) use (&$called) {
					$this->assertInstanceOf(DataMap::class, $arg);
					$this->assertEquals('valid', $arg->req('prop1'));
					$called = true;
				})
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($called);
		$this->assertTrue($result->isValid());
	}


	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testOnSuccessNoCallOrError(): void {
		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => null])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->onSuccess(function () use (&$called) {
					$this->fail();
				})
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testPropsIfValid(): void {
		$neverMapper = $this->createMock(Mapper::class);
		$neverMapper->expects($this->never())->method('map');

		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => null, 'prop2' => 'valid'])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->ifValid()
				->prop('prop2', $neverMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
		$magicContext = $this->createMock(MagicContext::class);
		$errorMap = $result->getErrorMap();
		assert($errorMap instanceof ErrorMap);
		$this->assertEqualsIgnoringCase('Mandatory', (string) $errorMap->getChild('prop1')->getMessages()[0]);


		$onceMapper = $this->createMock(Mapper::class);
		$onceMapper->expects($this->once())->method('map')->willReturn(new MapResult(true));

		$dataMap = new DataMap();
		$result = Bind::attrs(['prop1' => null, 'prop2' => 'valid'])->toAttrs($dataMap)
				->prop('prop1', Mappers::cleanString(true))
				->prop('prop2', $onceMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testValuesIfValid(): void {
		$neverMapper = $this->createMock(Mapper::class);
		$neverMapper->expects($this->never())->method('map');

		$dataMap = new DataMap();
		$result = Bind::values(null, 'valid')->toAttrs($dataMap)
				->map(Mappers::cleanString(true))
				->ifValid()
				->map($neverMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());

		$onceMapper = $this->createMock(Mapper::class);
		$onceMapper->expects($this->once())->method('map')->willReturn(new MapResult(true));

		$dataMap = new DataMap();
		$result = Bind::values(null, 'valid')->toAttrs($dataMap)
				->map(Mappers::cleanString(true))
				->map($onceMapper)
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testInput(): void {
		$bindTask = Bind::attrs()
				->prop('prop1', Mappers::valueClosure(fn ($v) => $v . '-updated'))
				->toArray();

		$this->assertEquals(['prop1' => 'value-updated'], $bindTask->exec(input: ['prop1' => 'value'])->get());
		$this->assertEquals(['prop1' => 'value2-updated'], $bindTask->exec(input: ['prop1' => 'value2'])->get());

		$bindTask = Bind::values()
				->map(Mappers::valueClosure(fn ($v) => $v . '-updated'))
				->toArray();

		$this->assertEquals(['value-updated'], $bindTask->exec(input: ['value'])->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testJsonSerializeArrayAttrs(): void {
		$input = new class implements JsonSerializable {
			public function jsonSerialize(): array {
				return ['prop1' => 'value1'];
			}
		};

		$result = Bind::attrs($input)->prop('prop1')->toArray()
				->exec()->get();

		$this->assertEquals(['prop1' => 'value1'], $result);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testJsonSerializeStringAttrs(): void {
		$input = new class implements JsonSerializable {
			public function jsonSerialize(): string {
				return 'value1';
			}
		};

		$result = Bind::attrs($input)->prop('0')->toArray()
				->exec()->get();

		$this->assertEquals(['value1'], $result);
	}
}