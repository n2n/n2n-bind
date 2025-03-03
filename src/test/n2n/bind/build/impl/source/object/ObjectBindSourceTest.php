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
namespace n2n\bind\build\impl\source\object;

use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\util\ex\IllegalStateException;
use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use AllowDynamicProperties;

class ObjectBindSourceTest extends TestCase{
	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testObj() {
		$source = new TestObject();
		$source->firstname = 'Tester' . "\x06" . 'ich ';
		$source->lastname = 'von  ' . "\t" . 'Testen ' . "\r\n";
		$source->favouriteNumber = 7;
		$source->hobbies = ['Ice Hockey', 'Tennis', 'Football'];

		$target = new TestObject();
		$result = Bind::obj($source)
				->toObj($target)
				->props(['firstname', 'lastname'], Mappers::cleanString())
				->prop('favouriteNumber', Mappers::valueClosure(fn($num) => $num + 1))
				->prop('hobbies', Mappers::valueClosure(function($arr) {
					array_pop($arr);
					return $arr;
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());


		$this->assertTrue($result->isValid());
		$this->assertEquals(spl_object_id($target), spl_object_id($result->get()));
		$this->assertEquals('Testerich', $target->firstname);
		$this->assertEquals('von Testen', $target->lastname);
		$this->assertEquals(8, $target->favouriteNumber);
		$this->assertEquals(['Ice Hockey', 'Tennis'], $target->hobbies);
		$this->assertEquals($target, $result->get());
	}

	function testObjValFail() {
		$source = new TestObject();
		$source->firstname = str_repeat('A', 256);
		$source->lastname = 'asdf';
		$source->hobbies = ['Ice Hockey', 'Tennis'];
		$source->favouriteNumber = 7;

		$target = new TestObject();
		$result = Bind::obj($source)
				->toObj($target)
				->prop('favouriteNumber', Mappers::int(true, 1, 10))
				->props(['firstname', 'lastname'], Mappers::cleanString(true, 11, 255))
				->prop('hobbies', Mappers::valueClosure(function($arr) {
					array_pop($arr);
					return $arr;
				}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());

		$errorMap = $result->getErrorMap();
		$this->assertFalse(isset($target->favouriteNumber));
		$this->assertCount(1, $errorMap->getChild('firstname')->getMessages());
		$this->assertCount(1, $errorMap->getChild('lastname')->getMessages());
		$this->assertFalse(isset($target->hobbies));

		$this->expectException(IllegalStateException::class);
		$result->get();
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testObjUnresolvableBindable() {
		$source = new TestObject();
		$source->huii = 'hoii';

		$this->expectException(UnresolvableBindableException::class);
		Bind::obj($source)
				->toObj(new TestObject())
				->prop('holeradio', Mappers::cleanString(true, 11, 255))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testObjOnSuccessCall(): void {
		$source = new TestObject();
		$source->firstname = 'valid';
		$target = new TestObject();

		$called = false;
		$result = Bind::obj($source)
				->toObj($target)
				->prop('firstname', Mappers::cleanString(true))
				->onSuccess(function ($arg) use (&$called) {
					$this->assertInstanceOf(TestObject::class, $arg);
					$this->assertEquals('valid', $arg->firstname);
					$called = true;
				})
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($called);
		$this->assertTrue($result->isValid());
	}

	function testObjOnSuccessNoCallOrError(): void {
		$source = new TestObject();
		$source->firstname = str_repeat('A', 256);
		$target = new TestObject();

		$result = Bind::obj($source)
				->toObj($target)
				->prop('firstname', Mappers::cleanString(maxlength: 255))
				->onSuccess(function () {
					$this->fail();
				})
				->exec($this->createMock(MagicContext::class));

		$this->assertFalse($result->isValid());
	}

	function testSourceObjToArray(): void {
		$source = new TestObject();
		$source->firstname = 'Testerich';
		$source->lastname = 'von Testen';

		$bindTask = Bind::obj($source)
				->dynProp('firstname', false, Mappers::valueClosure(fn ($v) => $v . '-updated'))
				->prop('lastname', Mappers::cleanString(true), Mappers::valueClosure(fn ($v) => $v . '-updated'))
				->toArray()
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($bindTask->isValid());

		$resultArr = $bindTask->get();

		$this->assertEquals('Testerich-updated', $resultArr['firstname']);
		$this->assertEquals('von Testen-updated', $resultArr['lastname']);
		$this->assertFalse(isset($resultArr['hobbies']));
		$this->assertFalse(isset($resultArr['favouriteNumber']));
	}

	function testSourceObjWithPath(): void {
		$source = new TestObject();
		$source->firstname = 'Testerich';
		$source->lastname = 'von Testen';
		$source->obj2 = new TestObject();
		$source->obj2->firstname = 'obj2Firstname';
		$source->obj2->lastname = 'obj2Lastname';

		$bindTask = Bind::obj($source)
				->dynProps(['firstname', 'lastname'], false, Mappers::valueClosure(fn ($v) => $v . '-updated'))
				->dynProps(['obj2/firstname', 'obj2/lastname'], false, Mappers::valueClosure(fn ($v) => $v . '-updated'))
				->toArray()
				->exec($this->createMock(MagicContext::class));

		$this->assertTrue($bindTask->isValid());

		$resultArr = $bindTask->get();
		$this->assertEquals('Testerich-updated', $resultArr['firstname']);
		$this->assertEquals('von Testen-updated', $resultArr['lastname']);
		$this->assertEquals('obj2Firstname-updated', $resultArr['obj2/firstname']);
		$this->assertEquals('obj2Lastname-updated', $resultArr['obj2/lastname']);
		$this->assertFalse(isset($resultArr['hobbies']));
		$this->assertFalse(isset($resultArr['favouriteNumber']));
	}

//	function testSourceObjExtension(): void {
//		$this->markTestSkipped('Skipped test: Parent class not supported by PropertiesAnalyzer');
//
//		$source = new TestObject();
//		$source->firstname = 'Testerich';
//		$source->lastname = 'von Testen';
//
//		$target = new TestObjExtension();
//		$bindTask = Bind::obj($source)
//				->dynProp('firstname', false, Mappers::valueClosure(fn ($v) => $v . '-updated'))
//				->prop('lastname', Mappers::cleanString(true), Mappers::valueClosure(fn ($v) => $v . '-updated'))
//				->toObj($target)
//				->exec($this->createMock(MagicContext::class));
//
//		$this->assertTrue($bindTask->isValid());
//
//		$resultArr = $bindTask->get();
//
//		$this->assertEquals('Testerich-updated', $resultArr['firstname']);
//		$this->assertEquals('von Testen-updated', $resultArr['lastname']);
//		$this->assertFalse(isset($resultArr['hobbies']));
//		$this->assertFalse(isset($resultArr['favouriteNumber']));
//		$this->assertFalse(isset($resultArr['newProp']));
//	}


}

#[AllowDynamicProperties]
class TestObject {
	public string $firstname;
	public string $lastname;
	public int $favouriteNumber;
	public array $hobbies;
	public TestObject $obj2;
}

class TestObjExtension extends TestObject {
	public string $newProp;
}