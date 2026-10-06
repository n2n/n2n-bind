<?php

namespace n2n\bind\mapper\impl\l10n;

use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use n2n\bind\mapper\impl\enum\mock\MockEnum;
use n2n\bind\err\BindMismatchException;
use n2n\l10n\N2nLocale;
use n2n\bind\err\UnresolvableBindableException;

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

	function testDocsUsage(): void {
		$result = Bind::values('de_CH', null)->map(Mappers::n2nLocale())->toArray()->exec();
		var_dump($result->get());

		$this->assertEquals([new N2nLocale('de_CH'), null], $result->get());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsVal(): void {
		$allowed = [new N2nLocale('de_CH'), new N2nLocale('mn')];

		$result = Bind::attrs(['n2nLocale1' => 'fr_FR', 'n2nLocale2' => null])
				->props(['n2nLocale1', 'n2nLocale2'], Mappers::n2nLocale(true, $allowed))
				->toArray()
				->exec();

		// result will be invalid with error messages provided for all n2nLocale properties.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('n2nLocale1')->isEmpty()); // false due to disallowed value
		var_dump($result->getErrorMap()->getChild('n2nLocale2')->isEmpty()); // false because mandatory

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('n2nLocale1')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('n2nLocale2')->isEmpty());
	}
}