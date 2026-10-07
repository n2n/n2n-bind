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

class PathPartMapperTest extends TestCase {
	private \Closure $simpleClosure;

	function setUp(): void {
		$this->simpleClosure = function() {
			return true;
		};
	}

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
						Mappers::pathPart($this->simpleClosure,null))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertSame(null, $tdm->reqString('pathPart1', true));
		$this->assertEquals('asdf', $tdm->reqString('pathPart2'));
		$this->assertSame('path', $tdm->reqString('pathPart3', true));
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
						Mappers::pathPart($this->simpleClosure, 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart2',
						Mappers::pathPart($this->simpleClosure, 'blubb', minlength: 4, maxlength: 12))
				->prop('pathPart3',
						Mappers::pathPart($this->simpleClosure, 'bl ubb', minlength: 4, maxlength: 12))
				->prop('pathPart4',
						Mappers::pathPart($this->simpleClosure, 'bl ubb', minlength: 4, maxlength: 12))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('pathPart1')); //basename don't exist, nothing changed
		$this->assertEquals('blubb', $tdm->reqString('pathPart2')); //exist but unique is not required
		$this->assertEquals('bl-ubb', $tdm->reqString('pathPart3')); //special-char is replaced, basename don't exist
		$this->assertEquals('bl-ubb', $tdm->reqString('pathPart4')); //special-char is replaced, basename exist unique is not required
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
		$this->assertEquals('path', $tdm->reqString('pathPart4')); //fallback used
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
		$this->assertEquals('path-path', $tdm->reqString('pathPart4')); //fallback used, extended to reach min
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
							return !in_array($value, []);
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
		$this->assertEquals('hoi-hoi-ho', $tdm->reqString('pathPart4')); //fallback used, extended(twice) to reach min, reduced to max
		$this->assertEquals('xy-hoi-hoi', $tdm->reqString('pathPart5')); //extended(twice) to reach min
		$this->assertEquals('awaytolo-2', $tdm->reqString('pathPart6')); //reduced to max, added num count for unique
	}


	function testSetFillStrViolationToShort() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/Invalid fill str, make sure it is at least.*long/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', minlength: 8, maxlength: 10, fillStr: '');
	}

	function testMinMaxViolation() {
		//prevent epic fail
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxlength.*[greater|equals].*minlength/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', minlength: 8, maxlength: 6);
	}

	function testMaxToShortForGenerationIfNullBaseNameViolation() {
		//make sure we have at least a char where a minus sign and a num 2-9999 is added to make unique pathPart
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/maxLength need to be greater than \(numberSuffixOnRetry \+ maxRetries\) length/i');
		Mappers::pathPart(fn($v) => $this->fail(), 'Blu&bb', minlength: 0, maxlength: 5);
	}



	function testDocsUsage(): void {
		$result = Bind::values('Asdf', null)->map(Mappers::pathPart($this->simpleClosure,null))->toArray()->exec();
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