<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use InvalidArgumentException;
use n2n\util\magic\TaskInputMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\util\attr\MissingAttributeFieldException;
use n2n\bind\mapper\impl\string\mock\StringValObjMock;
use n2n\bind\mapper\impl\string\mock\StringableObjMock;

class GenericGeneratedValueMapperTest extends TestCase {

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 */
	function testAttrs() {
		//generateAlternateValue could be used to crop or extend values
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => 'Asdf', 'genericGeneratedValue3' => '§§ ',
				'genericGeneratedValue4' => new StringValObjMock('abc'), 'genericGeneratedValue5' => new StringableObjMock('cba')]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2', 'genericGeneratedValue3',
						'genericGeneratedValue3', 'genericGeneratedValue4', 'genericGeneratedValue5'],
						Mappers::generateAlternateValue(generationIfNullBaseName: 'blubb', fillStr: 'fill')
								->setNoSpecialChars(false)->setLowerCase(false))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		//null is replaced with BaseName
		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1', true));
		//if we set lowerCase param to false we can have also uppercase Chars
		$this->assertEquals('Asdf', $tdm->reqString('genericGeneratedValue2'));
		//if input is less than min length, fillStr is added. if after cleanup we had an empty string, then end value would be the fill string
		$this->assertEquals('§§-fill', $tdm->reqString('genericGeneratedValue3'));
		$this->assertSame('abc', $tdm->reqStringValueObject('genericGeneratedValue4', StringValObjMock::class)->toScalar());
		$this->assertInstanceOf(StringValObjMock::class, $tdm->reqStringValueObject('genericGeneratedValue4', StringValObjMock::class));
		$this->assertSame('cba', $tdm->reqString('genericGeneratedValue5'));
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsValGenerated() {
		//generateAlternateValue could be used to crop or extend values, if maxlength reached the base is cropped so "-" and numbers 2-9999 are always visible
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => 'min', 'genericGeneratedValue3' => 'max-holeradio', 'genericGeneratedValue4' => '§§§§', 'genericGeneratedValue5' => 'blubb']);
		$tdm = new DataMap();
		$used = ['blubb', 'somepath', 'blubb-4', 'blubb-5', 'blubb-6', 'blubb-7', 'blubb-8', 'blubb-9'];
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2', 'genericGeneratedValue3', 'genericGeneratedValue4', 'genericGeneratedValue5'],
						Mappers::generateAlternateValue(4, 7, null, 'blubb', (function($value) use ($dm, &$used) {
							$return = !in_array($value, $used, true);
							$used[] = $value;
							return $return;
						})))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertEquals('blubb-2', $tdm->reqString('genericGeneratedValue1', true));
		$this->assertEquals('min-blu', $tdm->reqString('genericGeneratedValue2'));
		$this->assertEquals('max-hol', $tdm->reqString('genericGeneratedValue3'));
		$this->assertEquals('blubb-3', $tdm->reqString('genericGeneratedValue4'));
		$this->assertEquals('blub-10', $tdm->reqString('genericGeneratedValue5'));
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testAttrsUniqueGenerationForceFailOverflow() {
		// this should be rare or not happens, because this means 9999 entries with this BaseName or input value already exist
		$dm = new DataMap(['genericGeneratedValue1' => 'a', 'genericGeneratedValue2' => null]);
		$tdm = new DataMap();
		$unique = [];
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2'],
						Mappers::generateAlternateValue(4, 8, 'blubb', 'path',
								function($value) use ($dm, &$unique) {
									$unique[] = $value;
									return false;
								}))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
		$this->assertTrue($tdm->isEmpty());
		$this->assertCount(19998, $unique); //2x9999Entries
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('genericGeneratedValue1')->getMessages()); //Mandatory error because generation fails
		$this->assertEquals('Mandatory', $errorMap->getChild('genericGeneratedValue1')->jsonSerialize()['messages'][0]); //mandatory violation
		$this->assertCount(1, $errorMap->getChild('genericGeneratedValue2')->getMessages()); //Mandatory error because generation fails
		$this->assertEquals('Mandatory', $errorMap->getChild('genericGeneratedValue2')->jsonSerialize()['messages'][0]); //mandatory violation
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameNotUnique() {
		// GenerationIfNullBaseName should be used with uniqueTester else it is possible that 2 generated genericGeneratedValues are the same
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => null, 'genericGeneratedValue3' => null, 'genericGeneratedValue4' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'blubb'))
				->prop('genericGeneratedValue2',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'blubb'))
				->prop('genericGeneratedValue3',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'bl ubb'))
				->prop('genericGeneratedValue4',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'bl ubb'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue2')); //exist but unique is not required
		$this->assertEquals('bl-ubb', $tdm->reqString('genericGeneratedValue3')); //special-char is replaced, basename don't exist
		$this->assertEquals('bl-ubb', $tdm->reqString('genericGeneratedValue4')); //special-char is replaced, basename exist unique is not required
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsUniqueGenerationIfNullBaseName() {
		// if GenerationIfNullBaseName and uniqueTester are used, genericGeneratedValue is generated, unique num may will be added,
		// if somehow a num was already taken (manual or generated), that num will be skipped and next free num is used
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => null, 'genericGeneratedValue3' => null, 'genericGeneratedValue4' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'blubb',
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['blubb-2', 'blubb-5']);
								})))
				->prop('genericGeneratedValue2',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'blubb',
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['blubb', 'blubb-2', 'blubb-5']);
								})))
				->prop('genericGeneratedValue3',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'blubb',
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['blubb', 'blubb-2', 'blubb-3', 'blubb-5']);
								})))
				->prop('genericGeneratedValue4',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null, fillStr: 'blubb',
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['blubb', 'blubb-2', 'blubb-3', 'blubb-4', 'blubb-5']);
								})))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb-3', $tdm->reqString('genericGeneratedValue2')); //basename exist, first alternate exist and is skipped
		$this->assertEquals('blubb-4', $tdm->reqString('genericGeneratedValue3')); //basename exist, first alternates exist and are skipped
		$this->assertEquals('blubb-6', $tdm->reqString('genericGeneratedValue4')); //basename exist, first free alternate is used
	}


	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameMin4Max12() {
		$dm = new DataMap(['genericGeneratedValue1' => 'blubb', 'genericGeneratedValue2' => 'a§%sdf',
				'genericGeneratedValue3' => 'aWayToLongString', 'genericGeneratedValue4' => '§§§§',
				'genericGeneratedValue5' => 'xy', 'genericGeneratedValue6' => 'aWayToLongString']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue2',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue3',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setLowerCase(true))
				->prop('genericGeneratedValue4',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue5',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue6',
						Mappers::generateAlternateValue(minlength: 4, maxlength: 12, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['awaytolongst', 'somepath']);
								}))->setLowerCase(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1')); //use lowercase
		$this->assertEquals('asdf', $tdm->reqString('genericGeneratedValue2')); //stripped special-chars
		$this->assertEquals('awaytolongst', $tdm->reqString('genericGeneratedValue3')); //reduced to max
		$this->assertEquals('path', $tdm->reqString('genericGeneratedValue4')); //fallback used
		$this->assertEquals('xy-path', $tdm->reqString('genericGeneratedValue5')); //extended to reach min
		$this->assertEquals('awaytolong-2', $tdm->reqString('genericGeneratedValue6')); //reduced max and added num count for unique
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameMin8Max255() {
		$dm = new DataMap(['genericGeneratedValue1' => 'Blubb', 'genericGeneratedValue2' => 'a§%sdf',
				'genericGeneratedValue3' => 'aWayToLongString', 'genericGeneratedValue4' => '§§§§',
				'genericGeneratedValue5' => 'xy', 'genericGeneratedValue6' => 'aWayToLongString']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 255, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setLowerCase(true))
				->prop('genericGeneratedValue2',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 255, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue3',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 255, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setLowerCase(true))
				->prop('genericGeneratedValue4',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 255, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue5',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 255, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								})))
				->prop('genericGeneratedValue6',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 255, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['awaytolongstring', 'somepath']);
								}))->setLowerCase(true))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb-path', $tdm->reqString('genericGeneratedValue1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-path', $tdm->reqString('genericGeneratedValue2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolongstring', $tdm->reqString('genericGeneratedValue3')); //nothing done
		$this->assertEquals('path-path', $tdm->reqString('genericGeneratedValue4')); //fallback used, extended to reach min
		$this->assertEquals('xy-path-path', $tdm->reqString('genericGeneratedValue5')); //extended(twice) to reach min
		$this->assertEquals('awaytolongstring-2', $tdm->reqString('genericGeneratedValue6')); //added num count for unique
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameMin8Max10SetFillStr() {
		$dm = new DataMap(['genericGeneratedValue1' => 'Blubb', 'genericGeneratedValue2' => 'a§%sdf',
				'genericGeneratedValue3' => 'aWayToLongString', 'genericGeneratedValue4' => '§§§§',
				'genericGeneratedValue5' => 'xy', 'genericGeneratedValue6' => 'aWayToLongString']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setLowerCase(true)->setFillStr('hoi'))
				->prop('genericGeneratedValue2',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setFillStr('hoi'))
				->prop('genericGeneratedValue3',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setLowerCase(true)->setFillStr('hoi'))
				->prop('genericGeneratedValue4',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setFillStr('hoi'))
				->prop('genericGeneratedValue5',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, []);
								}))->setFillStr('hoi'))
				->prop('genericGeneratedValue6',
						Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
								uniqueTester: (function($value) use ($dm) {
									return !in_array($value, ['awaytolong', 'somepath']);
								}))->setLowerCase(true)->setFillStr('hoi'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb-hoi', $tdm->reqString('genericGeneratedValue1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-hoi', $tdm->reqString('genericGeneratedValue2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolong', $tdm->reqString('genericGeneratedValue3')); //reduced to max
		$this->assertEquals('hoi-hoi-ho', $tdm->reqString('genericGeneratedValue4')); //fallback used, extended(twice) to reach min, reduced to max
		$this->assertEquals('xy-hoi-hoi', $tdm->reqString('genericGeneratedValue5')); //extended(twice) to reach min
		$this->assertEquals('awaytolo-2', $tdm->reqString('genericGeneratedValue6')); //reduced to max, added num count for unique
	}


	function testSetFillStrViolationToShort() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Invalid fill str, make sure it is at least.*long/i');
		Mappers::generateAlternateValue(minlength: 8, maxlength: 10, generationIfNullBaseName: null,
				fillStr: 'Blu&bb', uniqueTester: fn($v) => $this->fail())->setFillStr('');
	}

	function testMinMaxViolation() {
		//prevent epic fail
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxlength.*[greater|equals].*minlength/i');
		Mappers::generateAlternateValue(minlength: 8, maxlength: 6, generationIfNullBaseName: null,
				fillStr: 'Blu&bb', uniqueTester: fn($v) => $this->fail());
	}

	function testMaxToShortForGenerationIfNullBaseNameViolation() {
		//make sure we have at least a char where a minus sign and a num 2-9999 is added to make unique genericGeneratedValue
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxlength must be greater/i');
		Mappers::generateAlternateValue(minlength: 0, maxlength: 5, generationIfNullBaseName: null,
				fillStr: 'Blu&bb', uniqueTester: fn($v) => $this->fail());
	}


}