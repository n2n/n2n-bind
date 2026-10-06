<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\bind\attribute\impl\Marshal;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\mapper\Mapper;
use n2n\bind\attribute\impl\Unmarshal;
use n2n\spec\valobj\scalar\StringValueObject;

class StringValObjMock implements StringValueObject, \Stringable {

	function __construct(private readonly string $value) {
	}

	#[Marshal]
	static function marshalMapper(): Mapper {
		return Mappers::value(fn (StringValObjMock $mock) => $mock->toScalar());
	}

	#[Unmarshal]
	static function unmarshalMapper(): Mapper {
		$class = new \ReflectionClass(static::class);
		return Mappers::pipe(
				Mappers::cleanString(),
				Mappers::valueIfNotNull(fn(string $value) => $class->newInstance($value)));
	}

	function toScalar(): string {
		return $this->value;
	}

	function __toString(): string {
		return $this->value;
	}

}