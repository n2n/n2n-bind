<?php
/*
 * Copyright (c) 2012-2016, Hofmänner New Media.
 * DO NOT ALTER OR REMOVE COPYRIGHT NOTICES OR THIS FILE HEADER.
 *
 * This file is part of the N2N FRAMEWORK.
 *
 * The N2N FRAMEWORK is free software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software Foundation, either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * N2N is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even
 * the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details: http://www.gnu.org/licenses/
 *
 * The following people participated in this project:
 *
 * Andreas von Burg.....: Architect, Lead Developer
 * Bert Hofmänner.......: Idea, Frontend UI, Community Leader, Marketing
 * Thomas Günther.......: Developer, Hangar
 */
namespace n2n\bind\mapper\impl;

use n2n\bind\mapper\impl\string\CleanStringMapper;
use n2n\bind\mapper\impl\numeric\IntMapper;
use n2n\bind\mapper\impl\string\EmailMapper;
use n2n\bind\mapper\impl\closure\PropsClosureMapper;
use n2n\bind\mapper\impl\closure\ValueClosureMapper;
use n2n\util\type\TypeConstraint;
use n2n\bind\mapper\impl\type\TypeMapper;
use n2n\bind\mapper\impl\closure\BindableClosureMapper;
use n2n\bind\mapper\impl\closure\BindablesClosureMapper;
use Closure;
use n2n\bind\mapper\impl\enum\EnumMapper;
use n2n\util\EnumUtils;
use n2n\bind\mapper\impl\date\DateTimeMapper;
use n2n\bind\mapper\impl\l10n\N2nLocaleMapper;
use n2n\bind\mapper\impl\numeric\FloatMapper;
use n2n\bind\mapper\impl\date\DateTimeImmutableMapper;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\compose\SubPropsMapper;
use n2n\bind\mapper\impl\compose\FromBindDataClosureMapper;
use n2n\bind\mapper\impl\mod\DeleteMapper;
use n2n\bind\mapper\impl\mod\SubMergeMapper;
use n2n\validation\validator\Validator;
use n2n\bind\mapper\impl\closure\ValueAsBindDataClosureMapper;
use n2n\bind\mapper\impl\valobj\UnmarshalMapper;
use n2n\bind\mapper\impl\valobj\MarshalMapper;
use n2n\bind\mapper\impl\mod\SubMergeToObjectMapper;
use n2n\bind\mapper\impl\op\AbortIfMapper;
use n2n\bind\mapper\impl\op\AbortIfCondition;
use n2n\bind\mapper\impl\date\DateTimeSqlMapper;
use n2n\bind\mapper\impl\op\DoIfSingleClosureMapper;
use n2n\bind\mapper\impl\date\DateSqlMapper;
use n2n\bind\mapper\impl\compose\SubForeachMapper;
use n2n\bind\mapper\impl\compose\FactoryClosureMapper;
use n2n\bind\mapper\impl\op\MustExistIfMapper;
use n2n\bind\mapper\impl\date\TimeMapper;
use n2n\util\calendar\Time;
use n2n\bind\mapper\impl\string\UrlMapper;
use n2n\bind\mapper\impl\date\TimeSqlMapper;
use n2n\bind\plan\BindBoundary;
use n2n\bind\plan\Bindable;
use n2n\util\calendar\Date;
use n2n\bind\mapper\impl\date\DateMapper;
use n2n\reflection\ReflectionUtils;
use n2n\bind\mapper\impl\compose\SubPropsForClassMapper;
use n2n\bind\mapper\impl\compose\SubPropsFromClassMapper;
use n2n\bind\mapper\impl\string\ColorHexMapper;
use n2n\bind\mapper\impl\op\DoIfMapper;
use n2n\bind\mapper\impl\op\ValueIfNotExistsMapper;
use n2n\bind\mapper\impl\mod\ValueToSubValuesMapper;
use n2n\bind\mapper\impl\string\PhoneMapper;
use n2n\bind\mapper\impl\string\NoSpecialCharsMapper;
use n2n\bind\mapper\impl\pipe\ChangeUntilValidMapper;
use n2n\bind\mapper\impl\pipe\RetryValueChanger;
use n2n\bind\mapper\impl\string\PathPartMapper;

