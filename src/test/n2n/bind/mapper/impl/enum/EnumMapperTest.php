<?php

namespace n2n\bind\mapper\impl\enum;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\enum\mock\MockEnum;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\util\type\attrs\MissingAttributeFieldException;
use n2n\bind\err\BindTargetException;

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
}