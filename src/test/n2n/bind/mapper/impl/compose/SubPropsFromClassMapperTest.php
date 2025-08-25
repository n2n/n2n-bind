<?php

namespace n2n\bind\mapper\impl\compose;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\bind\mapper\impl\compose\mock\SimpleBaseRecord;
use n2n\util\type\custom\Undefined;
use n2n\bind\mapper\impl\compose\mock\KnownTypesRecord;
use n2n\util\uri\Url;
use n2n\util\calendar\Date;
use n2n\util\calendar\Time;
use n2n\bind\mapper\impl\valobj\ValueObjectMock;
use n2n\bind\mapper\impl\compose\mock\ValObjRecord;
use n2n\bind\mapper\impl\compose\mock\SubBaseRecord;

class SubPropsFromClassMapperTest extends TestCase {

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testBaseAttrs() {
		$srcAttrs = ['prop' => 'value1', 'nullableProp' => 'value2', 'undefNullableProp' => 'value3', 'mixedProp' => ['value4']];

		$targetAttrs = Bind::attrs($srcAttrs)
				->logicalRoot(Mappers::subPropsFromClass(SimpleBaseRecord::class))
				->toArray()->exec()->get();

		$this->assertSame($srcAttrs, $targetAttrs);
	}

	/**
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testExtendsAttrs() {
		$srcAttrs = ['subProp' => 'subvalue', 'prop' => 'value1', 'nullableProp' => 'value2', 'undefNullableProp' => 'value3',
				'mixedProp' => ['value4']];

		$targetAttrs = Bind::attrs($srcAttrs)
				->logicalRoot(Mappers::subPropsFromClass(SubBaseRecord::class))
				->toArray()->exec()->get();

		$this->assertSame($srcAttrs, $targetAttrs);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testBaseUndefinedAttrs() {
		$srcAttrs = ['prop' => 'value1', 'nullableProp' => 'value2', 'mixedProp' => array()];

		$targetAttrs = Bind::attrs($srcAttrs)
				->logicalRoot(Mappers::subPropsFromClass(SimpleBaseRecord::class))
				->toArray()->exec()->get();

		$this->assertSame(
				['prop' => 'value1', 'nullableProp' => 'value2', 'undefNullableProp' => Undefined::i(), 'mixedProp' => array()],
				$targetAttrs);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testBaseNullAttrs() {
		$srcAttrs = ['prop' => 'value1', 'nullableProp' => null, 'undefNullableProp' => null, 'mixedProp' => null];

		$result = Bind::attrs($srcAttrs)
				->logicalRoot(Mappers::subPropsFromClass(SimpleBaseRecord::class))
				->toArray()->exec();
		$targetAttrs = $result->get();

		$this->assertSame($srcAttrs, $targetAttrs);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testKnownTypesAttrs() {
		$srcAttrs = ['url' => 'https://www.n2n.rocks/', 'dateTime' => '2025-08-01 12:00:00',
				'dateTimeImmutable' => '2025-08-01 12:00:00', 'dateTimeInterface' => '2025-08-01 12:00:00',
				'date' => '2025-08-01', 'time' => '12:00:00'];

		$result = Bind::attrs($srcAttrs)
				->logicalRoot(Mappers::subPropsFromClass(KnownTypesRecord::class))
				->toArray()->exec();
		$targetAttrs = $result->get();

		$this->assertEquals(
				[
					'url' => Url::create('https://www.n2n.rocks/'),
					'dateTime' => new \DateTime('2025-08-01 12:00:00'),
					'dateTimeImmutable' => new \DateTimeImmutable('2025-08-01 12:00:00'),
					'dateTimeInterface' => new \DateTimeImmutable('2025-08-01 12:00:00'),
					'date' => new Date('2025-08-01'),
					'time' => new Time('12:00:00'),
				],
				$targetAttrs);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testValObjAttrs() {
		$srcAttrs = ['valueObject' => 'info@holeradio.ch'];

		$result = Bind::attrs($srcAttrs)
				->logicalRoot(Mappers::subPropsFromClass(ValObjRecord::class))
				->toArray()->exec();
		$targetAttrs = $result->get();

		$this->assertEquals(
				[
					'valueObject' => new ValueObjectMock('info@holeradio.ch'),
				],
				$targetAttrs);
	}
}