class Mappers {

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/clean-string
	 */
	static function cleanString(bool $mandatory = false, ?int $minlength = 1, ?int $maxlength = 255,
			bool $simpleWhitespacesOnly = true): CleanStringMapper {
		return new CleanStringMapper($mandatory, $minlength, $maxlength, $simpleWhitespacesOnly);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/clean-string
	 */
	static function cleanMultilineString(bool $mandatory = false, ?int $minlength = 1, ?int $maxlength = 255): CleanStringMapper {
		return self::cleanString($mandatory, $minlength, $maxlength, false);
	}

//	static function emptyStringToNull(): EmptyStringToNullMapper {
//		throw new NotYetImplementedException();
//	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/int
	 */
	static function int(bool $mandatory = false, ?int $min = -100000, ?int $max = 100000): IntMapper {
		return new IntMapper($mandatory, $min, $max);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/float
	 */
	static function float(bool $mandatory = false, ?float $min = -100000, ?float $max = 100000, ?float $step = 0.01): FloatMapper {
		return new FloatMapper($mandatory, $min, $max, $step);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/type
	 */
	static function type(TypeConstraint $typeConstraint): TypeMapper {
		return new TypeMapper($typeConstraint);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/type
	 */
	static function typeNotNull(TypeConstraint $typeConstraint): TypeMapper {
		return new TypeMapper($typeConstraint, true);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/email
	 */
	static function email(bool $mandatory = false): EmailMapper {
		return new EmailMapper($mandatory);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/phone
	 */
	static function phone(bool $mandatory = false): PhoneMapper {
		return new PhoneMapper($mandatory);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/url
	 */
	static function url(bool $mandatory = false, ?array $allowedSchemas = ['https', 'http'], bool $schemeRequired = true,
			int $maxLength = 2048): UrlMapper {
		return new UrlMapper($mandatory, $allowedSchemas, $schemeRequired, $maxLength);
	}

	/**
	 * @deprecated use {@link self::values()}
	 */
	public static function propsClosure(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ALWAYS);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/values
	 */
	public static function values(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ALWAYS);
	}

	/**
	 * @deprecated use {@link self::valuesAny()}
	 */
	public static function propsClosureAny(Closure $closure): PropsClosureMapper {
		return self::valuesAny($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/values
	 */
	static function valuesAny(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ANY_BINDABLE_MUST_BE_PRESENT);
	}

	/**
	 * @deprecated use {@link self::valuesEvery()}
	 */
	public static function propsClosureEvery(Closure $closure): PropsClosureMapper {
		return self::valuesEvery($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/values
	 */
	static function valuesEvery(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::EVERY_BINDABLE_MUST_BE_PRESENT);
	}

	/**
	 * Example:
	 *
	 * <pre>
	 * 	Bind::attrs($srcDataMap)->toAttrs($targetDataMap)
	 * 			->props(['foo', 'bar'], Mappers::propsAsBindDataClosure(function (BindData $bindData) {
	 * 				$fooValue = $bindData->reqString('foo');
	 * 				$barValue = $bindData->reqString('bar');

	 * 				return ['foo' => 'someOtherFooValue', 'bar' => 'someOtherBarValue'];
	 * 			});
	 * </pre>
	 */
	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/values
	 */
	static function propsAsBindDataClosure(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ALWAYS, true);
	}

	/**
	 * @deprecated use {@link self::value()}
	 */
	public static function valueClosure(Closure $closure): ValueClosureMapper {
		return new ValueClosureMapper($closure, false);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/value
	 */
	public static function value(Closure $closure): ValueClosureMapper {
		return new ValueClosureMapper($closure, false);
	}

	/**
	 * @deprecated use {@link self::valueIfNotNull()}
	 */
	public static function valueNotNullClosure(Closure $closure): ValueClosureMapper {
		return new ValueClosureMapper($closure, true);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/value
	 */
	public static function valueIfNotNull(Closure $closure): ValueClosureMapper {
		return new ValueClosureMapper($closure, true);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/value-to-sub-values
	 */
	static function valueToSubValues(Closure|array $subValuesClosureOrArray): ValueToSubValuesMapper {
		return new ValueToSubValuesMapper($subValuesClosureOrArray);
	}

	/**
	 * @deprecated use {@link self::bindable()}
	 */
	static function bindableClosure(Closure $closure, bool $nonExistingSkipped = true, bool $dirtySkipped = true): BindableClosureMapper {
		return self::bindable($closure, $nonExistingSkipped, $dirtySkipped);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/bindable
	 */
	static function bindable(Closure $closure, bool $nonExistingSkipped = true, bool $dirtySkipped = true): BindableClosureMapper {
		return new BindableClosureMapper($closure, false, $nonExistingSkipped, $dirtySkipped);
	}

	/**
	 * @deprecated use {@link self::bindableIfNotNull()}
	 */
	static function bindableNotNullClosure(Closure $closure): BindableClosureMapper {
		return self::bindableIfNotNull($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/bindable
	 */
	static function bindableIfNotNull(Closure $closure): BindableClosureMapper {
		return new BindableClosureMapper($closure, true);
	}

	/**
	 * @deprecated use {@link self::bindables()}
	 */
	static function bindablesClosure(Closure $closure): BindablesClosureMapper {
		return new BindablesClosureMapper($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/bindables
	 */
	static function bindables(Closure $closure): BindablesClosureMapper {
		return new BindablesClosureMapper($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/bindables
	 */
	static function closure(Closure $closure): BindablesClosureMapper {
		return new BindablesClosureMapper($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/enum
	 */
	static function enum(\ReflectionEnum|string $enum, bool $mandatory = false): EnumMapper {
		return new EnumMapper(EnumUtils::valEnumArg($enum), $mandatory);
	}

	/**
	 * Example Usage:
	 * ```php
	 * $result = Bind::values('2023-12-13 12:04:12')->map(Mappers::dateTime())->exec();
	 * var_dump($result->get());
	 * ```
	 *
	 * @param bool $mandatory
	 * @param \DateTimeInterface|null $min
	 * @param \DateTimeInterface|null $max
	 * @return DateTimeMapper
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/datetime
	 */
	public static function dateTime(bool $mandatory = false, ?\DateTimeInterface $min = null, ?\DateTimeInterface $max = null): DateTimeMapper {
		return new DateTimeMapper($mandatory, $min, $max);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/datetime
	 */
	public static function dateTimeImmutable(bool $mandatory = false, ?\DateTimeInterface $min = null, ?\DateTimeInterface $max = null): DateTimeImmutableMapper {
		return new DateTimeImmutableMapper($mandatory, $min, $max);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/datetime-sql
	 */
	static function dateTimeSql(): DateTimeSqlMapper {
		return new DateTimeSqlMapper();
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/date-sql
	 */
	static function dateSql(): DateSqlMapper {
		return new DateSqlMapper();
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/n2n-locale
	 */
	static function n2nLocale(bool $mandatory = false, ?array $allowedValues = null): N2nLocaleMapper {
		return new N2nLocaleMapper($mandatory, $allowedValues);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/path-part
	 */
	static function pathPart(?Closure $uniqueTester = null, ?string $generationIfNullBaseName = null, bool $mandatory = false,
			int $minlength = 3, int $maxlength = 63, string $fillStr = 'path', int $maxRetryNo = 9999): Mapper {

		return new PathPartMapper($uniqueTester, $generationIfNullBaseName, $minlength, $maxlength, $mandatory)
				->setFillStr($fillStr)->setMaxRetryNo($maxRetryNo);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/no-special-chars
	 */
	static function noSpecialChars(bool $mandatory = false, ?int $minlength = 1, ?int $maxlength = 255,
			bool $lowercaseOnly = true): NoSpecialCharsMapper {
		return new NoSpecialCharsMapper($mandatory, $lowercaseOnly, $minlength, $maxlength);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/change-until-valid
	 */
	static function changeUntilValid(RetryValueChanger $retryValueChanger, Mapper|Validator ... $mappers): ChangeUntilValidMapper {
		$mappers = ValidatorMapper::convertValidators($mappers);
		return new ChangeUntilValidMapper($retryValueChanger, $mappers);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/pipe
	 */
	static function pipe(Mapper|Validator ...$mappers): PipeMapper {
		$mappers = ValidatorMapper::convertValidators($mappers);
		return new PipeMapper($mappers);
	}


	/**
	 * @deprecated use {@link self::subProps()}
	 * @return SubPropsMapper
	 */
	static function subProp(): SubPropsMapper {
		return self::subProps();
	}

	/**
	 * Example:
	 *
	 * <pre>
	 * 	Bind::attrs($srcDataMap)->toAttrs($targetDataMap)
	 * 			->logicalProp('foo', Mappers::subProp()->prop('childOfFoo', Mappers::someMapper())
	 * </pre>
	 *
	 * @return SubPropsMapper
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/sub-props
	 */
	static function subProps(): SubPropsMapper {
		return new SubPropsMapper();
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/sub-props-for-class
	 */
	static function subPropsForClass(\ReflectionClass|string $class): SubPropsForClassMapper {
		if (is_string($class)) {
			$class = ReflectionUtils::createReflectionClass($class);
		}

		return new SubPropsForClassMapper($class);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/sub-props-for-class
	 */
	static function subPropsFromClass(\ReflectionClass|string $class): SubPropsFromClassMapper {
		if (is_string($class)) {
			$class = ReflectionUtils::createReflectionClass($class);
		}

		return new SubPropsFromClassMapper($class);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/sub-foreach
	 */
	static function subForeach(Mapper|Validator ...$mappers): SubForeachMapper {
		return new SubForeachMapper(ValidatorMapper::convertValidators($mappers));
	}

	/**
	 * Merges values of descendant Bindables as array into current Bindable and removes them.
	 */
	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/sub-merge
	 */
	static function subMerge(): SubMergeMapper {
		return new SubMergeMapper();
	}

	/**
	 * Merges values of descendant Bindables as to an object into the current Bindable and removes them.
	 *
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/sub-merge
	 */
	static function subMergeToObject(Closure $objCallbackClosure): SubMergeToObjectMapper {
		return new SubMergeToObjectMapper($objCallbackClosure);
	}

	/**
	 * Example:
	 *
	 * <pre>
	 * 	Bind::attrs($srcDataMap)->toAttrs($targetDataMap)
	 * 			->prop('foo', Mappers::fromBindDataClosure(function (BindData $bindData) {
	 * 				$mandatory, $bindData->reqBool('propWhichWillDecideIfMandatory');
	 * 				return Mappers::subProp()->dynProp('childOfFoo', $mandatory, Mappers::someMapper())
	 * 			});
	 * </pre>
	 *
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/from-bind-data-closure
	 */
	static function fromBindDataClosure(Closure $closure): FromBindDataClosureMapper {
		return new FromBindDataClosureMapper($closure);
	}

	/**
	 * Example:
	 *
	 * <pre>
	 * 	Bind::attrs($srcDataMap)->toAttrs($targetDataMap)
	 * 			->prop('foo', Mappers::valueAsBindDataClosure(function (BindData $bindData) {
	 * 				$value = $bindData->reqString('childPropOfFoo');

	 * 				return ['childPropOfFoo' => 'someOtherValue'];
	 * 			});
	 * </pre>
	 *
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/value-as-bind-data
	 */
	static function valueAsBindDataClosure(Closure $closure): ValueAsBindDataClosureMapper {
		return new ValueAsBindDataClosureMapper($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/delete
	 */
	static function delete(): DeleteMapper {
		return new DeleteMapper();
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/marshal
	 */
	static function marshal(): MarshalMapper {
		return new MarshalMapper();
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/unmarshal
	 */
	static function unmarshal(string $typeName): UnmarshalMapper {
		return new UnmarshalMapper($typeName);
	}

	/**
	 * Aborts bind process if any of the passed Bindables are invalid.
	 *
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/abort-if
	 *
	 * @return AbortIfMapper
	 */
	static function abortIfInvalid(): AbortIfMapper {
		return new AbortIfMapper(AbortIfCondition::INVALID);
	}

	/**
	 * Aborts bind process if any of the passed Bindables are dirty.
	 *
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/abort-if
	 *
	 * @return AbortIfMapper
	 */
	static function abortIfDirty(): AbortIfMapper {
		return new AbortIfMapper(AbortIfCondition::DIRTY);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function doIfNull(bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return self::doIfValueClosure(fn ($v) => $v === null, $abort, $skipNextMappers, $chLogical);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function doIfNotNull(bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return self::doIfValueClosure(fn ($v) => $v !== null, $abort, $skipNextMappers, $chLogical);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function doIfValueClosure(Closure $closure, bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null, ?bool $chExists = null, bool $nonExistingSkipped = true,
			bool $cascaded = false): DoIfSingleClosureMapper {
		if ($chExists === true && $nonExistingSkipped === true) {
			throw new \InvalidArgumentException(
					'It makes no sense when arguments chExists and nonExistingSkipped both are true.');
		}

		return (new DoIfSingleClosureMapper($closure, $abort, $skipNextMappers, $chLogical, $chExists))
				->setNonExistingSkipped($nonExistingSkipped)
				->setCascaded($cascaded);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function doIfInvalid(bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return self::doIfBindableClosure(fn (Bindable $b) => !$b->isValid(), $abort, $skipNextMappers, $chLogical);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function doIfBindableClosure(Closure $closure, bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null, ?bool $chExists = null, bool $nonExistingSkipped = true,
			bool $cascaded = false): DoIfSingleClosureMapper {
		return self::doIfValueClosure($closure, $abort, $skipNextMappers, $chLogical, $chExists, $nonExistingSkipped,
						$cascaded)
				->setValueAsFirstArg(false);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function deleteIfValueClosure(Closure $closure, bool $cascaded = true): DoIfSingleClosureMapper {
		return self::doIfValueClosure($closure, chExists: false, cascaded: $cascaded);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function deleteIfBindableClosure(Closure $closure, bool $cascaded = true): DoIfSingleClosureMapper {
		return self::doIfBindableClosure($closure, chExists: false, cascaded: $cascaded);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function doIf(Closure|bool $closureOrBool, bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null, ?bool $chExists = null, bool $cascaded = false): DoIfMapper {
		return (new DoIfMapper($closureOrBool, $abort, $skipNextMappers, $chLogical, $chExists))
				->setCascaded($cascaded);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/factory-closure
	 */
	static function factoryClosure(Closure $closure): FactoryClosureMapper  {
		return new FactoryClosureMapper($closure);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/do-if
	 */
	static function deleteIf(Closure|bool $closureOrBool, bool $cascaded = true): DoIfMapper {
		return self::doIf($closureOrBool, chExists: false, cascaded: $cascaded);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/must-exist-if
	 */
	static function mustExistIf(Closure|bool $closureOrBool, bool $elseChExistToFalse = false): MustExistIfMapper {
		return new MustExistIfMapper($closureOrBool, $elseChExistToFalse);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/must-exist-if
	 */
	static function mustExistAllIfAnyExist(): Mapper {
		return new MustExistIfMapper(fn (BindBoundary $bindBoundary)
				=> 0 < count(array_filter($bindBoundary->getBindables(), fn (Bindable $b) => $b->doesExist())));
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/time
	 */
	static function time(bool $mandatory = false, ?Time $min = null, ?Time $max = null): TimeMapper {
		return new TimeMapper($mandatory, $min, $max);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/time-sql
	 */
	static function timeSql(): TimeSqlMapper {
		return new TimeSqlMapper();
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/date
	 */
	static function date(bool $mandatory = false, ?Date $min = null, ?Date $max = null): DateMapper {
		return new DateMapper($mandatory, $min, $max);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/color-hex
	 */
	static function colorHex(bool $mandatory = false): ColorHexMapper {
		return new ColorHexMapper($mandatory);
	}

	/**
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/value-if-not-exists
	 */
	static function valueIfNotExists(mixed $closureOrValue): ValueIfNotExistsMapper {
		return new ValueIfNotExistsMapper($closureOrValue);
	}

	/**
	 * Renames Bindable according to the passed a map.
	 *
	 * @see https://docs.n2n.rocks/docs/n2n-bind/mappers/rename
	 *
	 * @param array<string> $propsMap key old property name, value new property name.
	 * @return Mapper
	 */
	static function rename(array $propsMap): Mapper {
		return self::values(function (array $props) use ($propsMap) {
			$newProps = [];
			foreach ($propsMap as $oldName => $newName) {
				if (array_key_exists($oldName, $props)) {
					$newProps[$newName] = $props[$oldName];
				}
			}

			foreach (array_keys($propsMap) as $oldName) {
				unset($props[$oldName]);
			}

			foreach ($newProps as $name => $value) {
				$props[$name] = $value;
			}

			return $props;
		});
	}

}
