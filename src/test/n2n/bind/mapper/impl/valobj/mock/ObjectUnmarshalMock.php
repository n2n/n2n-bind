<?php

namespace n2n\bind\mapper\impl\valobj\mock;

use n2n\bind\mapper\impl\valobj\util\ObjectUnmarshalTrait;

class ObjectUnmarshalMock {
	use ObjectUnmarshalTrait;

	public string $name;
	public int $age;

}
