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
use n2n\bind\mapper\impl\string\PathPartMapper;
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

class Mappers {

	/**
	 * @param bool $mandatory
	 * @param int|null $minlength
	 * @param int|null $maxlength
	 * @param bool $simpleWhitespacesOnly
	 * @return CleanStringMapper
	 */
	static function cleanString(bool $mandatory = false, ?int $minlength = 1, ?int $maxlength = 255,
			bool $simpleWhitespacesOnly = true): CleanStringMapper {
		return new CleanStringMapper($mandatory, $minlength, $maxlength, $simpleWhitespacesOnly);
	}

	static function cleanMultilineString(bool $mandatory = false, ?int $minlength = 1, ?int $maxlength = 255): CleanStringMapper {
		return self::cleanString($mandatory, $minlength, $maxlength, false);
	}

//	static function emptyStringToNull(): EmptyStringToNullMapper {
//		throw new NotYetImplementedException();
//	}

	/**
	 * @param bool $mandatory
	 * @param int|null $min
	 * @param int|null $max
	 * @return IntMapper
	 */
	static function int(bool $mandatory = false, ?int $min = -100000, ?int $max = 100000): IntMapper {
		return new IntMapper($mandatory, $min, $max);
	}

	/**
	 * @param bool $mandatory
	 * @param float|null $min
	 * @param float|null $max
	 * @param float|null $step
	 * @return FloatMapper
	 */
	static function float(bool $mandatory = false, ?float $min = -100000, ?float $max = 100000, ?float $step = 0.01): FloatMapper {
		return new FloatMapper($mandatory, $min, $max, $step);
	}

	/**
	 * @param TypeConstraint $typeConstraint
	 * @return TypeMapper
	 */
	static function type(TypeConstraint $typeConstraint): TypeMapper {
		return new TypeMapper($typeConstraint);
	}

	static function typeNotNull(TypeConstraint $typeConstraint): TypeMapper {
		return new TypeMapper($typeConstraint, true);
	}

	/**
	 * @param bool $mandatory
	 * @return EmailMapper
	 */
	static function email(bool $mandatory = false): EmailMapper {
		return new EmailMapper($mandatory);
	}

	/**
	 * @param bool $mandatory
	 * @param array|null $allowedSchemas
	 * @param bool $schemeRequired
	 * @param int $maxLength
	 * @return UrlMapper
	 */
	static function url(bool $mandatory = false, ?array $allowedSchemas = ['https', 'http'], bool $schemeRequired = true,
			int $maxLength = 2048): UrlMapper {
		return new UrlMapper($mandatory, $allowedSchemas, $schemeRequired, $maxLength);
	}

