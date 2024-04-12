<?php

namespace n2n\bind\mapper\impl;

use PHPUnit\Framework\TestCase;
use n2n\util\magic\MagicTaskExecutionException;
use n2n\util\type\attrs\DataMap;
use n2n\bind\build\impl\Bind;
use n2n\util\magic\MagicContext;
use n2n\bind\err\BindMismatchException;
use n2n\util\StringUtils;
use n2n\bind\mapper\Mapper;
use PHPUnit\Util\Xml\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\bind\err\BindTargetException;
use n2n\bind\err\UnresolvableBindableException;

class PipeMapperTest extends TestCase {
	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrs() {
		//pipe mapper can chain multiple mapper, but count itself as single mapper

		$dataMap = new DataMap(['clo1' => null, 'clo2' => 'null', 'clo3' => '', 'clo4' => false, 'clo5' => true]);
		$tdm = new DataMap();

		$result = Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3', 'clo4', 'clo5'],
						Mappers::pipe(
								Mappers::valueNotNullClosure((function($value) use ($dataMap) {
									if ($value === true) {
										return 'TRUE';
									}
									if ($value === false) {
										return 'FALSE';
									}
									return $value;
								})),
								Mappers::valueClosure((function($value) use ($dataMap) {
									if ($value === 'TRUE' || $value === 'FALSE') {
										return $value;
									}
									if ($value === null) {
										return 'ERROR';
									}
									return 'OK';
								})),
								Mappers::cleanString(false, 2, 5)))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->hasErrors());

		$this->assertEquals('ERROR', $tdm->reqString('clo1', true));
		$this->assertEquals('OK', $tdm->reqString('clo2', true));
		$this->assertEquals('OK', $tdm->reqString('clo3', true));
		$this->assertEquals('FALSE', $tdm->reqString('clo4', true));
		$this->assertEquals('TRUE', $tdm->reqString('clo5', true));
	}

	function testAttrsFail() {
		//every bindable will only show the error of the first mapper that fails, even if it would fail on a later mapper too
		$dataMap = new DataMap(['clo1' => 'aa', 'clo2' => 'blibla', 'clo3' => 'blubb']);
		$tdm = new DataMap();

		$result = Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3'],
						Mappers::pipe(Mappers::cleanString(false, 3, 8),
								Mappers::cleanString(false, 4, 5)))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());


		$this->assertTrue($tdm->isEmpty());
		$this->assertTrue($result->hasErrors());
		$errorMap = $result->getErrorMap();

		//Mapper2 validations are not executed if there is an error from mapper1
		$this->assertCount(1, $errorMap->getChild('clo1')->getMessages());
		$this->assertEquals('Minlength [minlength = 3]', $errorMap->getChild('clo1')->jsonSerialize()['messages'][0]); //error from Mapper1

		//Mapper2 validations are executed if there is no error from mapper1, and can throw own Errors
		$this->assertCount(1, $errorMap->getChild('clo2')->getMessages());
		$this->assertEquals('Maxlength [maxlength = 5]', $errorMap->getChild('clo2')->jsonSerialize()['messages'][0]); //error from Mapper2

		//if Mapper1 and Mapper2 throw no error message then there is no count ;-)
		$this->assertCount(0, $errorMap->getChild('clo3')->getMessages());
	}

	/**
	 * @throws BindTargetException
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testThatFailingMapperPreventLaterMappersToBeReached() {
		$this->markTestSkipped('Test fails due to dirty implementation.');

		//if a mapper return false, all mapper behind will be not reached and executed
		//same test like testNotValidateMappersDoNotPreventExecutionOfFollowingMappers, but second mapper fails
		$dataMap = new DataMap(['clo1' => 'aaaa', 'clo2' => 'blibla', 'clo3' => 'blubb']);
		$tdm = new DataMap();

		$mapperMock = $this->createMock(Mapper::class);
		$mapperMock->expects($this->never())->method('map');

		$result = Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3'],
						Mappers::pipe(
								Mappers::cleanString(false, 8, 12),
								Mappers::bindableClosure(function($bindable) use ($dataMap) {
									$bindable->setValue($bindable->getValue());
									return false;
								}),
								$mapperMock))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());


		$this->assertTrue($tdm->isEmpty());
		$this->assertTrue($result->hasErrors());
		$errorMap = $result->getErrorMap();

		//Mapper1 validations are shown but mapper is aborted
		$this->assertCount(1, $errorMap->getChild('clo1')->getMessages());
		$this->assertEquals('Minlength [minlength = 8]', $errorMap->getChild('clo1')->jsonSerialize()['messages'][0]); //error from Mapper1

		//Mapper1 validations are shown but mapper is aborted
		$this->assertCount(1, $errorMap->getChild('clo2')->getMessages());
		$this->assertEquals('Minlength [minlength = 8]', $errorMap->getChild('clo2')->jsonSerialize()['messages'][0]); //error from Mapper1

		//Mapper1 validations are shown but mapper is aborted
		$this->assertCount(1, $errorMap->getChild('clo3')->getMessages());
		$this->assertEquals('Minlength [minlength = 8]', $errorMap->getChild('clo3')->jsonSerialize()['messages'][0]); //error from Mapper1
	}

	function testNotValidateMappersDoNotPreventExecutionOfFollowingMappers() {
		//all mapper behind will be reached even if Validation of a previous Mapper would fail
		//same test like testThatFailingMapperPreventLaterMappersToBeReached, but second mapper is true
		$dataMap = new DataMap(['clo1' => 'aaaa', 'clo2' => 'blibla', 'clo3' => 'blubb']);
		$tdm = new DataMap();

		$mapperMock = $this->createMock(Mapper::class);
		$mapperMock->expects($this->once())->method('map')->willReturn(true);

		Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3'],
						Mappers::pipe(Mappers::cleanString(false, 8, 12),
								Mappers::bindableClosure(function($bindable) use ($dataMap) {
									$bindable->setValue($bindable->getValue());
									return true;
								}),
								$mapperMock))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}


	function testCrazy() {
		//pipe inside pipe, string and closure mix convert to int :-D
		$dataMap = new DataMap(['clo1' => 'aaa', 'clo2' => 'blibla', 'clo3' => 'blubb', 'clo4' => 'blubber']);
		$tdm = new DataMap();

		Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3', 'clo4'],
						Mappers::pipe(
								Mappers::pipe(
										Mappers::cleanString(false, 3, 8),
										Mappers::valueClosure((function($value) use ($dataMap) {
												return StringUtils::reduce($value . '§§§', 6);
								}))),
								Mappers::pipe(
										Mappers::cleanString(true, 4, 6),
										Mappers::bindableClosure(function($bindable) use ($dataMap) {
												$bindable->setValue(mb_strlen($bindable->getValue()));
												return true;
								})),
								Mappers::int(false, 4, 6)))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertContainsOnly('int', $tdm->toArray());
		$this->assertContainsEquals(6, $tdm->toArray());

	}

	function testValidatorAsPipeParam() {
		$this->markTestSkipped('Test fails due to dirty implementation.');

		//have validators instead of mappers inside pipe, because pipe can do that :-D
		$dataMap = new DataMap(['clo1' => 'aaa', 'clo2' => 'blibla', 'clo3' => 'blubb@appagic.test', 'clo4' => 'blubber@n2n.test']);
		$tdm = new DataMap();

		$result = Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3', 'clo4'],
						Mappers::pipe(
								Validators::noSpecialChars(),
								Validators::email(),
						))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertContainsOnly('string', $tdm->toArray());

		$this->assertTrue($result->hasErrors());
		$errorMap = $result->getErrorMap();
		$this->assertCount(1, $errorMap->getChild('clo1')->getMessages());
		$this->assertEquals('Email', $errorMap->getChild('clo1')->jsonSerialize()['messages'][0]);
		$this->assertCount(1, $errorMap->getChild('clo2')->getMessages());
		$this->assertEquals('Email', $errorMap->getChild('clo2')->jsonSerialize()['messages'][0]);
		$this->assertCount(1, $errorMap->getChild('clo3')->getMessages());
		$this->assertEquals('Special Chars', $errorMap->getChild('clo3')->jsonSerialize()['messages'][0]);
		$this->assertCount(1, $errorMap->getChild('clo4')->getMessages());
		$this->assertEquals('Special Chars', $errorMap->getChild('clo4')->jsonSerialize()['messages'][0]);

	}

	function testMapperValidatorMix() {
		$this->markTestSkipped('Test fails due to dirty implementation.');

		//mappers and validators can given to pipe-mapper :-D
		$dataMap = new DataMap(['clo1' => 'aaa', 'clo2' => 'blibla', 'clo3' => 'blubb@appagic.test', 'clo4' => 'bli@n2n.test']);
		$tdm = new DataMap();

		$result = Bind::attrs($dataMap)->toAttrs($tdm)
				->optProps(['clo1', 'clo2', 'clo3', 'clo4'],
						Mappers::pipe(
								Mappers::cleanString(false, 4, 12),
								Validators::email(),
						))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertContainsOnly('string', $tdm->toArray());

		$this->assertTrue($result->hasErrors());
		$errorMap = $result->getErrorMap();
		$this->assertCount(1, $errorMap->getChild('clo1')->getMessages());
		$this->assertEquals('Minlength [minlength = 4]', $errorMap->getChild('clo1')->jsonSerialize()['messages'][0]);
		$this->assertCount(1, $errorMap->getChild('clo2')->getMessages());
		$this->assertEquals('Email', $errorMap->getChild('clo2')->jsonSerialize()['messages'][0]);
		$this->assertCount(1, $errorMap->getChild('clo3')->getMessages());
		$this->assertEquals('Maxlength [maxlength = 12]', $errorMap->getChild('clo3')->jsonSerialize()['messages'][0]);
		$this->assertCount(0, $errorMap->getChild('clo4')->getMessages());

	}
}