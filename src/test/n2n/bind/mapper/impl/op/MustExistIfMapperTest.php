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

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMustExistIfMapperFalseCallbackElseChExistToFalse(): void {
		$called = false;
		$called2 = false;
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio2'])
				->optProp('prop',
						Mappers::bindablesClosure(function (array $bindables) use (&$called) {
							$this->assertCount(1, $bindables);
							$this->assertTrue($bindables['prop']->doesExist());
							$called = true;
						}),
						Mappers::mustExistIf(function() use (&$called) {
							$called = true;
							return false;
						}, elseChExistToFalse: true),
						Mappers::bindablesClosure(function (array $bindables) use (&$called2) {
							$this->assertCount(1, $bindables);
							$this->assertFalse($bindables['prop']->doesExist());
							$called2 = true;
						}),
						Mappers::valueClosure(function() {
							$this->fail('Mapper should have be called, because Bindable must no longer exist.');
						}))
				->optProp('prop2',
						Mappers::mustExistIf(fn () => false),
						Mappers::valueClosure(fn (string $v) => $v . '-mapped'))
				->toArray()->exec();
		$this->assertTrue($called, 'Mapper should have be called.');
		$this->assertTrue($called2, 'Mapper 2 should have be called.');

		$this->assertTrue($result->isValid());
		$this->assertSame(['prop2' => 'holeradio2-mapped'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testMustExistIfMapperFalseElseChExistToFalse(): void {
		$called = false;
		$called2 = false;
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio2'])
				->optProp('prop',
						Mappers::bindablesClosure(function (array $bindables) use (&$called) {
							$this->assertCount(1, $bindables);
							$this->assertTrue($bindables['prop']->doesExist());
							$called = true;
						}),
						Mappers::mustExistIf(false, elseChExistToFalse: true),
						Mappers::bindablesClosure(function (array $bindables) use (&$called2) {
							$this->assertCount(1, $bindables);
							$this->assertFalse($bindables['prop']->doesExist());
							$called2 = true;
						}),
						Mappers::valueClosure(function() {
							$this->fail('Mapper should have be called, because Bindable must no longer exist.');
						}))
				->optProp('prop2',
						Mappers::mustExistIf(fn () => false),
						Mappers::valueClosure(fn (string $v) => $v . '-mapped'))
				->toArray()->exec();
		$this->assertTrue($called, 'Mapper should have be called.');
		$this->assertTrue($called2, 'Mapper 2 should have be called.');
		$this->assertTrue($result->isValid());
		$this->assertSame(['prop2' => 'holeradio2-mapped'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException|UnresolvableBindableException
	 */
	function testMustExistAllIfAnyExistSuccess(): void {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio2'])
				->optProps(['prop',	'prop2'], Mappers::mustExistAllIfAnyExist())
				->toArray()->exec();

		$this->assertTrue($result->isValid());
		$this->assertSame(['prop' => 'holeradio', 'prop2' => 'holeradio2'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMustExistAllIfAnyExistFailure(): void {
		$this->expectException(UnresolvableBindableException::class);

		Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio2'])
				->optProps(['prop',	'prop2', 'prop3'], Mappers::mustExistAllIfAnyExist())
				->toArray()->exec();
	}
}
