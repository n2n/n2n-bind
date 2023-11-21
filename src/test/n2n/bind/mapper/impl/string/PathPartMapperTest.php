<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\util\type\attrs\DataMap;
use InvalidArgumentException;
use n2n\util\magic\MagicTaskExecutionException;

class PathPartMapperTest extends TestCase {

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrs() {
		//pathPart is always changed to lowercase (GenerationIfNullBaseName would make other automatic changes, see other tests)
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => 'Asdf', 'pathPart3' => ' ', 'pathPart4' => 'abc']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2', 'pathPart3', 'pathPart3', 'pathPart4'],
						Mappers::pathPart(null, null, min: null))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals(null, $tdm->reqString('pathPart1', true));
		$this->assertEquals('asdf', $tdm->reqString('pathPart2'));
		$this->assertEquals('', $tdm->reqString('pathPart3'));
		$this->assertEquals('abc', $tdm->reqString('pathPart4'));
	}

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsValFail() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => 'min', 'pathPart3' => 'holeradio', 'pathPart4' => '§§§§', 'pathPart5' => 'blubb']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2', 'pathPart3', 'pathPart4', 'pathPart5'],
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'somepath']);
						}), null, true, 4, 8))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->hasErrors());
		$this->assertTrue($tdm->isEmpty());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('pathPart1')->getMessages()); //is empty
		$this->assertEquals('Mandatory', $errorMap->getChild('pathPart1')->jsonSerialize()['messages'][0]); //mandatory violation

		$this->assertCount(1, $errorMap->getChild('pathPart2')->getMessages()); //min chars not reached
		$this->assertEquals('Minlength [minlength = 4]', $errorMap->getChild('pathPart2')->jsonSerialize()['messages'][0]); //min violation

		$this->assertCount(1, $errorMap->getChild('pathPart3')->getMessages()); //more chars than max allows
		$this->assertEquals('Maxlength [maxlength = 8]', $errorMap->getChild('pathPart3')->jsonSerialize()['messages'][0]); //max violation

		$this->assertCount(1, $errorMap->getChild('pathPart4')->getMessages()); //contains special chars
		$this->assertEquals('Special Chars', $errorMap->getChild('pathPart4')->jsonSerialize()['messages'][0]); //special chars violation

		$this->assertCount(1, $errorMap->getChild('pathPart5')->getMessages()); //path already used, unique fails
		$this->assertEquals('Already Taken', $errorMap->getChild('pathPart5')->jsonSerialize()['messages'][0]); //unique violation
	}

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsUniqueGenerationIfNullBaseNameForceFailOverflow() {
		// this should be rare or not happens, because this means 9999 entries with this BaseName already exist
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null]);
		$tdm = new DataMap();
		$unique = [];
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2'],
						Mappers::pathPart(function($value) use ($dm, &$unique) {
							$unique[] = $value;
							return false;
						}, 'blubb', true, 4, 8))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->hasErrors());
		$this->assertTrue($tdm->isEmpty());
		$this->assertCount(19998, $unique); //2x9999Entries
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('pathPart1')->getMessages()); //Mandatory error because generation fails
		$this->assertEquals('Mandatory', $errorMap->getChild('pathPart1')->jsonSerialize()['messages'][0]); //mandatory violation
		$this->assertCount(1, $errorMap->getChild('pathPart2')->getMessages()); //Mandatory error because generation fails
		$this->assertEquals('Mandatory', $errorMap->getChild('pathPart2')->jsonSerialize()['messages'][0]); //mandatory violation
	}

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsGenerationIfNullBaseNameNotUnique() {
		// GenerationIfNullBaseName should be used with uniqueTester else it is possible that 2 generated pathParts are the same
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart(null, 'blubb', min: 4, max: 12))
				->prop('pathPart2',
						Mappers::pathPart(null, 'blubb', min: 4, max: 12))
				->prop('pathPart3',
						Mappers::pathPart(null, 'bl ubb', min: 4, max: 12))
				->prop('pathPart4',
						Mappers::pathPart(null, 'bl ubb', min: 4, max: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb', $tdm->reqString('pathPart2')); //exist but unique is not required
		$this->assertEquals('bl-ubb', $tdm->reqString('pathPart3')); //special-char is replaced, basename don't exist
		$this->assertEquals('bl-ubb', $tdm->reqString('pathPart4')); //special-char is replaced, basename exist unique is not required
	}

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsUniqueGenerationIfNullBaseName() {
		// if GenerationIfNullBaseName and uniqueTester are used, pathPart is generated, unique num may will be added,
		// if somehow a num was already taken (manual or generated), that num will be skipped and next free num is used
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb-2', 'blubb-5']);
						}), 'blubb', min: 4, max: 12))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'blubb-2', 'blubb-5']);
						}), 'blubb', min: 4, max: 12))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'blubb-2', 'blubb-3', 'blubb-5']);
						}), 'blubb', min: 4, max: 12))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'blubb-2', 'blubb-3', 'blubb-4', 'blubb-5']);
						}), 'blubb', min: 4, max: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb-3', $tdm->reqString('pathPart2')); //basename exist, first alternate exist and is skipped
		$this->assertEquals('blubb-4', $tdm->reqString('pathPart3')); //basename exist, first alternates exist and are skipped
		$this->assertEquals('blubb-6', $tdm->reqString('pathPart4')); //basename exist, first free alternate is used
	}


	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsGenerationIfNullBaseNameMin4Max12() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => null,
				'pathPart5' => null, 'pathPart6' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'Blubb', min: 4, max: 12))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'a§%sdf', min: 4, max: 12))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'aWayToLongString', min: 4, max: 12))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), '§§§§', min: 4, max: 12))
				->prop('pathPart5',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'xy', min: 4, max: 12))
				->prop('pathPart6',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['awaytolongst', 'somepath']);
						}), 'aWayToLongString', min: 4, max: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //use lowercase
		$this->assertEquals('asdf', $tdm->reqString('pathPart2')); //stripped special-chars
		$this->assertEquals('awaytolongst', $tdm->reqString('pathPart3')); //reduced to max
		$this->assertEquals('path', $tdm->reqString('pathPart4')); //fallback used
		$this->assertEquals('xy-path', $tdm->reqString('pathPart5')); //extended to reach min
		$this->assertEquals('awaytolong-2', $tdm->reqString('pathPart6')); //reduced max and added num count for unique
	}

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsGenerationIfNullBaseNameMin8Max255() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => null,
				'pathPart5' => null, 'pathPart6' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'Blubb', min: 8, max: 255))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'a§%sdf', min: 8, max: 255))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'aWayToLongString', min: 8, max: 255))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), '§§§§', min: 8, max: 255))
				->prop('pathPart5',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'xy', min: 8, max: 255))
				->prop('pathPart6',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['awaytolongstring', 'somepath']);
						}), 'aWayToLongString', min: 8, max: 255))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('blubb-path', $tdm->reqString('pathPart1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-path', $tdm->reqString('pathPart2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolongstring', $tdm->reqString('pathPart3')); //nothing done
		$this->assertEquals('path-path', $tdm->reqString('pathPart4')); //fallback used, extended to reach min
		$this->assertEquals('xy-path-path', $tdm->reqString('pathPart5')); //extended(twice) to reach min
		$this->assertEquals('awaytolongstring-2', $tdm->reqString('pathPart6')); //added num count for unique
	}

	/**
	 * @throws MagicTaskExecutionException
	 */
	function testAttrsGenerationIfNullBaseNameMin8Max10SetFillStr() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => null,
				'pathPart5' => null, 'pathPart6' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'Blubb', min: 8, max: 10)->setFillStr('hoi'))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'a§%sdf', min: 8, max: 10)->setFillStr('hoi'))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'aWayToLongString', min: 8, max: 10)->setFillStr('hoi'))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), '§§§§', min: 8, max: 10)->setFillStr('hoi'))
				->prop('pathPart5',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'xy', min: 8, max: 10)->setFillStr('hoi'))
				->prop('pathPart6',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['awaytolong', 'somepath']);
						}), 'aWayToLongString', min: 8, max: 10)->setFillStr('hoi'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue(!$result->hasErrors());

		$this->assertEquals('blubb-hoi', $tdm->reqString('pathPart1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-hoi', $tdm->reqString('pathPart2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolong', $tdm->reqString('pathPart3')); //reduced to max
		$this->assertEquals('hoi-hoi-ho', $tdm->reqString('pathPart4')); //fallback used, extended(twice) to reach min, reduced to max
		$this->assertEquals('xy-hoi-hoi', $tdm->reqString('pathPart5')); //extended(twice) to reach min
		$this->assertEquals('awaytolo-2', $tdm->reqString('pathPart6')); //reduced to max, added num count for unique
	}

	function testSetFillStrViolationUppercase() {
		//error message is the same for all setFillStr violations, but fail for different reasons
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/lowercase.*specialChars.*long/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', min: 8, max: 10)->setFillStr('Hoi');
	}

	function testSetFillStrViolationSpecialChar() {
		//error message is the same for all setFillStr violations, but fail for different reasons
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/lowercase.*specialChars.*long/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', min: 8, max: 10)->setFillStr('H§oi');
	}

	function testSetFillStrViolationToShort() {
		//error message is the same for all setFillStr violations, but fail for different reasons
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/lowercase.*specialChars.*long/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', min: 8, max: 10)->setFillStr('');
	}

	function testMinMaxViolation() {
		//prevent epic fail
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxlength.*[greater|equals].*minlength/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', min: 8, max: 6);
	}

	function testMaxToShortForGenerationIfNullBaseNameViolation() {
		//make sure we have at least a char where a minus sign and a num 2-9999 is added to make unique pathPart
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/generation.*maxlength must be greater/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', min: 0, max: 5);
	}

	function testAttrsValFailCustomErrorMessages() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => 'min', 'pathPart3' => 'holeradio', 'pathPart4' => '§§§§', 'pathPart5' => 'blubb']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2', 'pathPart3', 'pathPart4', 'pathPart5'],
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'somepath']);
						}), null, true, 4, 8)
								->setMaxlengthErrorMessage('CustomErrorMessage max')
								->setMinlengthErrorMessage('CustomErrorMessage min')
								->setUniqueErrorMessage('CustomErrorMessage unique')
								->setMandatoryErrorMessage('CustomErrorMessage req')
								->setNoSpecialCharsErrorMessage('CustomErrorMessage noSpecial'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->hasErrors());
		$this->assertTrue($tdm->isEmpty());
		$errorMap = $result->getErrorMap();

		$this->assertCount(1, $errorMap->getChild('pathPart1')->getMessages()); //is empty
		$this->assertEquals('CustomErrorMessage req', $errorMap->getChild('pathPart1')->jsonSerialize()['messages'][0]); //mandatory violation

		$this->assertCount(1, $errorMap->getChild('pathPart2')->getMessages()); //min chars not reached
		$this->assertEquals('CustomErrorMessage min', $errorMap->getChild('pathPart2')->jsonSerialize()['messages'][0]); //min violation

		$this->assertCount(1, $errorMap->getChild('pathPart3')->getMessages()); //more chars than max allows
		$this->assertEquals('CustomErrorMessage max', $errorMap->getChild('pathPart3')->jsonSerialize()['messages'][0]); //max violation

		$this->assertCount(1, $errorMap->getChild('pathPart4')->getMessages()); //contains special chars
		$this->assertEquals('CustomErrorMessage noSpecial', $errorMap->getChild('pathPart4')->jsonSerialize()['messages'][0]); //special chars violation

		$this->assertCount(1, $errorMap->getChild('pathPart5')->getMessages()); //path already used, unique fails
		$this->assertEquals('CustomErrorMessage unique', $errorMap->getChild('pathPart5')->jsonSerialize()['messages'][0]); //unique violation
	}

}