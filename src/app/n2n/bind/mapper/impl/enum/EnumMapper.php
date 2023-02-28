<?php

namespace n2n\bind\mapper\impl\enum;

use n2n\bind\plan\Bindable;
use n2n\bind\plan\BindContext;
use n2n\util\magic\MagicContext;
use n2n\validation\plan\ValidationGroup;
use n2n\validation\validator\Validator;
use n2n\validation\validator\impl\Validators;
use n2n\bind\mapper\impl\SingleMapperAdapter;
use n2n\util\type\UnionTypeConstraint;
use n2n\util\EnumUtils;

class EnumMapper extends SingleMapperAdapter {
	function __construct(private bool $mandatory, private \ReflectionEnum|string $enum) {
	}

	protected function mapSingle(Bindable $bindable, BindContext $bindContext, MagicContext $magicContext): bool {
		$value = $this->readSafeValue($bindable, UnionTypeConstraint::from('string|int|null', false));

		if ($value !== null) {
			$bindable->setValue(EnumUtils::backedToUnit($value, $this->enum));
		}

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