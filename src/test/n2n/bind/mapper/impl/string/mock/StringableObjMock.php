<?php

namespace n2n\bind\mapper\impl\string\mock;

class StringableObjMock implements  \Stringable {

	function __construct(private readonly string $value) {
	}
	function toScalar(): string {
		return $this->value;
	}

	public function __toString(): string {
		return $this->value;
	}
}