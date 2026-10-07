<?php

namespace n2n\bind\mapper\impl\string;

use n2n\bind\build\impl\Bind;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\magic\MagicContext;
use PHPUnit\Framework\TestCase;
use n2n\util\attr\DataMap;
use n2n\util\magic\TaskInputMismatchException;
use n2n\bind\err\UnresolvableBindableException;
use n2n\bind\err\BindMismatchException;
use n2n\util\attr\InvalidAttributeException;
use n2n\util\attr\MissingAttributeFieldException;
use n2n\bind\mapper\impl\string\mock\StringValObjMock;
use n2n\bind\mapper\impl\string\mock\StringableObjMock;
use n2n\bind\mapper\impl\string\mock\OrgObjMock;
use n2n\bind\mapper\impl\pipe\ChangeUntilLoopState;
use n2n\bind\mapper\Mapper;
use n2n\util\StringUtils;
use n2n\bind\mapper\impl\string\mock\PathPartObjMock;
use n2n\test\case\N2nTestCaseTrait;
use n2n\bind\err\MisconfiguredMapperException;
use n2n\bind\mapper\impl\string\mock\RetryValueChangersMock;
use n2n\bind\mapper\impl\pipe\RetryValueChangers;

class ChangeUntilValidMapperTest extends TestCase {
	use N2nTestCaseTrait;

	public OrgObjMock $org1;
	public OrgObjMock $org2;
	public OrgObjMock $org3;
	public OrgObjMock $org4;
	public ChangeUntilLoopState $changeUntilLoopState;

	function setUp(): void {
		$this->org1 = new OrgObjMock('Organisation 1');
		$this->org2 = new OrgObjMock('Organisation §§§ Super');
		$this->org3 = new OrgObjMock('$$$');
		$this->org3->path = new PathPartObjMock('blubb');
		$this->org4 = new OrgObjMock('Blubber');

	}

	static function inToBoMapper(OrgObjMock $org, &$invalidValues): Mapper {
		$mapper = Mappers::subProps();
		$mapper->prop('title', Mappers::cleanString(true), Mappers::unmarshal(StringValObjMock::class));
		$mapper->dynProp(
				'path',
				false,
				Mappers::changeUntilValid(
						RetryValueChangers::numberSuffixOnRetry(
								closure: (function($value) use (&$invalidValues) {
									$return = !in_array($value, $invalidValues);
									if ($return) {
										$invalidValues[] = $value;
									}
									return $return;
								}), min: 3, max: 63, fallBackOnNullValue: $org->title, fillStr: 'path',
								valueNumberSuffixSeparator: '-'),
						Mappers::noSpecialChars(), Mappers::cleanString()
				),
				Mappers::value(function(?string $value) {
					return new PathPartObjMock($value);
				})
//				Mappers::unmarshal(PathPartObjMock::class),
		);
		return $mapper;
	}

	/**
	 * @throws BindMismatchException
	 * @throws UnresolvableBindableException
	 */
	function testMapper() {
		$invalidValues = ['blubber', 'somepath', 'blubb-4', 'blubb-5', 'blubb-6', 'blubb-7', 'blubb-8', 'abc'];

		$org1 = $this->org1;
		$result1 = Bind::attrs(new DataMap(['title' => $org1->title, 'path' => $org1->path]))->toObj($org1)
				->logicalRoot(self::inToBoMapper($org1, $invalidValues))
				->exec();

		$this->assertTrue($result1->isValid());

		$org2 = $this->org2;
		$result2 = Bind::attrs(new DataMap(['title' => $org2->title, 'path' => $org2->path]))->toObj($org2)
				->logicalRoot(self::inToBoMapper($org2, $invalidValues))
				->exec();

		$this->assertTrue($result2->isValid());

		$org3 = $this->org3;
		$result3 = Bind::attrs(new DataMap(['title' => $org3->title, 'path' => $org3->path]))->toObj($org3)
				->logicalRoot(self::inToBoMapper($org3, $invalidValues))
				->exec();

		$this->assertTrue($result3->isValid());

		$org4 = $this->org4;
		$this->assertNull($org4->path);
		$result4 = Bind::attrs(new DataMap(['title' => $org4->title, 'path' => $org4->path]))->toObj($org4)
				->logicalRoot(self::inToBoMapper($org4, $invalidValues))
				->exec();

		$this->assertTrue($result4->isValid());

		$this->assertTypeSafeEquals(new StringValObjMock('Organisation 1'), $org1->title);
		$this->assertTypeSafeEquals(new PathPartObjMock('organisation-1'), $org1->path);
		$this->assertTypeSafeEquals(new StringValObjMock('Organisation §§§ Super'), $org2->title);
		$this->assertTypeSafeEquals(new PathPartObjMock('organisation-super'), $org2->path);
		$this->assertTypeSafeEquals(new StringValObjMock('$$$'), $org3->title);
		$this->assertTypeSafeEquals(new PathPartObjMock('blubb'), $org3->path);
		$this->assertTypeSafeEquals(new StringValObjMock('Blubber'), $org4->title);
		$this->assertTypeSafeEquals(new PathPartObjMock('blubber-2'), $org4->path);
	}

