<?php

namespace n2n\bind\mapper\impl\op;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;

class MustExistIfMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperDynFalse() {

		$result = Bind::attrs(['prop' => 'holeradio'])
				->dynProp('conditionalReqProp', false,
						Mappers::mustExistIf(function() {
							return false;
						}),
						Mappers::propsClosure(function(array $props) {
							$this->assertEmpty($props);
							return ['prop' => 'super-holeradio'];
						}))
				->toArray()->exec();
		$this->assertTrue($result->isValid());
		$this->assertEquals('super-holeradio', $result->get()['prop']);

	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperDyn() {
		$this->expectException(UnresolvableBindableException::class);
		Bind::attrs(['prop' => 'holeradio'])
				->dynProp('conditionalReqProp', false,
						Mappers::mustExistIf(function() {
							return true;
						}),
						Mappers::propsClosure(function(array $props) {
							$this->assertEmpty($props);
							return ['prop' => 'super-holeradio'];
						}))
				->toArray()->exec();
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testMustExistIfMapperOptFalse() {

		$result = Bind::attrs(['prop' => 'holeradio'])
				->optProp('conditionalReqProp',
						Mappers::mustExistIf(function() {
							return false;
						}),
						Mappers::propsClosure(function(array $props) {
							$this->assertEmpty($props);
							return ['prop' => 'super-holeradio'];
						}))
				->toArray()->exec();
		$this->assertTrue($result->isValid());
		$this->assertEquals('super-holeradio', $result->get()['prop']);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperOpt() {
		$this->expectException(UnresolvableBindableException::class);

		Bind::attrs(['prop' => 'holeradio'])
				->optProp('conditionalReqProp',
						Mappers::mustExistIf(function() {
							return true;
						}),
						Mappers::propsClosure(function(array $props) {
							$this->assertEmpty($props);
							return ['prop' => 'super-holeradio'];
						}))
				->toArray()->exec();
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperProps() {
		$this->expectException(UnresolvableBindableException::class);

		Bind::attrs(['prop' => 'holeradio'])
				->optProps(['prop', 'conditionalReqProp'],
						Mappers::mustExistIf(function() {
							return true;
						}))
				->toArray()->exec();
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperPropsBool() {
		$this->expectException(UnresolvableBindableException::class);

		Bind::attrs(['prop' => 'holeradio'])
				->optProps(['prop', 'conditionalReqProp'],
						Mappers::mustExistIf(true))
				->toArray()->exec();
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperTrue() {
		$called = false;
		$called2 = false;
		$result = Bind::attrs(['prop' => 'holeradio'])
				->optProp('prop',
						Mappers::mustExistIf(function() use (&$called) {
							$called = true;
							return true;
						}),
						Mappers::valueClosure(function() use (&$called2) {
							$called2 = true;
						}))
				->toArray()->exec();
		$this->assertTrue($called, 'Mapper should have be called.');
		$this->assertTrue($called2, 'Mapper should have be called.');
		$this->assertTrue($result->isValid());
	}
}
