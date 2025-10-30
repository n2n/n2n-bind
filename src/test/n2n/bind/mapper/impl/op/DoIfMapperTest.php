<?php

namespace n2n\bind\mapper\impl\op;

use PHPUnit\Framework\TestCase;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\plan\BindBoundary;
use n2n\util\type\attrs\AttributePath;

/**
 * this will test {@link DoIfMapper} and it's different params or options
 */
class DoIfMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testAbortMapper() {
		$result = Bind::attrs(['prop' => 'holeradio'])
				->prop('prop',
						Mappers::valueClosure(fn (string $v) => $v . '-1'),
						Mappers::doIf(function (BindBoundary $v) {
							$this->assertEquals('holeradio-1', $v->getBindable(new AttributePath(['prop']))->getValue());
							return true;
						}, abort: true),
						Mappers::doIf(fn (BindBoundary $v) => $v->getBindable(new AttributePath(['prop']))->setValue($v->getBindable(new AttributePath(['prop']))->getValue() . '-1')),
						Mappers::valueClosure(function () {
							$this->fail('Mapper should not be called.');
						}))
				->toArray()->exec();

		$this->assertFalse($result->isValid());
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
						Mappers::doIf(function (BindBoundary $v) {
							$this->assertEquals('holeradio-1', $v->getBindable(new AttributePath(['prop']))->getValue());
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
						Mappers::doIf(function (BindBoundary $v) {
							$this->assertEquals('holeradio-1', $v->getBindable(new AttributePath(['prop']))->getValue());
							return false;
						}, skipNextMappers: true),
						Mappers::valueClosure(fn (string $v) => $v . '-2'))
				->toArray()->exec();

		$this->assertSame(['prop' => 'holeradio-1-2'], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testChLogicalMapper() {
		$result = Bind::attrs(['prop' => 'holeradio', 'prop2' => 'holeradio!'])
				->prop('prop', Mappers::doIf(fn () => true, chLogical: true))
				->logicalProp('prop2', Mappers::doIf(fn () => true, chLogical: false))
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
				->prop('prop', Mappers::doIf(fn () => false, chLogical: true))
				->logicalProp('prop2', Mappers::doIf(fn () => false, chLogical: false))
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
				Mappers::doIf(function (BindBoundary $v) {
					$this->assertTrue($v->getBindable(new AttributePath(['prop']))->doesExist());
					$this->assertTrue($v->getBindable(new AttributePath(['prop']))->isValid());
					$this->assertEquals('holeradio-1', $v->getBindable(new AttributePath(['prop']))->getValue());
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
						Mappers::doIf(function (BindBoundary $v) {
							$this->assertEquals('holeradio-1', $v->getBindable(new AttributePath(['prop']))->getValue());
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
						Mappers::doIf(function (BindBoundary $bindBoundary) {
							$this->assertEquals('holeradio-1', $bindBoundary->getBindable(new AttributePath(['prop']))->getValue());
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
				->prop('prop', Mappers::doIf(fn (BindBoundary $bindBoundary) => true, chLogical: true))
				->logicalProp('prop2', Mappers::doIf(fn (BindBoundary $bindBoundary) => true, chLogical: false))
				->toArray()->exec();

		$this->assertSame(['prop2' => 'holeradio!'], $result->get());
	}


	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDoDeleteIfContainsInvalidProps() {
		$result = Bind::attrs(['prop' => 'holeradio','prop2' => 'blubb','prop3' => 'holeradio'])
				->props(['prop', 'prop2', 'prop3'],
						Mappers::valueClosure(fn(string $v) => $v . '-1'),
						Mappers::doIf(function (BindBoundary $bindBoundary) {
							foreach ($bindBoundary->getBindables() as $bindable) {
								if (!str_starts_with($bindable->getValue(), 'holeradio')) {
									return true;
								}
							}
							$this->fail();
						}, skipNextMappers: true, chExists: false),
						Mappers::valueClosure(fn(string $v) => $v . '-1'))
				->toArray()->exec();


		$this->assertEmpty($result->get());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDoDeleteIfContainsCorrectProps() {
		$result = Bind::attrs(['prop' => 'holeradio','prop2' => 'blubb','prop3' => 'holeradio'])
				->props(['prop', 'prop2', 'prop3'],
						Mappers::valueClosure(fn(string $v) => $v . '-1'),
						Mappers::doIf(function (BindBoundary $bindBoundary) {
							foreach ($bindBoundary->getBindables() as $bindable) {
								if (!str_ends_with($bindable->getValue(), '-1')) {
									return false;
								}
							}
							return true;
						}, skipNextMappers: false, chExists: true),
						Mappers::valueClosure(fn(string $v) => $v . '-1'))
				->toArray()->exec();


		$this->assertSame(['prop' => 'holeradio-1-1', 'prop2' => 'blubb-1-1', 'prop3' => 'holeradio-1-1'], $result->get());
	}


}