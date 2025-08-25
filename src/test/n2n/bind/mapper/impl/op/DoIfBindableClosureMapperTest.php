<?php

namespace n2n\bind\mapper\impl\op;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\plan\Bindable;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;

class DoIfBindableClosureMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAbortMapper() {
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
	function testConditionFalseMapperContinues() {
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
	function testSkipNextMapper() {
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
	function testChLogicalMapper() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop', Mappers::doIfBindableClosure(fn (Bindable $b) => true, chLogical: true))
				->logicalProp('prop2', Mappers::doIfBindableClosure(fn (Bindable $b) => true, chLogical: false))
				->toArray()->exec();

		$this->assertSame(['prop2' => 'holeradio!'], $result->get());
	}
}


