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
use n2n\bind\err\MisconfiguredMapperException;
use n2n\validation\plan\ErrorMap;

class PathPartMapperTest extends TestCase {

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 */
	function testAttrs() {
		//pathPart is always changed to lowercase (GenerationIfNullBaseName would make other automatic changes, see other tests)
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => 'Asdf', 'pathPart3' => '§§ ', 'pathPart4' => 'abc']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2', 'pathPart3', 'pathPart3', 'pathPart4'],
						Mappers::pathPart(null,null))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertSame(null, $tdm->reqString('pathPart1', true));
		$this->assertEquals('asdf', $tdm->reqString('pathPart2'));
		$this->assertSame(null, $tdm->reqString('pathPart3', true));
		$this->assertEquals('abc', $tdm->reqString('pathPart4'));
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testAttrsUniqueGenerationIfNullBaseNameForceFailOverflow() {
		// this should be rare or not happens, because this means 9999 entries with this BaseName already exist
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null]);
		$tdm = new DataMap();
		$unique = [];
		$this->expectException(MisconfiguredMapperException::class);
		$this->expectExceptionMessage('could not find a unique value after');
		Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2'],
						Mappers::pathPart(function($value) use ($dm, &$unique) {
							$unique[] = $value;
							return false;
						}, 'blubb', minlength: 4, maxlength: 8, maxRetryNo: 9))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameNotUnique() {
		// GenerationIfNullBaseName should be used with uniqueTester else it is possible that 2 generated pathParts are the same
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart(null, 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart2',
						Mappers::pathPart(null, 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart3',
						Mappers::pathPart(null, 'bl ubb', minlength: 4, maxlength: 12))
				->prop('pathPart4',
						Mappers::pathPart(null, 'bl ubb', minlength: 4, maxlength: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb', $tdm->reqString('pathPart2')); //exist but unique is not required
		$this->assertEquals('bl-ubb', $tdm->reqString('pathPart3')); //special-char is replaced, basename don't exist
		$this->assertEquals('bl-ubb', $tdm->reqString('pathPart4')); //special-char is replaced, basename exist unique is not required
	}

	/**
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindMismatchException
	 */
	function testAttrsGenerationIfNullBaseNameIgnored() {
		$dm = new DataMap(['pathPart1' => 'holeradio', 'pathPart2' => 'Hole Radio', 'pathPart3' => 'Höle_Radiö ']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2', 'pathPart3'],
						Mappers::pathPart(null, 'Base Name', minlength: 4, maxlength: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('holeradio', $tdm->reqString('pathPart1'));
		$this->assertEquals('hole-radio', $tdm->reqString('pathPart2'));
		$this->assertEquals('hoele-radioe', $tdm->reqString('pathPart3'));
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfErrors() {
		$dm = new DataMap(['pathPart1' => 'h', 'pathPart2' => 'Hole Radio Hole Radio', 'pathPart3' => 'blubb']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2', 'pathPart3'],
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'path']);
						}), 'Base Name', minlength: 4, maxlength: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
		$errorMap = $result->getErrorMap();

		$this->assertEquals('Minlength [minlength = 4]', (string) $errorMap->getChild('pathPart1')->getMessages()[0]);
		$this->assertEquals('Maxlength [maxlength = 12]', (string) $errorMap->getChild('pathPart2')->getMessages()[0]);
		$this->assertEquals('Already Taken', (string) $errorMap->getChild('pathPart3')->getMessages()[0]);
	}


	/**
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindMismatchException
	 */
	function testModificationNotAllowedWithGenerationError() {
		$dm = new DataMap(['pathPart1' => 'holeradio', 'pathPart2' => 'Höle_Radiö ']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2'],
						Mappers::pathPart(null, 'Base Name', minlength: 4, maxlength: 12)
								->setValueModificationAllowed(false))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
		$errorMap = $result->getErrorMap();
		assert($errorMap instanceof ErrorMap);

		$this->assertNull($errorMap->getChild('pathPart1'));
		$this->assertEquals('Special Chars', (string) $errorMap->getChild('pathPart2')->getMessages()[0]);
	}

	/**
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindMismatchException
	 */
	function testModificationNotAllowedWithoutGenerationError() {
		$dm = new DataMap(['pathPart1' => 'holeradio', 'pathPart2' => 'Höle_Radiö ']);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['pathPart1', 'pathPart2'],
						Mappers::pathPart(null, null, minlength: 4, maxlength: 12)
								->setValueModificationAllowed(false))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertFalse($result->isValid());
		$errorMap = $result->getErrorMap();
		assert($errorMap instanceof ErrorMap);

		$this->assertNull($errorMap->getChild('pathPart1'));
		$this->assertEquals('Special Chars', (string) $errorMap->getChild('pathPart2')->getMessages()[0]);
	}

	/**
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindMismatchException
	 */
	function testModificationNotAllowedWithGeneration() {
		$dm = new DataMap(['pathPart1' => 'holeradio', 'pathPart2' => null, 'pathPart3' => 'holeradio', 'pathPart4' => null]);
		$result = Bind::attrs($dm)
				->props(['pathPart1', 'pathPart2'],
						Mappers::pathPart(null, 'Base Name', minlength: 4, maxlength: 12)
								->setValueModificationAllowed(false))
				->props(['pathPart3', 'pathPart4'],
						Mappers::pathPart(null, null, minlength: 4, maxlength: 12)
								->setValueModificationAllowed(false))
				->toArray()
				->exec($this->getMockBuilder(MagicContext::class)->getMock());


		$this->assertTrue($result->isValid());

		$this->assertSame(
				['pathPart1' => 'holeradio', 'pathPart2' => 'base-name', 'pathPart3' => 'holeradio', 'pathPart4' => null],
				$result->get());
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
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
						}), 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'blubb-2', 'blubb-5']);
						}), 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'blubb-2', 'blubb-3', 'blubb-5']);
						}), 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['blubb', 'blubb-2', 'blubb-3', 'blubb-4', 'blubb-5']);
						}), 'blubb', minlength: 4, maxlength: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb-3', $tdm->reqString('pathPart2')); //basename exist, first alternate exist and is skipped
		$this->assertEquals('blubb-4', $tdm->reqString('pathPart3')); //basename exist, first alternates exist and are skipped
		$this->assertEquals('blubb-6', $tdm->reqString('pathPart4')); //basename exist, first free alternate is used
	}


	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testMandatory() {
		$result = Bind::values('Some Name', null)->map(Mappers::pathPart(mandatory: true))->toArray()
				->exec();
		$this->assertFalse($result->isValid());
		$errorMap = $result->getErrorMap();
		assert($errorMap instanceof ErrorMap);
		$this->assertNull($errorMap->getChild(0));
		$this->assertFalse($errorMap->getChild(1)->isEmpty());
		$this->assertEquals('Mandatory', (string) $errorMap->getChild(1)->getMessages()[0]);
	}


	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameMin4Max12() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => '§§§§',
				'pathPart5' => null, 'pathPart6' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'Blubb', minlength: 4, maxlength: 12))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'a§%sdf', minlength: 4, maxlength: 12))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'aWayToLongString', minlength: 4, maxlength: 12))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), null, minlength: 4, maxlength: 12))
				->prop('pathPart5',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'xy', minlength: 4, maxlength: 12))
				->prop('pathPart6',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['awaytolongst', 'somepath']);
						}), 'aWayToLongString', minlength: 4, maxlength: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //use lowercase
		$this->assertEquals('asdf', $tdm->reqString('pathPart2')); //stripped special-chars
		$this->assertEquals('awaytolongst', $tdm->reqString('pathPart3')); //reduced to max
		$this->assertEquals(null, $tdm->reqString('pathPart4', true)); //changed to null
		$this->assertEquals('xy-path', $tdm->reqString('pathPart5')); //extended to reach min
		$this->assertEquals('awaytolong-2', $tdm->reqString('pathPart6')); //reduced max and added num count for unique
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameMin8Max255() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => '§§§§',
				'pathPart5' => null, 'pathPart6' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'Blubb', minlength: 8, maxlength: 255))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'a§%sdf', minlength: 8, maxlength: 255))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'aWayToLongString', minlength: 8, maxlength: 255))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), null, minlength: 8, maxlength: 255))
				->prop('pathPart5',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'xy', minlength: 8, maxlength: 255))
				->prop('pathPart6',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['awaytolongstring', 'somepath']);
						}), 'aWayToLongString', minlength: 8, maxlength: 255))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb-path', $tdm->reqString('pathPart1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-path', $tdm->reqString('pathPart2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolongstring', $tdm->reqString('pathPart3')); //nothing done
		$this->assertEquals(null, $tdm->reqString('pathPart4', true)); //stay null
		$this->assertEquals('xy-path-path', $tdm->reqString('pathPart5')); //extended(twice) to reach min
		$this->assertEquals('awaytolongstring-2', $tdm->reqString('pathPart6')); //added num count for unique
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsGenerationIfNullBaseNameMin8Max10SetFillStr() {
		$dm = new DataMap(['pathPart1' => null, 'pathPart2' => null, 'pathPart3' => null, 'pathPart4' => '§§§§',
				'pathPart5' => null, 'pathPart6' => null]);
		$tdm = new DataMap();
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('pathPart1',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'Blubb', minlength: 8, maxlength: 10, fillStr: 'hoi'))
				->prop('pathPart2',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'a§%sdf', minlength: 8, maxlength: 10, fillStr: 'hoi'))
				->prop('pathPart3',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'aWayToLongString', minlength: 8, maxlength: 10, fillStr: 'hoi'))
				->prop('pathPart4',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['hoi-hoi-ho', 'hoi-hoi-2', 'hoi-hoi-3',
									'hoi-hoi-4', 'hoi-hoi-5', 'hoi-hoi-6', 'hoi-hoi-7', 'hoi-hoi-8', 'hoi-hoi-9']);
						}), '§§§§', minlength: 8, maxlength: 10, fillStr: 'hoi'))
				->prop('pathPart5',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, []);
						}), 'xy', minlength: 8, maxlength: 10, fillStr: 'hoi'))
				->prop('pathPart6',
						Mappers::pathPart((function($value) use ($dm) {
							return !in_array($value, ['awaytolong', 'somepath']);
						}), 'aWayToLongString', minlength: 8, maxlength: 10, fillStr: 'hoi'))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb-hoi', $tdm->reqString('pathPart1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-hoi', $tdm->reqString('pathPart2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolong', $tdm->reqString('pathPart3')); //reduced to max
		$this->assertEquals('hoi-hoi-10', $tdm->reqString('pathPart4')); //fallback used, extended(twice) to reach min, reduced to max
		$this->assertEquals('xy-hoi-hoi', $tdm->reqString('pathPart5')); //extended(twice) to reach min
		$this->assertEquals('awaytolo-2', $tdm->reqString('pathPart6')); //reduced to max, added num count for unique
	}


	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testSetFillStrViolationToShort() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Invalid fill str, make sure it is at least.*long/i');
		Bind::values('Asdf', null)->map(Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', minlength: 8, maxlength: 10, fillStr: ''))->toArray()->exec();
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMinMaxViolation() {
		//prevent epic fail
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxlength.*[greater|equals].*minlength/i');
		Bind::values('Asdf', null)->map(Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', minlength: 8, maxlength: 6))->toArray()->exec();
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testMaxToShortForGenerationIfNullBaseNameViolation() {
		//make sure we have at least a char where a minus sign and a num 2-9999 is added to make unique pathPart
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxLength need to be greater than \(numberSuffixOnRetry \+ maxRetries\) length/i');
		Bind::values('Asdf', null)->map(Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', minlength: 0, maxlength: 5))->toArray()->exec();
	}


	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsUsage(): void {
		$result = Bind::values('Asdf', null)->map(Mappers::pathPart(null,null))->toArray()->exec();
		var_dump($result->get());

		$this->assertSame(['asdf', null], $result->get());
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testDocsGeneration(): void {
		$result = Bind::values(null)->map(Mappers::pathPart(fn ($v) => true, 'Blubb', minlength: 8, maxlength: 12))
				->toArray()->exec();
		var_dump($result->get());

		$this->assertSame(['blubb-path'], $result->get());
	}

}