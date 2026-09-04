<?php

namespace n2n\bind\mapper\impl\string\mock;

use n2n\bind\attribute\impl\Marshal;
use n2n\bind\mapper\impl\Mappers;
use n2n\bind\mapper\Mapper;
use n2n\bind\attribute\impl\Unmarshal;
use n2n\spec\valobj\scalar\StringValueObject;

class StringValObjMock implements StringValueObject {

	function __construct(private readonly string $value) {
	}

	#[Marshal]
	static function marshalMapper(): Mapper {
		return Mappers::valueClosure(fn (StringValObjMock $mock) => $mock->toScalar());
	}

	#[Unmarshal]
	static function unmarshalMapper(): Mapper {
		return Mappers::pipe(Mappers::noSpecialChars());
	}

	function toScalar(): string {
		return $this->value;
	}

}