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
use n2n\bind\mapper\impl\date\DateTimeImmutableMapper;

class Mappers {

	/**
	 * @param bool $mandatory
	 * @param int|null $minlength
	 * @param int|null $maxlength
	 * @return CleanStringMapper
	 */
	static function cleanString(bool $mandatory = false, ?int $minlength = 0, ?int $maxlength = 255): CleanStringMapper {
		return new CleanStringMapper($mandatory, $minlength, $maxlength);
	}

	/**
	 * @param bool $mandatory
	 * @param int|null $min
	 * @param int|null $max
	 * @return IntMapper
	 */
	static function int(bool $mandatory = false, ?int $min = 0, ?int $max = 1000000): IntMapper {
		return new IntMapper($mandatory, $min, $max);
	}

	/**
	 * @param TypeConstraint $typeConstraint
	 * @return TypeMapper
	 */
	static function type(TypeConstraint $typeConstraint): TypeMapper {
		return new TypeMapper($typeConstraint);
	}

	/**
	 * @param bool $mandatory
	 * @return EmailMapper
	 */
	static function email(bool $mandatory = false): EmailMapper {
		return new EmailMapper($mandatory);
	}

	/**
	 * @param Closure $closure
	 * @return PropsClosureMapper
	 */
	public static function propsClosure(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, null);
	}

	/**
	 * @param Closure $closure
	 * @return PropsClosureMapper
	 */
	public static function propsClosureAny(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, false);
	}

	/**
	 * @param Closure $closure
	 * @return PropsClosureMapper
	 */
	public static function propsClosureEvery(Closure $closure): PropsClosureMapper {
		return new PropsClosureMapper($closure, true);
	}

	/**
	 * @param Closure $closure
	 * @return ValueClosureMapper
	 */
	public static function valueClosure(Closure $closure) {
		return new ValueClosureMapper($closure);
	}

	/**
	 * @param Closure $closure
	 * @return BindableClosureMapper
	 */
	static function bindableClosure(Closure $closure): BindableClosureMapper {
		return new BindableClosureMapper($closure);
	}

	/**
	 * @param Closure $closure
	 * @return BindablesClosureMapper
	 */
	static function bindablesClosure(Closure $closure): BindablesClosureMapper {
		return new BindablesClosureMapper($closure);
	}

	static function enum(\ReflectionEnum|string $enum, bool $mandatory = false): EnumMapper {
		return new EnumMapper(EnumUtils::valEnumArg($enum), $mandatory);
	}

	public static function dateTime(bool $mandatory = false, ?\DateTimeInterface $min = null, ?\DateTimeInterface $max = null): DateTimeMapper {
		return new DateTimeMapper($mandatory, $min, $max);
	}

	public static function dateTimeImmutable(bool $mandatory = false, ?\DateTimeInterface $min = null, ?\DateTimeInterface $max = null): DateTimeImmutableMapper {
		return new DateTimeImmutableMapper($mandatory, $min, $max);
	}

	static function n2nLocale(bool $mandatory = false, array $allowedValues = null) {
		return new N2nLocaleMapper($mandatory, $allowedValues);
	}
}