	/**
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindMismatchException
	 */
	function testAttrs() {
		//mapper will be used to crop or extend values
		$dm = new DataMap(['genericGeneratedValue1' => $this->org1->path, 'genericGeneratedValue2' => 'Asdf', 'genericGeneratedValue3' => '§§ ',
				'genericGeneratedValue4' => new StringValObjMock('abc'), 'genericGeneratedValue5' => new StringableObjMock('cba')]);
		$tdm = new DataMap();
		$invalidValues = ['blubber', 'somepath', 'blubb-4', 'blubb-5', 'blubb-6', 'blubb-7', 'blubb-8', 'abc'];
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: (function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		}), min: 3, max: 70, fallBackOnNullValue: 'blubb', fillStr: 'blubb', valueNumberSuffixSeparator: '-');

		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2', 'genericGeneratedValue3',
						'genericGeneratedValue3', 'genericGeneratedValue4', 'genericGeneratedValue5'],
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(lowercase: false), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->isValid());

		//null is replaced with BaseName
		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1', true, true));
		//if we set lowerCase param to false we can have also uppercase Chars
		$this->assertEquals('Asdf', $tdm->reqString('genericGeneratedValue2'));
		//if input is less than min length, fillStr is added. if after cleanup we had an empty string, then end value would be the fill string
		$this->assertEquals('blubb-2', $tdm->reqString('genericGeneratedValue3'));
		$this->assertSame('abc-2', $tdm->reqStringValueObject('genericGeneratedValue4', StringValObjMock::class)->toScalar());
		$this->assertInstanceOf(StringValObjMock::class, $tdm->reqStringValueObject('genericGeneratedValue4', StringValObjMock::class));
		$this->assertSame('cba', $tdm->reqString('genericGeneratedValue5'));
	}

	/**
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 * @throws InvalidAttributeException
	 * @throws BindMismatchException
	 */
	function testNonStringAttrs() {
		//mapper will be used to crop or extend values
		$dm = new DataMap(['genericGeneratedValue1' => $this->org3->path, 'genericGeneratedValue2' => null, 'genericGeneratedValue3' => '§§ ',
				'genericGeneratedValue4' => new StringValObjMock('abc'), 'genericGeneratedValue5' => new StringableObjMock('cba')]);
		$tdm = new DataMap();
		$invalidValues = ['blubber', 'somepath', 'blubb-4', 'blubb-5', 'blubb-6', 'blubb-7', 'blubb-8', 'abc'];
		$retry = RetryValueChangersMock::simpleNumberSuffixOnRetry((function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		}));
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2', 'genericGeneratedValue3',
						'genericGeneratedValue3', 'genericGeneratedValue4', 'genericGeneratedValue5'],
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(lowercase: false), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1', true, true));
		$this->assertNull($tdm->reqString('genericGeneratedValue2', true, true));
		$this->assertNull($tdm->reqString('genericGeneratedValue3', true, true));
		$this->assertSame('abc1', $tdm->reqStringValueObject('genericGeneratedValue4', StringValObjMock::class)->toScalar());
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
		// generateAlternateValue could be invalidValues to crop or extend values,
		// if maxlength reached the base is cropped so suffix "!" and retry-numbers 2-999 are always visible
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => 'min',
				'genericGeneratedValue3' => 'max-holeradio', 'genericGeneratedValue4' => ' ',
				'genericGeneratedValue5' => 'blubb']);
		$tdm = new DataMap();
		$retry = RetryValueChangers::numberSuffixOnRetry(
				closure: (function($value) use (&$invalidValues) {
					$return = !in_array($value, $invalidValues);
					if ($return) {
						$invalidValues[] = $value;
					}
					return $return;
				}),
				min: 4, max: 7, fallBackOnNullValue: 'blubb', fillStr: 'blue', valueNumberSuffixSeparator: '_',
				maxRetryNo: 999);

		$invalidValues = ['blubb', 'somepath', 'blubb_4', 'blubb_5', 'blubb_6', 'blubb_7', 'blubb_8', 'blubb_9'];
		$result = Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2', 'genericGeneratedValue3',
						'genericGeneratedValue3', 'genericGeneratedValue4', 'genericGeneratedValue5'],
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb_2', $tdm->reqString('genericGeneratedValue1'));
		$this->assertEquals('min_blu', $tdm->reqString('genericGeneratedValue2'));
		$this->assertEquals('max-hol', $tdm->reqString('genericGeneratedValue3'));
		$this->assertEquals('blubb_3', $tdm->reqString('genericGeneratedValue4'));
		$this->assertEquals('blub_10', $tdm->reqString('genericGeneratedValue5'));
	}

	/**
	 * @throws TaskInputMismatchException
	 */
	function testAttrsUniqueGenerationForceFailOverflow() {
		// this should be rare or not happens, because this means 999 entries with this BaseName or input value already exist
		$dm = new DataMap(['genericGeneratedValue1' => 'a', 'genericGeneratedValue2' => 'a']);
		$tdm = new DataMap();
		$unique = [];
		$this->expectException(MisconfiguredMapperException::class);
		$this->expectExceptionMessage('could not find a unique value after');
		Bind::attrs($dm)->toAttrs($tdm)
				->props(['genericGeneratedValue1', 'genericGeneratedValue2'],
						Mappers::changeUntilValid(RetryValueChangersMock::simpleNumberSuffixOnRetry((function($value) use (&$unique) {
							$unique[] = $value;
							return false;
						}), 999), Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsUniqueFallback() {
		// FallBack used sanitized and tested with uniqueTester sanitized value can result in same initial, but unique tester will change it
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => null, 'genericGeneratedValue3' => null, 'genericGeneratedValue4' => null, 'genericGeneratedValue5' => null]);
		$tdm = new DataMap();
		$invalidValues = [];
		$closure = (function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		});

		$retry1 = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 120, fallBackOnNullValue: 'blubb');
		$retry2 = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 120, fallBackOnNullValue: 'Blubb');
		$retry3 = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 120, fallBackOnNullValue: 'bl ubb');
		$retry4 = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 120, fallBackOnNullValue: 'bl ubb');
		$retry5 = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 120, fallBackOnNullValue: null);

		$mapper = Mappers::valueIfNotNull(fn(?string $string): string => StringUtils::hyphenated($string, false));


		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::changeUntilValid($retry1, $mapper, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue2',
						Mappers::changeUntilValid($retry2, $mapper, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue3',
						Mappers::changeUntilValid($retry3, $mapper, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue4',
						Mappers::changeUntilValid($retry4, $mapper, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue5',
						Mappers::changeUntilValid($retry5, $mapper))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1')); //basename don't exist, and is sanitized nothing changed
		$this->assertEquals('blubb-2', $tdm->reqString('genericGeneratedValue2')); //exist but not sanitized, after that it was not unique and changed
		$this->assertEquals('bl-ubb', $tdm->reqString('genericGeneratedValue3')); //special-char is replaced, but after that FallBack was unique
		$this->assertEquals('bl-ubb-2', $tdm->reqString('genericGeneratedValue4')); //special-char is replaced, after that it was not unique and changed
		$this->assertNull($tdm->reqString('genericGeneratedValue5', true)); //special-char is replaced, after that it was not unique and changed
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsSameFallBack() {
		// if FallBack and uniqueTester are used, genericGeneratedValue is generated, unique num may will be added,
		// if somehow a num was already taken (manual or generated), that num will be skipped and next free num is used
		$dm = new DataMap(['genericGeneratedValue1' => null, 'genericGeneratedValue2' => null, 'genericGeneratedValue3' => null, 'genericGeneratedValue4' => null]);
		$tdm = new DataMap();
		$invalidValues = ['blubb-2', 'blubb-5'];
		$closure = (function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		});
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 120, fallBackOnNullValue: 'blubb');

		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue2',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue3',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue4',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
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
	function testAttrsInputFallBackMixMin4Max12() {
		$dm = new DataMap(['genericGeneratedValue1' => 'blubb', 'genericGeneratedValue2' => 'a§%sdf',
				'genericGeneratedValue3' => 'aWayToLongString', 'genericGeneratedValue4' => '§§§§',
				'genericGeneratedValue5' => 'xy', 'genericGeneratedValue6' => 'aWayToLongString']);
		$tdm = new DataMap();
		$invalidValues = ['somepath'];
		$closure = (function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		});
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 4, max: 12, fallBackOnNullValue: 'path');

		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue2',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue3',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue4',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue5',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue6',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb', $tdm->reqString('genericGeneratedValue1')); //use lowercase
		$this->assertEquals('asdf', $tdm->reqString('genericGeneratedValue2')); //stripped special-chars
		$this->assertEquals('awaytolongst', $tdm->reqString('genericGeneratedValue3')); //reduced to max
		$this->assertEquals('path', $tdm->reqString('genericGeneratedValue4')); //FallBack used
		$this->assertEquals('xy-path', $tdm->reqString('genericGeneratedValue5')); //extended to reach min
		$this->assertEquals('awaytolong-2', $tdm->reqString('genericGeneratedValue6')); //reduced max and added num count for unique
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsInputFallBackMixMin8Max255() {
		$dm = new DataMap(['genericGeneratedValue1' => 'Blubb', 'genericGeneratedValue2' => 'a§%sdf',
				'genericGeneratedValue3' => 'aWayToLongString', 'genericGeneratedValue4' => '§§§§',
				'genericGeneratedValue5' => 'xy', 'genericGeneratedValue6' => 'aWayToLongString']);
		$tdm = new DataMap();
		$invalidValues = ['somepath'];
		$closure = (function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		});
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 8, max: 255, fallBackOnNullValue: 'path');


		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue2',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue3',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue4',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue5',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue6',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb-path', $tdm->reqString('genericGeneratedValue1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-path', $tdm->reqString('genericGeneratedValue2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolongstring', $tdm->reqString('genericGeneratedValue3')); //nothing done
		$this->assertEquals('path-path', $tdm->reqString('genericGeneratedValue4')); //FallBack used, extended to reach min
		$this->assertEquals('xy-path-path', $tdm->reqString('genericGeneratedValue5')); //extended(twice) to reach min
		$this->assertEquals('awaytolongstring-2', $tdm->reqString('genericGeneratedValue6')); //added num count for unique
	}

	/**
	 * @throws BindMismatchException
	 * @throws InvalidAttributeException
	 * @throws MissingAttributeFieldException
	 * @throws UnresolvableBindableException
	 */
	function testAttrsInputFallBackMixMin8Max10SetFillStr() {
		$dm = new DataMap(['genericGeneratedValue1' => 'Blubb', 'genericGeneratedValue2' => 'a§%sdf',
				'genericGeneratedValue3' => 'aWayToLongString', 'genericGeneratedValue4' => '§§§§',
				'genericGeneratedValue5' => 'xy', 'genericGeneratedValue6' => 'aWayToLongString']);
		$tdm = new DataMap();
		$invalidValues = ['somepath'];
		$closure = (function($value) use (&$invalidValues) {
			$return = !in_array($value, $invalidValues);
			if ($return) {
				$invalidValues[] = $value;
			}
			return $return;
		});
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: $closure, min: 8, max: 10, fallBackOnNullValue: 'hui', fillStr: 'hoi');

		$result = Bind::attrs($dm)->toAttrs($tdm)
				->prop('genericGeneratedValue1',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue2',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue3',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue4',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue5',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->prop('genericGeneratedValue6',
						Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());

		$this->assertTrue($result->isValid());

		$this->assertEquals('blubb-hoi', $tdm->reqString('genericGeneratedValue1')); ////use lowercase and extended to reach min
		$this->assertEquals('asdf-hoi', $tdm->reqString('genericGeneratedValue2')); //stripped special-chars and extended to reach min
		$this->assertEquals('awaytolong', $tdm->reqString('genericGeneratedValue3')); //reduced to max
		$this->assertEquals('hui-hoi-ho', $tdm->reqString('genericGeneratedValue4')); //FallBack used, extended(twice) to reach min, reduced to max
		$this->assertEquals('xy-hoi-hoi', $tdm->reqString('genericGeneratedValue5')); //extended(twice) to reach min
		$this->assertEquals('awaytolo-2', $tdm->reqString('genericGeneratedValue6')); //reduced to max, added num count for unique
	}

	/*
		function testSetFillStrViolationToShort() {
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessageMatches('/Invalid fill str, make sure it is at least.*long/i');
			Mappers::generateAlternateValue(minlength: 8, maxlength: 10, FallBack: null,
					fillStr: '', uniqueTester: fn($v) => $this->fail());
		}
	
		function testMinMaxViolation() {
			//prevent epic fail
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessageMatches('/maxlength.*[greater|equals].*minlength/i');
			Mappers::generateAlternateValue(minlength: 8, maxlength: 6, FallBack: null,
					fillStr: 'Blu&bb', uniqueTester: fn($v) => $this->fail());
		}
	
		function testMaxToShortForFallBackViolation() {
			//make sure we have at least a char where a minus sign and a num 2-9999 is added to make unique genericGeneratedValue
			$this->expectException(InvalidArgumentException::class);
			$this->expectExceptionMessageMatches('/maxLength need to be greater than/i');
			Mappers::generateAlternateValue(minlength: 0, maxlength: 5, FallBack: null,
					fillStr: 'Blu&bb', uniqueTester: fn($v) => $this->fail());
	}
	*/

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsUniqueSlugGeneration(): void {
		$taken = [];
		$isUnique = function (string $value) use (&$taken) {
			if (in_array($value, $taken, true)) return false;
			$taken[] = $value;
			return true;
		};
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: $isUnique, min: 4, max: 30, fallBackOnNullValue: 'slug',
				valueNumberSuffixSeparator: '-');
		$tdm = new DataMap();

		$result = Bind::attrs(['a' => null, 'b' => null])->toAttrs($tdm)
				->props(['a', 'b'], Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		var_dump($result->isValid(), $tdm->reqString('a', true, true), $tdm->reqString('b', true, true));

		$this->assertTrue($result->isValid());
		$this->assertEquals('slug', $tdm->reqString('a'));
		$this->assertEquals('slug-2', $tdm->reqString('b'));
	}

	/**
	 * @throws UnresolvableBindableException
	 * @throws BindMismatchException
	 */
	function testDocsSanitizeThenUnique(): void {
		$taken = ['asdf'];
		$isUnique = function (string $value) use (&$taken) {
			if (in_array($value, $taken, true)) return false;
			$taken[] = $value;
			return true;
		};
		$retry = RetryValueChangers::numberSuffixOnRetry(closure: $isUnique, min: 4, max: 30, fallBackOnNullValue: 'slug',
				valueNumberSuffixSeparator: '-');
		$tdm = new DataMap();

		$result = Bind::attrs(['c' => 'a§%sdf'])->toAttrs($tdm)
				->prop('c', Mappers::changeUntilValid($retry, Mappers::noSpecialChars(), Mappers::cleanString()))
				->exec($this->getMockBuilder(MagicContext::class)->getMock());
		var_dump($result->isValid(), $tdm->reqString('c', true, true));

		$this->assertTrue($result->isValid());
		$this->assertEquals('asdf-2', $tdm->reqString('c'));
	}
}