	/**
	 * @param Closure $closure
	 * @return PropsClosureMapper
	 */
	public static function propsClosure(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ALWAYS);
	}

	/**
	 * @param Closure $closure
	 * @return PropsClosureMapper
	 */
	public static function propsClosureAny(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ANY_BINDABLE_MUST_BE_PRESENT);
	}

	/**
	 * @param Closure $closure
	 * @return PropsClosureMapper
	 */
	public static function propsClosureEvery(Closure $closure): PropsClosureMapper {
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
	static function propsAsBindDataClosure(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, MultiMapMode::ALWAYS, true);
	}

	/**
	 * @param Closure $closure
	 * @return ValueClosureMapper
	 */
	public static function valueClosure(Closure $closure): ValueClosureMapper {
		return new ValueClosureMapper($closure, false);
	}

	/**
	 * @param Closure $closure
	 * @return ValueClosureMapper
	 */
	public static function valueNotNullClosure(Closure $closure): ValueClosureMapper {
		return new ValueClosureMapper($closure, true);
	}

	/**
	 * @param Closure $closure
	 * @return BindableClosureMapper
	 */
	static function bindableClosure(Closure $closure, bool $nonExistingSkipped = true, bool $dirtySkipped = true): BindableClosureMapper {
		return new BindableClosureMapper($closure, false, $nonExistingSkipped, $dirtySkipped);
	}

	/**
	 * @param Closure $closure
	 * @return BindableClosureMapper
	 */
	static function bindableNotNullClosure(Closure $closure): BindableClosureMapper {
		return new BindableClosureMapper($closure, true);
	}

	/**
	 * @param Closure $closure
	 * @return BindablesClosureMapper
	 */
	static function bindablesClosure(Closure $closure): BindablesClosureMapper {
		return new BindablesClosureMapper($closure);
	}

	static function closure(Closure $closure): BindablesClosureMapper {
		return new BindablesClosureMapper($closure);
	}

	static function enum(\ReflectionEnum|string $enum, bool $mandatory = false): EnumMapper {
		return new EnumMapper(EnumUtils::valEnumArg($enum), $mandatory);
	}


	/**
	 * @param bool $mandatory
	 * @param \DateTimeInterface|null $min
	 * @param \DateTimeInterface|null $max
	 * @return DateTimeMapper
	 */
	public static function dateTime(bool $mandatory = false, ?\DateTimeInterface $min = null, ?\DateTimeInterface $max = null): DateTimeMapper {
		return new DateTimeMapper($mandatory, $min, $max);
	}

	public static function dateTimeImmutable(bool $mandatory = false, ?\DateTimeInterface $min = null, ?\DateTimeInterface $max = null): DateTimeImmutableMapper {
		return new DateTimeImmutableMapper($mandatory, $min, $max);
	}

	static function dateTimeSql(): DateTimeSqlMapper {
		return new DateTimeSqlMapper();
	}

	static function dateSql(): DateSqlMapper {
		return new DateSqlMapper();
	}

	static function n2nLocale(bool $mandatory = false, ?array $allowedValues = null): N2nLocaleMapper {
		return new N2nLocaleMapper($mandatory, $allowedValues);
	}

	static function pathPart(?Closure $uniqueTester, ?string $generationIfNullBaseName, bool $mandatory = false,
			?int $minlength = 3, ?int $maxlength = 150): PathPartMapper {
		return new PathPartMapper($uniqueTester, $generationIfNullBaseName, $minlength, $maxlength, $mandatory);
	}

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
	 */
	static function subProps(): SubPropsMapper {
		return new SubPropsMapper();
	}

	static function subPropsForClass(\ReflectionClass|string $class): SubPropsForClassMapper {
		if (is_string($class)) {
			$class = ReflectionUtils::createReflectionClass($class);
		}

		return new SubPropsForClassMapper($class);
	}

	static function subPropsFromClass(\ReflectionClass|string $class): SubPropsFromClassMapper {
		if (is_string($class)) {
			$class = ReflectionUtils::createReflectionClass($class);
		}

		return new SubPropsFromClassMapper($class);
	}

	static function subForeach(Mapper|Validator ...$mappers): SubForeachMapper {
		return new SubForeachMapper(ValidatorMapper::convertValidators($mappers));
	}

	/**
	 * Merges values of descendant Bindables as array into current Bindable and removes them.
	 */
	static function subMerge(): SubMergeMapper {
		return new SubMergeMapper();
	}

	/**
	 * Merges values of descendant Bindables as to an object into the current Bindable and removes them.
	 */
	static function subMergeToObject(\Closure $objCallbackClosure): SubMergeToObjectMapper {
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
	 */
	static function fromBindDataClosure(\Closure $closure): FromBindDataClosureMapper {
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
	 */
	static function valueAsBindDataClosure(\Closure $closure): ValueAsBindDataClosureMapper {
		return new ValueAsBindDataClosureMapper($closure);
	}

	static function delete(): DeleteMapper {
		return new DeleteMapper();
	}

	static function marshal(): MarshalMapper {
		return new MarshalMapper();
	}

	static function unmarshal(string $typeName): UnmarshalMapper {
		return new UnmarshalMapper($typeName);
	}

	/**
	 * Aborts bind process if any of the passed Bindables are invalid.
	 *
	 * @return AbortIfMapper
	 */
	static function abortIfInvalid(): AbortIfMapper {
		return new AbortIfMapper(AbortIfCondition::INVALID);
	}

	/**
	 * Aborts bind process if any of the passed Bindables are dirty.
	 *
	 * @return AbortIfMapper
	 */
	static function abortIfDirty(): AbortIfMapper {
		return new AbortIfMapper(AbortIfCondition::DIRTY);
	}

	static function doIfNull(bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return self::doIfValueClosure(fn ($v) => $v === null, $abort, $skipNextMappers, $chLogical);
	}

	static function doIfNotNull(bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return self::doIfValueClosure(fn ($v) => $v !== null, $abort, $skipNextMappers, $chLogical);
	}

	static function doIfValueClosure(\Closure $closure, bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return new DoIfSingleClosureMapper($closure, $abort, $skipNextMappers, $chLogical);
	}

	static function doIfInvalid(bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return self::doIfBindableClosure(fn (Bindable $b) => !$b->isValid(), $abort, $skipNextMappers, $chLogical);
	}

	static function doIfBindableClosure(\Closure $closure, bool $abort = false, bool $skipNextMappers = false,
			?bool $chLogical = null): DoIfSingleClosureMapper {
		return (new DoIfSingleClosureMapper($closure, $abort, $skipNextMappers, $chLogical))
				->setValueAsFirstArg(false);
	}

	static function factoryClosure(\Closure $closure): FactoryClosureMapper  {
		return new FactoryClosureMapper($closure);
	}

	static function mustExistIf(\Closure|bool $closureOrBool, bool $elseChExistToFalse = false): MustExistIfMapper {
		return new MustExistIfMapper($closureOrBool, $elseChExistToFalse);
	}

	static function mustExistAllIfAnyExist(): Mapper {
		return new MustExistIfMapper(fn (BindBoundary $bindBoundary)
				=> 0 < count(array_filter($bindBoundary->getBindables(), fn (Bindable $b) => $b->doesExist())));
	}

	static function time(bool $mandatory = false, ?Time $min = null, ?Time $max = null): TimeMapper {
		return new TimeMapper($mandatory, $min, $max);
	}

	static function timeSql(): TimeSqlMapper {
		return new TimeSqlMapper();
	}

	static function date(bool $mandatory = false, ?Date $min = null, ?Date $max = null): DateMapper {
		return new DateMapper($mandatory, $min, $max);
	}

	/**
	 * Renames Bindable according to the passed a map.
	 *
	 * @param array<string> $propsMap key old property name, value new property name.
	 * @return Mapper
	 */
	static function rename(array $propsMap): Mapper {
		return self::propsClosure(function (array $props) use ($propsMap) {
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
