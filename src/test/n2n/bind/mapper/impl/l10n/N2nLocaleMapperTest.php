<?php

namespace n2n\bind\mapper\impl\l10n;

use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\enum\mock\MockEnum;
use n2n\bind\err\BindMismatchException;
use n2n\l10n\N2nLocale;

class N2nLocaleMapperTest extends TestCase {
	function testSimpleValue() {
		$sdm = new DataMap(['n2nLocale' => 'mn']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['n2nLocale'], Mappers::n2nLocale(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals(new N2nLocale('mn'), $tdm->req('n2nLocale'));
	}

	function testAllowedValues() {
		$sdm = new DataMap(['n2nLocale' => 'mn']);
		$tdm = new DataMap();

		$allowedN2nLocales = [new N2nLocale('de_CH'), new N2nLocale('mn')];

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['n2nLocale'], Mappers::n2nLocale(true, $allowedN2nLocales))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals(new N2nLocale('mn'), $tdm->req('n2nLocale'));
	}

	function testEnumNull() {
		$sdm = new DataMap(['n2nLocale' => null]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['n2nLocale'], Mappers::n2nLocale())
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertNull($tdm->req('n2nLocale'));
	}

	function testMissmatch() {
		$sdm = new DataMap(['n2nLocale' => 'l']);
		$tdm = new DataMap();

		$this->expectException(BindMismatchException::class);

		Bind::attrs($sdm)->toAttrs($tdm)->props(['n2nLocale'], Mappers::n2nLocale(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

	}

	function testValMandatoryFail() {
		$sdm = new DataMap(['n2nLocale' => null]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['n2nLocale'], Mappers::n2nLocale(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
	}

	function testValAllowedValuesFail() {
		$sdm = new DataMap(['n2nLocale' => 'ok_UI']);
		$tdm = new DataMap();


		$allowedN2nLocales = [new N2nLocale('de_CH'), new N2nLocale('mn')];

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['n2nLocale'], Mappers::n2nLocale(true, $allowedN2nLocales))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
	}
}