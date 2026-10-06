<?php

namespace n2n\bind\mapper\impl\valobj\mock;

use n2n\bind\mapper\impl\valobj\ValueObjectMock;
use n2n\util\col\attribute\ValueType;
use n2n\bind\mapper\impl\valobj\util\TypedArrayUnmarshalTrait;
use n2n\bind\mapper\impl\valobj\util\TypedArrayMarshalTrait;
use n2n\util\col\TypedArray;

#[ValueType(ValueObjectMock::class)]
class TypedArrayMarshalUnmarshalMock extends TypedArray {
	use TypedArrayMarshalTrait, TypedArrayUnmarshalTrait;

}