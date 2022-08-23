<?php

namespace n2n\bind\mapper\impl;

use n2n\bind\mapper\Mapper;
use n2n\util\type\TypeConstraint;
use n2n\bind\plan\Bindable;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ValidationContext;
use n2n\util\ex\IllegalStateException;
use n2n\validation\plan\Validatable;
use n2n\validation\err\ValidationMismatchException;
use n2n\bind\build\impl\Bind;

class MapperAdapter implements Mapper {

	function __construct(private ?TypeConstraint $typeConstraint) {
		$this->typeConstraint = $typeConstraint;
	}

	function getTypeConstraint(): ?TypeConstraint {
		if ($this->typeConstraint !== null) {
			return $this->typeConstraint;
		}

		throw new IllegalStateException(get_class($this) . ' did not provide a TypeConstraint (missing parent constructor call).');
	}

	/**
	 * @param Validatable $bindable
	 * @return mixed|null
	 *@throws ValidationMismatchException
	 */
	protected function readSafeValue(Bindable $bindable) {
		$value = $bindable->getValue();

		if ($this->typeConstraint === null) {
			return $value;
		}

		try {
			return $this->typeConstraint->validate($value);
		} catch (\n2n\util\type\ValueIncompatibleWithConstraintsException $e) {
			throw new MappingMismatchException('Validatable ' . $bindable->getName() . ' is not compatible with '
					. get_class($this), 0, $e);
		}
	}

	function map(array $bindables, ValidationContext $validationContext, MagicContext $magicContext): array {
		// TODO: Implement map() method.
	}
}