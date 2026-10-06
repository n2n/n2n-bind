<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\spec\valobj\scalar\StringValueObject;
use n2n\bind\attribute\impl\Marshal;
use n2n\bind\mapper\Mapper;
use n2n\bind\mapper\impl\Mappers;

class PathPartObjMock implements StringValueObject, \JsonSerializable {
	function __construct(public ?string $value) {
	}

	public function jsonSerialize(): array {
		return ['path' => $this->value];
	}
	#[Marshal]
	static function marshalMapper(): Mapper {
		return Mappers::value(fn (PathPartObjMock $mock) => $mock->toScalar());
	}
//
//	#[Unmarshal]
//	static function unmarshalMapper(): Mapper {
//		$class = new \ReflectionClass(static::class);
//		return Mappers::pipe(
//				Mappers::changeUntilValid(
//						$this->retryValueChanger,
//						Mappers::noSpecialChars(), Mappers::cleanString()
//				),
//				Mappers::valueIfNotNull(fn(string $value) => $class->newInstance($value)));
//	}
	function toScalar(): string {
		return $this->value;
	}
	function __toString(): string {
		return $this->value;
	}
}