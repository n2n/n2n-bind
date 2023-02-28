<?php

namespace n2n\bind\mapper\impl\enum;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\enum\mock\MockEnum;
use InvalidArgumentException;

class EnumMapperTest extends TestCase {
	function testAttrs() {
		$sdm = new DataMap(['timezone' => 'Europe/Zurich']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['timezone'], Mappers::enum(false, MockEnum::class))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertInstanceOf(MockEnum::class, $tdm->req('timezone'));
	}

	function testAttrsValFail() {
		$sdm = new DataMap(['timezone' => 'unknown/unknown']);
		$tdm = new DataMap();

		$this->expectException(InvalidArgumentException::class);

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['timezone'], Mappers::enum(false, MockEnum::class))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}
}