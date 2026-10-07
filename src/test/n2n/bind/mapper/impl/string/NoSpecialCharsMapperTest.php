<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use InvalidArgumentException;
use n2n\util\magic\TaskInputMismatchException;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\util\attr\MissingAttributeFieldException;
use n2n\bind\mapper\impl\string\mock\StringValObjMock;
use n2n\bind\mapper\impl\string\mock\StringableObjMock;
use n2n\test\case\N2nTestCaseTrait;

class NoSpecialCharsMapperTest extends TestCase {
	use N2nTestCaseTrait;

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 */
	function testAttrs() {
		//noSpecialChars is always changed to lowercase if param is set, and specialChars are stripped or converted
		$dm = new DataMap(['noSpecialChars1' => null, 'noSpecialChars2' => 'Jklö', 'noSpecialChars3' => ' ',
				'noSpecialChars4' => new StringValObjMock('a&b&c'), 'noSpecialChars5' => new StringableObjMock('cba')]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['noSpecialChars1', 'noSpecialChars2', 'noSpecialChars3', 'noSpecialChars3',
						'noSpecialChars4', 'noSpecialChars5'],
						Mappers::noSpecialChars(false, minlength: null, lowercaseOnly: true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertNull($tdm->reqString('noSpecialChars1', true));
		$this->assertSame('jkloe', $tdm->reqString('noSpecialChars2'));
		$this->assertNull($tdm->reqString('noSpecialChars3', true));
		$this->assertTypeSafeEquals(new StringValObjMock('abc'), $tdm->reqStringValueObject('noSpecialChars4', StringValObjMock::class));
		$this->assertSame('abc', $tdm->reqString('noSpecialChars4'));
		$this->assertSame('cba', $tdm->reqString('noSpecialChars5'));
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testAttrsValFail() {
		$dm = new DataMap(['noSpecialChars1' => null, 'noSpecialChars2' => 'min', 'noSpecialChars3' => 'holeradio']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['noSpecialChars1', 'noSpecialChars2', 'noSpecialChars3'],
						Mappers::noSpecialChars(true, 4, 8, lowercaseOnly: true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());
		$this->assertTrue($tdm->isEmpty());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('noSpecialChars1')->getMessages()); //is empty
		$this->assertEquals('Mandatory', $errorMap->getChild('noSpecialChars1')->jsonSerialize()['messages'][0]); //mandatory violation

		$this->assertCount(1, $errorMap->getChild('noSpecialChars2')->getMessages()); //min chars not reached
		$this->assertEquals('Minlength [minlength = 4]', $errorMap->getChild('noSpecialChars2')->jsonSerialize()['messages'][0]); //min violation

		$this->assertCount(1, $errorMap->getChild('noSpecialChars3')->getMessages()); //more chars than max allows
		$this->assertEquals('Maxlength [maxlength = 8]', $errorMap->getChild('noSpecialChars3')->jsonSerialize()['messages'][0]); //max violation
	}


	function testMinMaxViolation() {
		//prevent epic fail
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxlength.*[greater|equals].*minlength/i');
		Mappers::noSpecialChars(true, minlength: 8, maxlength: 6, lowercaseOnly: true);
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFailCustomErrorMessages() {
		$dm = new DataMap(['noSpecialChars1' => null, 'noSpecialChars2' => 'min', 'noSpecialChars3' => 'holeradio']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['noSpecialChars1', 'noSpecialChars2', 'noSpecialChars3'],
						Mappers::noSpecialChars(true, 4, 8, lowercaseOnly: true)
								->setMaxlengthErrorMessage('CustomErrorMessage max')
								->setMinlengthErrorMessage('CustomErrorMessage min')
								->setMandatoryErrorMessage('CustomErrorMessage req'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertFalse($result->isValid());
		$this->assertTrue($tdm->isEmpty());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('noSpecialChars1')->getMessages()); //is empty
		$this->assertEquals('CustomErrorMessage req', $errorMap->getChild('noSpecialChars1')->jsonSerialize()['messages'][0]); //mandatory violation

		$this->assertCount(1, $errorMap->getChild('noSpecialChars2')->getMessages()); //min chars not reached
		$this->assertEquals('CustomErrorMessage min', $errorMap->getChild('noSpecialChars2')->jsonSerialize()['messages'][0]); //min violation

		$this->assertCount(1, $errorMap->getChild('noSpecialChars3')->getMessages()); //more chars than max allows
		$this->assertEquals('CustomErrorMessage max', $errorMap->getChild('noSpecialChars3')->jsonSerialize()['messages'][0]); //max violation

	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsUsage(): void {
		$result = Bind::values(' Jklö ', null)->map(Mappers::noSpecialChars(minlength: null))
				->toArray()->exec($this->getMockBuilder(MagicContext::class)->getMock());
		var_dump($result->get());

		$this->assertSame(['jkloe', null], $result->get());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['a' => null, 'b' => 'min', 'c' => 'holeradio'])
				->props(['a', 'b', 'c'], Mappers::noSpecialChars(true, 4, 8, lowercaseOnly: true))
				->toArray()->exec();
		var_dump($result->getErrorMap()->getChild('a')->isEmpty()); // false becuase mandatory
		var_dump($result->getErrorMap()->getChild('b')->isEmpty()); // false because to short
		var_dump($result->getErrorMap()->getChild('c')->isEmpty()); // false because to long

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('a')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('b')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('c')->isEmpty());

	}

}