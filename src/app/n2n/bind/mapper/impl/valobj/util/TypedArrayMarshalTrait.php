<?php

namespace n2n\bind\mapper\impl\valobj\util;

use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\attribute\impl\Marshal;
use n2n\util\col\TypedArray;
use n2n\util\ex\err\ConfigurationError;

trait TypedArrayMarshalTrait {
	#[Marshal]
	static function marshalMapper(): Mapper {
		if (!is_a(static::class, TypedArray::class)) {
			throw new ConfigurationError(self::class
					. ' must be only used in TypedArrays but it was used in ' . static::class);
		}

		return Mappers::pipe(
				Mappers::subForeach(Mappers::marshal()),
				Mappers::subMerge());
	}
}