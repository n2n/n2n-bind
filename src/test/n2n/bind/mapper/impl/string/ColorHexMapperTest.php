<?php

namespace n2n\bind\mapper\impl\string;

use n2n\util\attr\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\util\attr\MissingAttributeFieldException;

class ColorHexMapperTest extends TestCase {

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		$sdm = new DataMap(['colorHex1' => '#aaBBcc ', 'colorHex2' => ' #ABCDEF ', 'colorHex3' => ' #000000']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['colorHex1', 'colorHex2', 'colorHex3'], Mappers::colorHex(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('#aabbcc', $tdm->reqString('colorHex1'));
		$this->assertEquals('#abcdef', $tdm->reqString('colorHex2'));
		$this->assertEquals('#000000', $tdm->reqString('colorHex3'));
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFail() {
		$sdm = new DataMap(['colorHex1' => 'asdf', 'colorHex2' => 'aabbcc', 'colorHex3' => '#GG0000', 'colorHex4' => null]);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)
				->props(['colorHex1', 'colorHex2', 'colorHex3', 'colorHex4'], Mappers::colorHex(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());

		$this->assertTrue($tdm->isEmpty());

		$errorMap = $result->getErrorMap();

		$this->assertStringContainsString('Hex Color',  $errorMap->getChild('colorHex1')->getMessages()[0]);
		$this->assertStringContainsString('Hex Color',  $errorMap->getChild('colorHex2')->getMessages()[0]);
		$this->assertStringContainsString('Hex Color',  $errorMap->getChild('colorHex3')->getMessages()[0]);
		$this->assertStringContainsString('Mandatory',  $errorMap->getChild('colorHex4')->getMessages()[0]);
	}

	function testDocsUsage(): void {
		$result = Bind::values(' #aaBBcc ', null)->map(Mappers::colorHex())->toArray()->exec();
		var_dump($result->get());

		$this->assertSame(['#aabbcc', null], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['color1' => 'asdf', 'color2' => '#GG0000', 'color3' => null])
				->props(['color1', 'color2', 'color3'], Mappers::colorHex(true))
				->toArray()
				->exec();

		// result will be invalid with error messages provided for all color properties.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('color1')->isEmpty()); // false due to invalid format
		var_dump($result->getErrorMap()->getChild('color2')->isEmpty()); // false due to invalid format
		var_dump($result->getErrorMap()->getChild('color3')->isEmpty()); // false because mandatory

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('color1')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('color2')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('color3')->isEmpty());
	}
}