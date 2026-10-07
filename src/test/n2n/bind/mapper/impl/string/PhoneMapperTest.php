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

class PhoneMapperTest extends TestCase {

	/**
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindTargetException
	 * @throws MissingAttributeFieldException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		$sdm = new DataMap(['phone1' => '+49 (0)228-997799-0', 'phone2' => ' + 41 52 
				233 79 77 ', 'phone3' => ' 079  123  45  67 ', 'phone4' => ' 0041 79  123  45  67 ',
				'phone5' => '+49\ 228/9977990']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['phone1', 'phone2', 'phone3', 'phone4', 'phone5'], Mappers::phone(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('+49 (0)228-997799-0', $tdm->reqString('phone1'));
		$this->assertEquals('+41 52 233 79 77', $tdm->reqString('phone2'));
		$this->assertEquals('079 123 45 67', $tdm->reqString('phone3'));
		$this->assertEquals('+41 79 123 45 67', $tdm->reqString('phone4'));
		$this->assertEquals('+49 228 9977990', $tdm->reqString('phone5'));
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testAttrsValFail() {
		$sdm = new DataMap(['phone1' => '+ 41! 12 123 45 67a', 'phone2' => 'äöl@äsdf.adsf', 'phone3' => '+1415926535 8979323846 2643383279']);
		$tdm = new DataMap();

		$result = Bind::attrs($sdm)->toAttrs($tdm)->props(['phone1', 'phone2', 'phone3'], Mappers::phone(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());

		$this->assertTrue($tdm->isEmpty());

		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('phone1')->getMessages());
		$this->assertCount(1, $errorMap->getChild('phone2')->getMessages());
		$this->assertCount(1, $errorMap->getChild('phone3')->getMessages());
	}

	function testDocsUsage(): void {
		$result = Bind::values(' +41 (0) 52 233 79 77 ', null)->map(Mappers::phone())->toArray()->exec();
		var_dump($result->get());

		$this->assertSame(['+41 (0) 52 233 79 77', null], $result->get());
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testDocsVal(): void {
		$result = Bind::attrs(['phone1' => '+ 41! 12 123 45 67a', 'phone2' => 'äöl@äsdf.adsf', 'phone3' => null])
				->props(['phone1', 'phone2', 'phone3'], Mappers::phone(true))
				->toArray()
				->exec();

		// result will be invalid with error messages provided for all phone properties.
		var_dump($result->isValid()); // false
		var_dump($result->getErrorMap()->getChild('phone1')->isEmpty()); // false due to invalid format
		var_dump($result->getErrorMap()->getChild('phone2')->isEmpty()); // false due to invalid format
		var_dump($result->getErrorMap()->getChild('phone3')->isEmpty()); // false because mandatory

		$this->assertFalse($result->isValid());
		$this->assertFalse($result->getErrorMap()->getChild('phone1')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('phone2')->isEmpty());
		$this->assertFalse($result->getErrorMap()->getChild('phone3')->isEmpty());
	}
}