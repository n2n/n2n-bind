<?php

namespace n2n\bind\mapper\impl\enum;

use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\type\TypeConstraints;
use n2n\util\EnumUtils;

class EnumMapper extends SingleMapperAdapter {
	function __construct(private \ReflectionEnum $enum, private bool $mandatory) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, TypeConstraints::namedType($this->enum, true, true));
		// @todo: test null without ''

		$bindable->setValue($value);

		$validationGroup = new ValidationGroup($this->createValidators(), [$bindable], $bindContext);
		$validationGroup->exec($magicContext);

		return true;
	}

	/**
	 * @return Validator[]
	 */
	private function createValidators() {
		$validators = [];

		if ($this->mandatory) {
			$validators[] = Validators::mandatory();
		}

		return $validators;
	}
}