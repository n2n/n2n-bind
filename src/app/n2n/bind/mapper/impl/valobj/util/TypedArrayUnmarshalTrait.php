<?php

namespace n2n\bind\mapper\impl\valobj\util;

use n2n\bind\attribute\impl\Unmarshal;
use n2n\bind\mapper\Mapper;
use n2n\util\col\CollectionTypeUtils;
use n2n\bind\mapper\impl\Mappers;
use n2n\validation\validator\impl\Validators;
use n2n\util\col\TypedArray;
use n2n\util\ex\err\ConfigurationError;

trait TypedArrayUnmarshalTrait {
	#[Unmarshal]
	static function unmarshalMapper(): Mapper {
		if (!is_a(static::class, TypedArray::class)) {
			throw new ConfigurationError(self::class
					. ' must be only used in TypedArrays but it was used in ' . static::class);
		}

		$class = new \ReflectionClass(static::class);
		$namedTypeConstraint = CollectionTypeUtils::detectValueTypeConstraint(new \ReflectionClass(static::class));

		return Mappers::pipe(
				Mappers::subForeach(
						Mappers::unmarshal($namedTypeConstraint->getTypeName()),
						Validators::mandatoryIf(!$namedTypeConstraint->allowsNull())),
				Mappers::subMerge(),
				Mappers::value(fn (array $stringValueObjects) => $class->newInstance($stringValueObjects)));
	}
}