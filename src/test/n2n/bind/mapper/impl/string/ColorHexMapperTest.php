<?php

namespace n2n\bind\mapper\impl\string;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\type\attrs\InvalidAttributeException;
use n2n\util\type\attrs\MissingAttributeFieldException;

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
}