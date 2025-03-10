<?php

namespace n2n\bind\mapper\impl\op;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\target\mock\BindTestClassA;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindTargetException;
use n2n\bind\build\impl\target\mock\BindTestClass;
use n2n\bind\mapper\Mapper;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;

class DoIfValueClosureMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testAbortMapper() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::valueClosure(fn (string $v) => $v . '-1'),
						Mappers::doIfValueClosure(function (string $v) {
							$this->assertEquals('holeradio-1', $v);
							return true;
						}, abort: true),
						Mappers::valueClosure(function () {
							$this->fail('Mapper should not be called.');
						}))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoAbortIfNull() {
		$result = Bind::attrs(['prop' => null])
				->prop('prop',
						Mappers::doIfNull(abort: true),
						Mappers::valueClosure(function () {
							$this->fail('Mapper should not be called.');
						}))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoAbortIfNotNull() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::doIfNotNull(abort: true),
						Mappers::valueClosure(function () {
							$this->fail('Mapper should not be called.');
						}))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoAbortIfNullConditionFalse() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::doIfNull(abort: true),
						Mappers::valueClosure(function (string $v) {
							return $v . '-1';
						}))
				->toArray()->exec();

		$this->assertSame(['prop' => 'holeradio-1'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testSkipNextMapper() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::valueClosure(fn (string $v) => $v . '-1'),
						Mappers::doIfValueClosure(function (string $v) {
							$this->assertEquals('holeradio-1', $v);
							return true;
						}, skipNextMappers: true),
						Mappers::valueClosure(function () {
							$this->fail('Mapper should be skipped');
						}))
				->toArray()->exec();


		$this->assertSame(['prop' => 'holeradio-1'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testSkipNextMapperConditionFalse() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::valueClosure(fn (string $v) => $v . '-1'),
						Mappers::doIfValueClosure(function (string $v) {
							$this->assertEquals('holeradio-1', $v);
							return true;
						}, skipNextMappers: true),
						Mappers::valueClosure(fn (string $v) => $v . '-2'))
				->toArray()->exec();

		$this->assertSame(['prop' => 'holeradio-1'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testChLogicalMapper() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop', Mappers::doIfValueClosure(fn () => true, chLogical: true))
				->logicalProp('prop2', Mappers::doIfValueClosure(fn () => true, chLogical: false))
				->toArray()->exec();

		$this->assertSame(['prop2' => 'holeradio!'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testChLogicalConditionFalse() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop', Mappers::doIfValueClosure(fn () => false, chLogical: true))
				->logicalProp('prop2', Mappers::doIfValueClosure(fn () => false, chLogical: false))
				->toArray()->exec();

		$this->assertSame(['prop' => 'holeradio'], $result->get());
	}

}