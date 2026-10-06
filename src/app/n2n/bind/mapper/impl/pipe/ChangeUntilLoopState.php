<?php

namespace n2n\bind\mapper\impl\pipe;

use n2n\util\magic\MagicContext;

class ChangeUntilLoopState {
	public int $retryNo = 0;
	private array $validatedValues = [];

	function __construct(public mixed $originalValue, public readonly MagicContext $magicContext) {

	}

	function tryToGetFirstMappedNonNullValue(): mixed {
		return array_find(
				$this->validatedValues,
				static fn($value) => $value !== null
		);
	}


	function addMappedValue(mixed $value): void {
			$this->validatedValues[] = $value;
	}

}