<?php

namespace n2n\bind\mapper\impl\valobj\util;

use n2n\bind\attribute\impl\Unmarshal;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\ex\err\ConfigurationError;
use n2n\reflection\ReflectionUtils;
use ReflectionClass;
use n2n\reflection\ObjectCreationFailedException;

trait ObjectUnmarshalTrait {
	#[Unmarshal]
	static function unmarshal(): Mapper {
		$class = new ReflectionClass(static::class);

		return Mappers::pipe(
				Mappers::subPropsForClass(static::class),
				Mappers::subMergeToObject(function () use ($class) {
					try {
						return ReflectionUtils::createObject($class);
					} catch (ObjectCreationFailedException $e) {
						throw new ConfigurationError(self::class
								. ' was used in class which could not be instantiated. Reason: ' . $e->getMessage());
					}
				}));
	}
}