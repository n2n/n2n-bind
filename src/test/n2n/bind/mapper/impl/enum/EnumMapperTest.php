<?php

namespace n2n\bind\mapper\impl\enum;

use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\enum\mock\MockEnum;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\attr\MissingAttributeFieldException;
use n2n\bind\err\BindTargetException;
use n2n\util\type\custom\Undefined;

class EnumMapperTest extends TestCase {
	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws MissingAttributeFieldException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		$sdm = new DataMap(['timezone' => 'Europe/Zurich']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['timezone'], Mappers::enum(MockEnum::class))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertInstanceOf(MockEnum::class, $tdm->req('timezone'));
	}

	/**
	 * @throws InvalidAttributeException
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testEnumNull() {
		$sdm = new DataMap(['timezone' => null]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['timezone'], Mappers::enum(MockEnum::class))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertNull($tdm->req('timezone'));
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testEnumMandatoryOnNull() {
		$sdm = new DataMap(['timezone' => null]);
		$val = Undefined::val();

		$result = Bind::attrs($sdm)->toValue($val)
				->props(['timezone'], Mappers::enum(MockEnum::class, true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
		$this->assertInstanceOf(Undefined::class, $val);
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindTargetException
	 * @throws BindMismatchException
	 */
	function testEnumNoMandatoryOnNull() {
		$sdm = new DataMap(['timezone' => null]);
		$val = Undefined::val();

		$result = Bind::attrs($sdm)->toValue($val)
				->props(['timezone'], Mappers::enum(MockEnum::class, false))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());
		$this->assertNull($val);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsValFail() {
		$sdm = new DataMap(['timezone' => 'unknown/unknown']);
		$tdm = new DataMap();

		$this->expectException(BindMismatchException::class);

		Bind::attrs($sdm)->toAttrs($tdm)->props(['timezone'], Mappers::enum(MockEnum::class))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	function testDocsUsage(): void {
		$result = Bind::values('Europe/Zurich', null)->map(Mappers::enum(MockEnum::class))->toArray()->exec();
		var_dump($result->get());

		$this->assertTrue($result->isValid());
		$this->assertEquals([MockEnum::EUROPE_ZURICH, null], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['timezone' => null])
				->props(['timezone'], Mappers::enum(MockEnum::class, true))
				->toArray()
				->exec();

		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('timezone')->isEmpty()); // false because mandatory

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('timezone')->isEmpty());
	}
}