<?php

namespace n2n\bind\mapper\impl\valobj;

use n2n\bind\attribute\impl\Marshal;
use n2n\bind\mapper\impl\Mappers;
use n2n\util\valobj\StringValueObject;
use n2n\validation\validator\impl\ValidationUtils;
use n2n\util\valobj\IllegalValueException;
use n2n\bind\mapper\Mapper;
use n2n\bind\attribute\impl\Unmarshal;

class ValueObjectMock implements StringValueObject {

	function __construct(private string $value) {
		IllegalValueException::assertTrue(ValidationUtils::isEmail($this->value),
				'Invalid email: ' . $this->value);
	}

	#[Marshal]
	static function marshalMapper(): Mapper {
		return Mappers::valueClosure(fn (ValueObjectMock $mock) => $mock->toValue());
	}

	#[Unmarshal]
	static function unmarshalMapper(): Mapper {
		return Mappers::pipe(Mappers::email(), Mappers::valueNotNullClosure(fn (string $email) => new self($email)));
	}

	function toValue(): string {
		return $this->value;
	}
}