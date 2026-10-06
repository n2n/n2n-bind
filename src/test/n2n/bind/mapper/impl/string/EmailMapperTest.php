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

class EmailMapperTest extends TestCase {

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
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

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFail() {
		$sdm = new DataMap(['email1' => 'asdf@', 'email2' => 'äöl@äsdf.adsf', 'email3' => 'asdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdfasdf@yxcv.ch']);
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

	function testDocsUsage(): void {
		$result = Bind::values(' Test@Testerich.ch ', null)->map(Mappers::email())->toArray()->exec();
		var_dump($result->get());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['email1' => 'asdf@', 'email2' => 'no-at-sign', 'email3' => null])
				->props(['email1', 'email2', 'email3'], Mappers::email(true))
				->toArray()
				->exec();

		// result will be invalid with error messages provided for all email properties.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('email1')->isEmpty()); // false due to invalid format
		var_dump($result->getErrorMap()->getChild('email2')->isEmpty()); // false due to invalid format
		var_dump($result->getErrorMap()->getChild('email3')->isEmpty()); // false because mandatory

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('email1')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('email2')->isEmpty());
	}


}