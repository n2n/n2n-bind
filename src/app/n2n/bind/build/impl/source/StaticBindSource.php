<?php

namespace n2n\bind\build\impl\source;

use n2n\bind\plan\BindSource;
use n2n\bind\plan\BindInstance;
use n2n\bind\err\IncompatibleBindInputException;
use n2n\util\type\TypeUtils;

class StaticBindSource implements BindSource {

	function __construct(private ?array $values = null) {
	}

	function next(mixed $input): BindInstance {
		if ($input === null || $this->values === null) {
			return new StaticBindInstance($this->values ?? []);
		}

		if (is_array($input)) {
			return new StaticBindInstance($input);
		}

		throw new IncompatibleBindInputException('AttrsBindSource requires input to be of type array. Given: '
				. TypeUtils::getTypeInfo($input));
	}
}