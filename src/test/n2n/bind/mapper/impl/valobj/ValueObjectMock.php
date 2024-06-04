<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\bind\attribute\impl\Marshal;
use n2n\bind\mapper\impl\Mappers;
use n2n\validation\validator\impl\ValidationUtils;
use n2n\spec\valobj\err\IllegalValueException;
use n2n\bind\mapper\Mapper;
use n2n\bind\attribute\impl\Unmarshal;
use n2n\spec\valobj\scalar\StringValueObject;

class ValueObjectMock implements StringValueObject {

	function __construct(private readonly string $value) {
		IllegalValueException::assertTrue(ValidationUtils::isEmail($this->value),
				'Invalid email: ' . $this->value);
	}

	#[Marshal]
	static function marshalMapper(): Mapper {
		return Mappers::valueClosure(fn (ValueObjectMock $mock) => $mock->toScalar());
	}

	#[Unmarshal]
	static function unmarshalMapper(): Mapper {
		return Mappers::pipe(Mappers::email(), Mappers::valueNotNullClosure(fn (string $email) => new self($email)));
	}

	function toScalar(): string {
		return $this->value;
	}
}