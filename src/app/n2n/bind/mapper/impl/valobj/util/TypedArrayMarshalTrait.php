<?php

namespace n2n\bind\mapper\impl\valobj\util;

use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\attribute\impl\Marshal;
use n2n\util\col\TypedArray;
use n2n\util\ex\err\ConfigurationError;
use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindBoundary;
use n2n\util\ex\NotYetImplementedException;

trait TypedArrayMarshalTrait {
	#[Marshal]
	static function marshalMapper(): Mapper {
		if (!is_subclass_of(static::class, TypedArray::class)) {
			throw new ConfigurationError(self::class
					. ' must be only used in TypedArrays but it was used in ' . static::class);
		}

		throw new NotYetImplementedException();

//		return Mappers::pipe(
//				Mappers::bindable(function (Bindable $b, BindBoundary $bindBoundary) {
//					$typedArray = $b->getValue();
//					assert($typedArray instanceof TypedArray);
//					foreach ($typedArray->toArray() as $key => $value) {
//						$bindBoundary->acquireBindable($b->getPath()->ext($key))->setValue($value);
//					}
//					$b->setExist(false);
//				}),
//				Mappers::subForeach(Mappers::marshal(), Mappers::value(fn ($v) => var_dump($v))),
//				Mappers::subMerge());
	}
}