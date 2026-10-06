<?php

namespace n2n\bind\mapper\impl\valobj\util;

use n2n\bind\attribute\impl\Unmarshal;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\ex\err\ConfigurationError;
use n2n\reflection\ReflectionUtils;
use ReflectionClass;
use n2n\reflection\ObjectCreationFailedException;

/**
 * Provides a basic unmarshal Mapper based on {@link Mappers::subPropsForClass()}.
 *
 * Usage example:
 *
 * ```php
 * class SomeObject {
 *     use ObjectUnmarshalTrait;
 *
 *     public string $name;
 *     public int $age;
 *
 *     // ...
 * }
 * ```
 *
 * If there are some properties which require special mapping, additional Mappers for these properties can be defined,
 * which will be executed first.
 *
 * ```php
 * class SomeObject {
 *     use ObjectUnmarshalTrait {
 *         unmarshal as traitUnmarshal;
 *     }
 *
 *     public string $name;
 *     public int $age;
 *
 *     #[Unmarshal]
 *     static function unmarshal(): Mapper {
 *         return self::traitUnmarshal(Mappers::subProps()->prop('age', Mappers::value(fn ($v) => 999999)));
 *     }
 *  }
 *  ```
 *
 *  The example above provides a special Mapper for the age property.
 */
trait ObjectUnmarshalTrait {
	#[Unmarshal]
	static function unmarshal(Mapper ...$preMappers): Mapper {
		$class = new ReflectionClass(static::class);

		return Mappers::pipe(...$preMappers)
				->add(Mappers::subPropsForClass(static::class))
				->add(Mappers::subMergeToObject(function () use ($class) {
					try {
						return ReflectionUtils::createObject($class);
					} catch (ObjectCreationFailedException $e) {
						throw new ConfigurationError(self::class
								. ' was used in class which could not be instantiated. Reason: ' . $e->getMessage());
					}
				}));
	}
}