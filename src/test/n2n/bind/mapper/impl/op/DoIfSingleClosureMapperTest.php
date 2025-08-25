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
use n2n\bind\plan\Bindable;
use n2n\validation\lang\ValidationMessages;

class DoIfSingleClosureMapperTest extends TestCase {

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

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoIfBindableClosureAbortMapper() {
		$result = Bind::attrs(['prop' => 'holeradio'])->prop('prop',
				Mappers::valueClosure(fn (string $v) => $v . '-1'),
				Mappers::doIfBindableClosure(function (Bindable $b) {
					$this->assertTrue($b->doesExist());
					$this->assertTrue($b->isValid());
					$this->assertEquals('holeradio-1', $b->getValue());
					return true;
				}, abort: true),
				Mappers::valueClosure(function () {
					$this->fail('Mapper should not be called.');
				}))->toArray()->exec();

		$this->assertFalse($result->isValid());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoIfBindableClosureConditionFalseMapperContinues() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::valueClosure(fn (string $v) => $v . '-1'),
						Mappers::doIfBindableClosure(function (Bindable $b) {
							$this->assertEquals('holeradio-1', $b->getValue());
							return false;
						}, abort: true),
						Mappers::valueClosure(fn (string $v) => $v . '-2'))
				->toArray()->exec();

		$this->assertTrue($result->isValid());
		$this->assertSame(['prop' => 'holeradio-1-2'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoIfBindableClosureSkipNextMapper() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop', Mappers::valueClosure(fn (string $v) => $v . '-1'),
						Mappers::doIfBindableClosure(function (Bindable $b) {
							$this->assertEquals('holeradio-1', $b->getValue());
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
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoIfBindableClosureChLogicalMapper() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop', Mappers::doIfBindableClosure(fn (Bindable $b) => true, chLogical: true))
				->logicalProp('prop2', Mappers::doIfBindableClosure(fn (Bindable $b) => true, chLogical: false))
				->toArray()->exec();

		$this->assertSame(['prop2' => 'holeradio!'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoIfInvalidAbortIfInvalid() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::bindableClosure(function (Bindable $bindable) {
							$bindable->addError(ValidationMessages::invalid());
						}),
						Mappers::doIfInvalid(abort: true),
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
	function testDoIfInvalidConditionFalseWhenValid() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::cleanString(true, 1, 255),
						Mappers::doIfInvalid(abort: true),
						Mappers::valueClosure(function (string $v) {
							return $v . '-ok';
						}))
				->toArray()->exec();

		$this->assertTrue($result->isValid());
		$this->assertSame(['prop' => 'holeradio-ok'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDoIfInvalidSkipNextMappersOnInvalid() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::valueClosure(fn () => null),
						Mappers::cleanString(true, 1, 255),
						Mappers::doIfInvalid(skipNextMappers: true),
						Mappers::valueClosure(function () {
							$this->fail('Mapper should be skipped');
						}))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
	}


	function testDoIfInvalidChLogicalOnInvalid() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop',
						Mappers::cleanString(true, 1, 5),
						Mappers::doIfInvalid(chLogical: false),
						Mappers::bindableClosure(function (Bindable $bindable) {
							$this->assertFalse($bindable->isLogical());
						}, true, false))
				->logicalProp('prop2',
						Mappers::cleanString(true, 1, 5),
						Mappers::doIfInvalid(chLogical: false)->setDirtySkipped(false),
						Mappers::bindableClosure(function (Bindable $bindable) {
							$this->assertFalse($bindable->isLogical());
						}, true, false))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
		$this->assertCount(1, $result->getErrorMap()->getChild('prop')->getMessages());
		$this->assertCount(1, $result->getErrorMap()->getChild('prop2')->getMessages());
	}
}