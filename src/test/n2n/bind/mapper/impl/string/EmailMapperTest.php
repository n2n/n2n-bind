<?php

namespace n2n\bind\mapper\impl\string;

use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;

class EmailMapperTest extends TestCase {
	function testAttrs() {
		$sdm = new DataMap(['email1' => 'test@t‏esterich.ch ', 'email2' => ' Test@testerich.ch ', 'email3' => ' TeSt@tesTerIch.ch']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['email1', 'email2', 'email3'], Mappers::email(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('test@testerich.ch', $tdm->reqString('email1'));
		$this->assertEquals('test@testerich.ch', $tdm->reqString('email2'));
		$this->assertEquals('test@testerich.ch', $tdm->reqString('email3'));
	}

	function testAttrsValFail() {
		$sdm = new DataMap(['email1' => 'asdf@', 'email2' => 'äöl@asdf.adsf', 'email3' => 'asdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdf@yxcv.ch']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['email1', 'email2', 'email3'], Mappers::email(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());

		$this->assertTrue($tdm->isEmpty());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('email1')->getMessages());
		$this->assertCount(1, $errorMap->getChild('email2')->getMessages());
		$this->assertCount(1, $errorMap->getChild('email3')->getMessages());
	}
}